import pymupdf
import shutil

def fix_pdf(input_path, output_path):
    doc = pymupdf.open(input_path)
    
    # Define mapping order
    text_fields_p1 = [
        'patient_name', 'patient_nric', 'patient_address', 'patient_postal_code',
        'patient_contact', 'patient_dob_gender', 'guardian_name', 'guardian_nric',
        'guardian_relationship', 'medical_others_p1_1', 'medical_others_p1_2'
    ]
    text_fields_p2 = [
        'medical_others_p2_1', 'medical_others', 'patient_signature_area',
        'patient_date', 'practitioner_signature_area', 'practitioner_date'
    ]
    
    conditions = [
        'heart', 'pacemaker', 'diabetes', 'hbp', 'cholesterol', 'cancer',
        'skin', 'allergies', 'hiv', 'seizures', 'anticoagulants', 
        'operation', 'bleeding', 'pregnant'
    ]
    
    # Process Page 1
    page1 = doc[0]
    texts1 = [w for w in page1.widgets() if w.field_type_string == 'Text']
    texts1.sort(key=lambda w: (round(w.rect.y0/15)*15, w.rect.x0))
    for i, w in enumerate(texts1):
        if i < len(text_fields_p1):
            w.field_name = text_fields_p1[i]
            w.update()
            
    checks1 = [w for w in page1.widgets() if w.field_type_string == 'CheckBox']
    checks1.sort(key=lambda w: (round(w.rect.y0/15)*15, w.rect.x0))
    cond_idx = 0
    for i in range(0, len(checks1), 3):
        group = checks1[i:i+3]
        if cond_idx < len(conditions):
            cond = conditions[cond_idx]
            if len(group) == 3:
                group[0].field_name = f'medical_{cond}_yes'
                group[0].update()
                group[1].field_name = f'medical_{cond}_no'
                group[1].update()
                group[2].field_name = f'medical_{cond}_unsure'
                group[2].update()
            cond_idx += 1
            
    # Process Page 2
    page2 = doc[1]
    texts2 = [w for w in page2.widgets() if w.field_type_string == 'Text']
    texts2.sort(key=lambda w: (round(w.rect.y0/15)*15, w.rect.x0))
    for i, w in enumerate(texts2):
        if i < len(text_fields_p2):
            w.field_name = text_fields_p2[i]
            w.update()
            
    checks2 = [w for w in page2.widgets() if w.field_type_string == 'CheckBox']
    checks2.sort(key=lambda w: (round(w.rect.y0/15)*15, w.rect.x0))
    for i in range(0, len(checks2), 3):
        group = checks2[i:i+3]
        if cond_idx < len(conditions):
            cond = conditions[cond_idx]
            if len(group) == 3:
                group[0].field_name = f'medical_{cond}_yes'
                group[0].update()
                group[1].field_name = f'medical_{cond}_no'
                group[1].update()
                group[2].field_name = f'medical_{cond}_unsure'
                group[2].update()
            cond_idx += 1

    doc.save(output_path)
    print(f"Fixed PDF saved to {output_path}")

if __name__ == '__main__':
    in_path = 'c:/Users/hawki/Documents/KerjaPraktik/InformedConsentTCM/public/template/INFORMED-CONSENT.pdf'
    out_path = 'c:/Users/hawki/Documents/KerjaPraktik/InformedConsentTCM/storage/pdf_templates/INFORMED-CONSENT-FILLABLE.pdf'
    fix_pdf(in_path, out_path)
