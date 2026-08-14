import sys
import os
import sqlite3
import datetime
import pymupdf  # PyMuPDF

def generate_pdf(token):
    base_dir = os.path.dirname(os.path.abspath(__file__))
    db_path = os.path.join(base_dir, '..', 'storage', 'consent.db')
    template_path = os.path.join(base_dir, '..', 'public', 'template', 'INFORMED-CONSENT.pdf')
    
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
    gender_str = 'Male / 男' if patient['gender'] == 'M' else 'Female / 女'
    address_str = patient['address']
        
    form_data = {
        'Text1': patient_name,
        'Text2': patient['nric'],
        'Text3': address_str,
        'Text4': patient['postal_code'] if patient['postal_code'] else '',
        'Text5': patient['contact_number'],
        'Text6': patient['date_of_birth']
    }
    
    # Store gender so we can draw it manually later
    patient_gender_val = patient['gender']
    
    if guardian:
        form_data['Text7'] = guardian['name']
        form_data['Text8'] = guardian['nric']
        form_data['Text9'] = guardian['relationship']
        
    # Map medical answers
    # Database keys to PDF Checkbox names: (Yes, No, Unsure)
    med_mapping = {
        'heart': ('Button15', 'Button16', 'Button17'),
        'pacemaker': ('Button18', 'Button46', 'Button47'),
        'diabetes': ('Button19', 'Button45', 'Button48'),
        'hbp': ('Button20', 'Button44', 'Button49'),
        'cholesterol': ('Button21', 'Button43', 'Button50'),
        'cancer': ('Button22', 'Button42', 'Button51'),
        'skin': ('Button23', 'Button41', 'Button52'),
        'allergies': ('Button24', 'Button40', 'Button53'),
        'hiv': ('Button25', 'Button39', 'Button54'),
        'seizures': ('Button26', 'Button38', 'Button55'),
        'anticoagulants': ('Button27', 'Button37', 'Button56'),
        'operation': ('Button28', 'Button35', 'Button34'),
        'bleeding': ('Button29', 'Button36', 'Button33'),
        'pregnant': ('Button30', 'Button31', 'Button32')
    }
    
    spec_mapping = {
        'cancer': 'Text10',
        'allergies': 'Text11',
        'operation': 'Text12'
    }

    for key, row in medical.items():
        if key == 'others':
            if row['specification']:
                form_data['Text59'] = row['specification']
            continue
            
        if key in med_mapping:
            ans = row['answer']
            btn_yes, btn_no, btn_unsure = med_mapping[key]
            
            if ans == 'Yes':
                form_data[btn_yes] = True
            elif ans == 'No':
                form_data[btn_no] = True
            elif ans == 'Unsure':
                form_data[btn_unsure] = True
                
            # Handle specification
            if ans == 'Yes' and key in spec_mapping and row['specification']:
                form_data[spec_mapping[key]] = row['specification']

    # Dates
    if 'patient' in signatures:
        form_data['Text13'] = signatures['patient']['signed_at'].split(' ')[0]
    if 'practitioner' in signatures:
        form_data['Text14'] = signatures['practitioner']['signed_at'].split(' ')[0]

    try:
        doc = pymupdf.open(template_path)
        
        for i, page in enumerate(doc):
            widgets_to_delete = []
            for widget in page.widgets():
                field_name = widget.field_name
                # Handle Signatures
                if field_name == 'Text57' and 'patient' in signatures:
                    sig_path = os.path.join(base_dir, '..', 'storage', 'signatures', signatures['patient']['image_path'])
                    if os.path.exists(sig_path):
                        rect = widget.rect
                        page.insert_image(rect, filename=sig_path)
                        widgets_to_delete.append(widget)
                        continue
                        
                if field_name == 'Text58' and 'practitioner' in signatures:
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
