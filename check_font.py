import pymupdf
doc = pymupdf.open('c:/Users/hawki/Documents/KerjaPraktik/InformedConsentTCM/storage/pdf_templates/INFORMED-CONSENT-FILLABLE.pdf')
page = doc[0]
for w in page.widgets():
    if w.field_name == 'patient_name':
        print(f"Font: {w.text_font}, Size: {w.text_fontsize}, Color: {w.text_color}")
