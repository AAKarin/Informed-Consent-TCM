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
        datas = list(img.getdata())
        newData = []
        for item in datas:
            # If transparent or near-white, make transparent
            if item[3] < 10 or (item[0] > 220 and item[1] > 220 and item[2] > 220):
                newData.append((255, 255, 255, 0))
            else:
                newData.append(item)
        img.putdata(newData)
        
        # Crop empty whitespace around signature strokes
        bbox = img.getbbox()
        if bbox:
            w, h = img.size
            crop_box = (max(0, bbox[0]-6), max(0, bbox[1]-6), min(w, bbox[2]+6), min(h, bbox[3]+6))
            img = img.crop(crop_box)
            
        buf = io.BytesIO()
        img.save(buf, format="PNG")
        return buf.getvalue()
    except Exception:
        try:
            with open(image_path, "rb") as f:
                return f.read()
        except Exception:
            return None

def generate_pdf(token):
    base_dir = os.path.dirname(os.path.abspath(__file__))
    db_path = os.path.join(base_dir, '..', 'storage', 'consent.db')
    
    template_candidates = [
        os.path.join(base_dir, '..', 'public', 'template', 'sctcm-treatment-template-read-only.pdf'),
        os.path.join(base_dir, '..', 'public', 'template', 'sctcm-treatment.pdf'),
        os.path.join(base_dir, '..', 'public', 'template', 'INFORMED-CONSENT.pdf')
    ]
    template_path = None
    for cand in template_candidates:
        if os.path.exists(cand):
            template_path = cand
            break
            
    if not template_path:
        print("ERROR: Template not found in public/template/. Please upload the PDF template.", file=sys.stderr)
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
        
    # Prepare patient details
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
        'patient_name': patient_name,
        'patient_sex': gender_val,
        'patient_nric': patient['nric'],
        'patient_dob': patient['date_of_birth'],
        'patient_contact': patient['contact_number'],
        'patient_address': patient['address'],
        'patient_postal': patient['postal_code']
    }
    
    # Guardian / Representative
    if guardian and guardian['name']:
        rep_text = guardian['name']
        if guardian['relationship']:
            rep_text += f" ({guardian['relationship']})"
        form_data['patient_representative'] = rep_text
        
    # 2. Medical answers mapping for 14 questions (a to n)
    med_mapping = {
        'heart_disease': 'medical_history_q1',
        'heart': 'medical_history_q1',
        'pacemaker': 'medical_history_q2',
        'diabetes': 'medical_history_q3',
        'high_blood_pressure': 'medical_history_q4',
        'hbp': 'medical_history_q4',
        'high_cholesterol': 'medical_history_q5',
        'cholesterol': 'medical_history_q5',
        'cancer': 'medical_history_q6',
        'sensitive_skin': 'medical_history_q7',
        'skin': 'medical_history_q7',
        'allergies': 'medical_history_q8',
        'hiv_aids': 'medical_history_q9',
        'hiv': 'medical_history_q9',
        'seizures': 'medical_history_q10',
        'anti_coagulants': 'medical_history_q11',
        'anticoagulants': 'medical_history_q11',
        'operation': 'medical_history_q12',
        'abnormal_bleeding': 'medical_history_q13',
        'bleeding': 'medical_history_q13',
        'currently_pregnant': 'medical_history_q14',
        'pregnant': 'medical_history_q14'
    }

    for key, row in medical.items():
        ans = row['answer']
        # Handle "others" field
        if key == 'others':
            if row['specification']:
                form_data['other_conditions'] = row['specification']
            continue
            
        if key in med_mapping:
            field_id = med_mapping[key]
            # Bilingual Choice: Yes 有 / No 无 / Unsure 不确定
            if ans == 'Yes':
                form_data[field_id] = "Yes 有"
            elif ans == 'No':
                form_data[field_id] = "No 无"
            elif ans == 'Unsure':
                form_data[field_id] = "Unsure 不确定"
            else:
                form_data[field_id] = ans
                
            # Handle specifications
            spec = row['specification']
            if spec:
                if key == 'cancer':
                    form_data['cacenr_specify'] = spec
                    form_data['cancer_specify'] = spec
                elif key == 'allergies':
                    form_data['allergies_specify'] = spec
                elif key == 'operation':
                    form_data['operation_specify'] = spec

    # 3. Dates & Practitioner
    if 'patient' in signatures:
        form_data['patient_signature_date'] = signatures['patient']['signed_at'].split(' ')[0]
        
    if 'practitioner' in signatures:
        form_data['physician_signature_date'] = signatures['practitioner']['signed_at'].split(' ')[0]
        form_data['Physician_signature_date'] = signatures['practitioner']['signed_at'].split(' ')[0]
        if signatures['practitioner']['signed_by']:
            form_data['physician_name'] = signatures['practitioner']['signed_by']

    # Normalize lookup dictionary for case-insensitivity
    form_data_lower = {k.lower(): v for k, v in form_data.items()}

    try:
        doc = pymupdf.open(template_path)
        
        for i, page in enumerate(doc):
            widgets_to_delete = []
            for widget in page.widgets():
                field_name = widget.field_name or ''
                field_name_clean = field_name.strip()
                field_name_lower = field_name_clean.lower()
                
                # Delete any signature widgets to avoid field boundaries or SIGN badges
                if field_name_lower in ['patient_signature', 'physician_signature', 'practitioner_signature', 'text34', 'text35', 'patient_signature_area', 'practitioner_signature_area']:
                    widgets_to_delete.append(widget)
                    continue
                
                # Text fields handling
                val = None
                if field_name_clean in form_data:
                    val = form_data[field_name_clean]
                elif field_name_lower in form_data_lower:
                    val = form_data_lower[field_name_lower]
                    
                if val is not None:
                    # Remove border/background fill to prevent covering template lines
                    doc.xref_set_key(widget.xref, 'MK', '<< /R 0 >>')
                    widget.field_value = str(val or '')
                    widget.text_font = 'china-s'
                    
                    # Font size set to 12pt & Read-Only
                    widget.text_fontsize = 12.0
                    widget.field_flags |= pymupdf.PDF_FIELD_IS_READ_ONLY
                    widget.border_width = 0
                    widget.border_color = None
                    widget.fill_color = None
                    widget.update()
                    # Make widget visible and printable
                    doc.xref_set_key(widget.xref, 'F', '4')
                        
            for w in widgets_to_delete:
                page.delete_widget(w)

        # 1. Flatten and bake all form fields and template annotations permanently first
        if hasattr(doc, 'bake'):
            doc.bake()

        # 2. Place high-quality transparent signature images directly ON TOP of the baked page
        if len(doc) >= 2:
            page2 = doc[1]
            if 'patient' in signatures:
                sig_path = os.path.join(base_dir, '..', 'storage', 'signatures', signatures['patient']['image_path'])
                if os.path.exists(sig_path):
                    sig_bytes = get_transparent_signature_bytes(sig_path)
                    if sig_bytes:
                        try:
                            # Patient / Representative signature box above the line (y ≈ 585.3)
                            page2.insert_image(
                                pymupdf.Rect(50.0, 540.0, 230.0, 583.0),
                                stream=sig_bytes,
                                keep_proportion=True,
                                overlay=True
                            )
                        except Exception as e:
                            print(f"Warning inserting patient signature: {e}", file=sys.stderr)

            if 'practitioner' in signatures:
                sig_path = os.path.join(base_dir, '..', 'storage', 'signatures', signatures['practitioner']['image_path'])
                if os.path.exists(sig_path):
                    sig_bytes = get_transparent_signature_bytes(sig_path)
                    if sig_bytes:
                        try:
                            # Physician signature box above the line (y ≈ 695.8)
                            page2.insert_image(
                                pymupdf.Rect(50.0, 650.0, 230.0, 693.0),
                                stream=sig_bytes,
                                keep_proportion=True,
                                overlay=True
                            )
                        except Exception as e:
                            print(f"Warning inserting practitioner signature: {e}", file=sys.stderr)
            
        # Construct filename
        safe_name = "".join([c if c.isalnum() else "_" for c in patient_name])
        timestamp = datetime.datetime.now().strftime("%Y%m%d_%H%M%S")
        filename = f"TCM_Consent_{safe_name}_{timestamp}.pdf"
        output_path = os.path.join(base_dir, '..', 'storage', 'pdf', filename)
        
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
