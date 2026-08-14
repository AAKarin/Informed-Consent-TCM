import sys
import os
import sqlite3
import datetime
import pymupdf  # PyMuPDF

def generate_pdf(token):
    base_dir = os.path.dirname(os.path.abspath(__file__))
    db_path = os.path.join(base_dir, '..', 'storage', 'consent.db')
    template_path = os.path.join(base_dir, '..', 'storage', 'pdf_templates', 'INFORMED-CONSENT-FILLABLE.pdf')
    
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
        medical = {row['condition_key']: row for row in medical_rows}
        
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
    gender_str = 'Male / 男' if patient['gender'] == 'M' else 'Female / 女'
    address_str = patient['address']
        
    form_data = {
        'patient_name': patient_name,
        'patient_nric': patient['nric'],
        'patient_address': address_str,
        'patient_postal_code': patient['postal_code'] if patient['postal_code'] else '',
        'patient_contact': patient['contact_number'],
        'patient_dob_gender': patient['date_of_birth']
    }
    
    # Store gender so we can draw it manually later
    patient_gender_val = patient['gender']
    
    if guardian:
        form_data['guardian_name'] = guardian['name']
        form_data['guardian_nric'] = guardian['nric']
        form_data['guardian_relationship'] = guardian['relationship']
        
    # Map medical answers
    # Convert 'Yes'/'No'/'Unsure' to checkbox logic
    for key, row in medical.items():
        ans = row['answer']
        spec = row['specification']
        
        # Check the correct box by setting it to True (which turns it ON in PyMuPDF)
        if ans == 'Yes':
            form_data[f'medical_{key}_yes'] = True
        elif ans == 'No':
            form_data[f'medical_{key}_no'] = True
        elif ans == 'Unsure':
            form_data[f'medical_{key}_unsure'] = True
            
        # If there's a specification and it's 'Yes', we might want to put it in a general 'others' or specific text field if one exists.
        # We mapped 'medical_others_p1_1', etc. but we'll just put all specs into 'medical_others' for now 
        # or append them to a list.
        if ans == 'Yes' and spec:
            if 'medical_others' not in form_data:
                form_data['medical_others'] = ""
            form_data['medical_others'] += f"{key.capitalize()}: {spec}. "
        
    if 'others' in medical and medical['others']['specification']:
        if 'medical_others' not in form_data:
            form_data['medical_others'] = ""
        form_data['medical_others'] += medical['others']['specification']

    # Dates
    if 'patient' in signatures:
        form_data['patient_date'] = signatures['patient']['signed_at'].split(' ')[0]
    if 'practitioner' in signatures:
        form_data['practitioner_date'] = signatures['practitioner']['signed_at'].split(' ')[0]

    try:
        doc = pymupdf.open(template_path)
        
        for i, page in enumerate(doc):
            widgets_to_delete = []
            for widget in page.widgets():
                field_name = widget.field_name
                # Handle Signatures
                if field_name == 'patient_signature_area' and 'patient' in signatures:
                    sig_path = os.path.join(base_dir, '..', 'storage', 'signatures', signatures['patient']['image_path'])
                    if os.path.exists(sig_path):
                        rect = widget.rect
                        page.insert_image(rect, filename=sig_path)
                        widgets_to_delete.append(widget)
                        continue
                        
                if field_name == 'practitioner_signature_area' and 'practitioner' in signatures:
                    sig_path = os.path.join(base_dir, '..', 'storage', 'signatures', signatures['practitioner']['image_path'])
                    if os.path.exists(sig_path):
                        rect = widget.rect
                        page.insert_image(rect, filename=sig_path)
                        widgets_to_delete.append(widget)
                        continue
                
                # Handle text fields and checkboxes
                if field_name in form_data:
                    if widget.field_type_string == 'CheckBox':
                        if form_data[field_name] is True:
                            page.insert_textbox(widget.rect, "X", fontsize=12, fontname="helv", align=1)
                    else:
                        val = str(form_data[field_name] or '')
                        # small padding for y to look natural
                        rect = widget.rect
                        rect.y0 += 2 
                        page.insert_textbox(rect, val, fontsize=10, fontname="helv", align=0)
                        
                widgets_to_delete.append(widget)
                
            for w in widgets_to_delete:
                page.delete_widget(w)
            
            # Draw gender strikethrough manually on Page 1
            if i == 0:
                if patient_gender_val in ['M', 'Male']:
                    # Strikethrough Female 女
                    page.draw_line(pymupdf.Point(166, 251.5), pymupdf.Point(210, 251.5), color=(0, 0, 0), width=1.5)
                elif patient_gender_val in ['F', 'Female']:
                    # Strikethrough Male 男
                    page.draw_line(pymupdf.Point(130, 251.5), pymupdf.Point(166, 251.5), color=(0, 0, 0), width=1.5)
                    
        # Construct filename
        safe_name = "".join([c if c.isalnum() else "_" for c in patient_name])
        timestamp = datetime.datetime.now().strftime("%Y%m%d_%H%M%S")
        filename = f"TCM_Consent_{safe_name}_{timestamp}.pdf"
        output_path = os.path.join(base_dir, '..', 'storage', 'pdf', filename)
        
        # Ensure appearance streams are used by viewers instead of relying on the viewer to generate them
        doc.need_appearances(False)
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
