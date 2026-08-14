import pymupdf
doc = pymupdf.open('c:/Users/hawki/Documents/KerjaPraktik/InformedConsentTCM/public/template/INFORMED-CONSENT.pdf')
for i, page in enumerate(doc):
    widgets = []
    for w in page.widgets():
        widgets.append((w.rect.y0, w.rect.x0, w.field_name, w.field_type_string))
    widgets.sort(key=lambda x: (round(x[0]/15)*15, x[1]))
    print(f"--- PAGE {i+1} ---")
    for w in widgets:
        print(f"Y: {w[0]:.1f}, X: {w[1]:.1f} | Name: {w[2]} | Type: {w[3]}")
