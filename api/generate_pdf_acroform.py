import sys
import os
import sqlite3
import datetime
import pymupdf  # PyMuPDF

import io
from PIL import Image

def get_transparent_signature_bytes(image_path):
    try:
        img = Image.open(image_path).convert("RGBA")
        datas = img.getdata()
        newData = []
        for item in datas:
            # If white or nearly white (or already transparent), make transparent
            if item[3] < 10 or (item[0] > 230 and item[1] > 230 and item[2] > 230):
                newData.append((255, 255, 255, 0))
            else:
                newData.append(item)
        img.putdata(newData)
        buf = io.BytesIO()
        img.save(buf, format="PNG")
        return buf.getvalue()
    except Exception:
        with open(image_path, "rb") as f:
            return f.read()

def generate_pdf(token):
    base_dir = os.path.dirname(os.path.abspath(__file__))
    db_path = os.path.join(base_dir, '..', 'storage', 'consent.db')
    template_path = os.path.join(base_dir, '..', 'public', 'template', 'sctcm-treatment.pdf')
    
    if not os.path.exists(template_path):
        print(f"ERROR: Template not found at {template_path}. Please upload the fillable PDF template.", file=sys.stderr)
        sys.exit(1)
        
    try:
        conn = sqlite3.connect(db_path)
        conn.row_factory = sqlite3.Row
        cursor = conn.cursor()
        
        # Get patient
        cursor.execute("SELECT * FROM patients WHERE consent_id = ?", (token,))
        patient = cursor.fetchone()
        if not patient:
            print("ERROR: Patient data not found.", file=sys.stderr)
            sys.exit(1)
            
        # Get guardian
        cursor.execute("SELECT * FROM guardians WHERE consent_id = ?", (token,))
        guardian = cursor.fetchone()
        
        # Get medical answers
        cursor.execute("SELECT * FROM medical_answers WHERE consent_id = ?", (token,))
        medical_rows = cursor.fetchall()
        medical = {row['question_code']: row for row in medical_rows}
        
        # Get signatures
        cursor.execute("SELECT * FROM signatures WHERE consent_id = ?", (token,))
        signature_rows = cursor.fetchall()
        signatures = {row['type']: row for row in signature_rows}
        
        # Get consent created_at for filename
        cursor.execute("SELECT created_at FROM consent_forms WHERE id = ?", (token,))
        consent = cursor.fetchone()
        
        conn.close()
    except Exception as e:
        print(f"ERROR: Database error: {str(e)}", file=sys.stderr)
        sys.exit(1)
        
    # Prepare form data
    patient_name = patient['name'] if patient['name'] else 'Unknown'
    
    # 1. Sex as M / F
    gender_raw = str(patient['gender'] or '').strip()
    if gender_raw.lower().startswith('m'):
        gender_val = 'M'
    elif gender_raw.lower().startswith('f'):
        gender_val = 'F'
    else:
        gender_val = gender_raw
        
    form_data = {
        'Text1': patient_name,
        'Text2': patient['date_of_birth'],
        'Text3': gender_val,
        'Text4': patient['nric'],
        'Text5': patient['contact_number']
    }
    
    if guardian:
        form_data['Text38'] = guardian['name']
        if guardian['relationship']:
            form_data['Text38'] += f" ({guardian['relationship']})"
        
    # Map medical answers
    # Database keys to PDF Question numbers
    med_mapping = {
        'operation': 'Text6',
        'medication': 'Text12',
        'allergies': 'Text9',
        'asthma': 'Text13',
        'hbp': 'Text14',
        'diabetes': 'Text15',
        'depression': 'Text16',
        'skin': 'Text17',
        'injuries': 'Text18',
        'mobility': 'Text19',
        'heart': 'Text20',
        'pacemaker': 'Text21',
        'bleeding': 'Text22',
        'hiv': 'Text23',
        'thalassemia': 'Text24',
        'seizures': 'Text25',
        'hepatitis': 'Text26',
        'cancer': 'Text27',
        'fainting': 'Text28',
        'pregnant': 'Text29',
        'irregular_periods': 'Text30'
    }
    
    spec_mapping = {
        'cancer': True,
        'allergies': True,
        'operation': True,
        'medication': True,
        'others': True
    }

    specifications = []

    for key, row in medical.items():
        ans = row['answer']
        # If it's a "others" field, we just append to specifications
        if key == 'others':
            if row['specification']:
                specifications.append(row['specification'])
            continue
            
        if key in med_mapping:
            field_id = med_mapping[key]
            # 2. Add Mandarin to Choice (Yes 有 / No 无 / Unsure 不确定)
            if ans == 'Yes':
                form_data[field_id] = "Yes 有"
            elif ans == 'No':
                form_data[field_id] = "No 无"
            elif ans == 'Unsure':
                form_data[field_id] = "Unsure 不确定"
            else:
                form_data[field_id] = ans
                
            # Handle specification
            if ans == 'Yes' and key in spec_mapping and row['specification']:
                specifications.append(f"{key}: {row['specification']}")
                
    if specifications:
        form_data['Text31'] = ", ".join(specifications)

    # Dates
    if 'patient' in signatures:
        form_data['Text32'] = signatures['patient']['signed_at'].split(' ')[0]
    if 'practitioner' in signatures:
        form_data['Text33'] = signatures['practitioner']['signed_at'].split(' ')[0]
        # Adding physician name from signature row
        if signatures['practitioner']['signed_by']:
            form_data['Text37'] = signatures['practitioner']['signed_by']

    try:
        doc = pymupdf.open(template_path)
        
        for i, page in enumerate(doc):
            widgets_to_delete = []
            for widget in page.widgets():
                field_name = widget.field_name
                # 3. Handle Signatures with transparent background
                if field_name == 'Text35' and 'patient' in signatures:
                    sig_path = os.path.join(base_dir, '..', 'storage', 'signatures', signatures['patient']['image_path'])
                    if os.path.exists(sig_path):
                        rect = widget.rect
                        sig_bytes = get_transparent_signature_bytes(sig_path)
                        page.insert_image(rect, stream=sig_bytes)
                        widgets_to_delete.append(widget)
                        continue
                        
                if field_name == 'Text34' and 'practitioner' in signatures:
                    sig_path = os.path.join(base_dir, '..', 'storage', 'signatures', signatures['practitioner']['image_path'])
                    if os.path.exists(sig_path):
                        rect = widget.rect
                        sig_bytes = get_transparent_signature_bytes(sig_path)
                        page.insert_image(rect, stream=sig_bytes)
                        widgets_to_delete.append(widget)
                        continue
                
                # 3. Handle text fields without background/border
                if field_name in form_data:
                    doc.xref_set_key(widget.xref, 'MK', '<< /R 0 >>')
                    val = str(form_data[field_name] or '')
                    widget.field_value = val
                    widget.text_font = 'china-s'
                    widget.text_fontsize = 8.5
                    widget.border_width = 0
                    widget.border_color = None
                    widget.fill_color = None
                    widget.update()
                    doc.xref_set_key(widget.xref, 'F', '4')
                        
            for w in widgets_to_delete:
                page.delete_widget(w)
            
            # No manual strikethrough needed since gender is stored in a text field

                    
        # Construct filename
        safe_name = "".join([c if c.isalnum() else "_" for c in patient_name])
        timestamp = datetime.datetime.now().strftime("%Y%m%d_%H%M%S")
        filename = f"TCM_Consent_{safe_name}_{timestamp}.pdf"
        output_path = os.path.join(base_dir, '..', 'storage', 'pdf', filename)
        
        # Generate appearances natively with PyMuPDF instead of relying on viewers
        doc.save(output_path)
        doc.close()
        
        print(output_path)
        
    except Exception as e:
        print(f"ERROR: Failed to process PDF: {str(e)}", file=sys.stderr)
        sys.exit(1)

if __name__ == '__main__':
    if len(sys.argv) < 2:
        print("ERROR: Missing token.", file=sys.stderr)
        sys.exit(1)
    
    token = sys.argv[1]
    generate_pdf(token)
