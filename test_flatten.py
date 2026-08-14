import pymupdf
doc = pymupdf.open('c:/Users/hawki/Documents/KerjaPraktik/InformedConsentTCM/storage/pdf_templates/INFORMED-CONSENT-FILLABLE.pdf')
page = doc[0]
for w in page.widgets():
    if w.field_name == 'patient_name':
        rect = w.rect
        page.insert_textbox(rect, "Test Patient Flattened", fontsize=10, fontname="helv", color=(0,0,0))
        page.delete_widget(w)
        break
doc.save('c:/Users/hawki/Documents/KerjaPraktik/InformedConsentTCM/storage/pdf/test_flatten.pdf')
print("Saved test_flatten.pdf")
