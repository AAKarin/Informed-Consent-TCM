import pymupdf
doc = pymupdf.open('c:/Users/hawki/Documents/KerjaPraktik/InformedConsentTCM/storage/pdf/TCM_Consent_John_Doe_20260814_103954.pdf')
for page in doc:
    for w in page.widgets():
        print(w.field_name, "->", w.field_value)
