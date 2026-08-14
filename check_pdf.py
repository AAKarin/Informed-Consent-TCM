import pymupdf

def check_pdf(path):
    print(f"Checking PDF: {path}")
    doc = pymupdf.open(path)
    count = 0
    for i, page in enumerate(doc):
        for widget in page.widgets():
            print(f"Page {i+1} Widget Name: '{widget.field_name}', Value: '{widget.field_value}', Label/Tooltip: '{widget.field_label}'")
            count += 1
            if count > 5:
                return
    if count == 0:
        print("No form fields (AcroForm widgets) found in this PDF!")

if __name__ == '__main__':
    check_pdf('c:/Users/hawki/Documents/KerjaPraktik/InformedConsentTCM/public/template/INFORMED-CONSENT.pdf')
