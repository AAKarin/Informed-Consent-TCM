import pymupdf

def test():
    doc = pymupdf.open('c:/Users/hawki/Documents/KerjaPraktik/InformedConsentTCM/public/template/INFORMED-CONSENT.pdf')
    page = doc[0]
    checkboxes = [w for w in page.widgets() if w.field_type_string == 'CheckBox']
    # Round Y to nearest 20 to group loosely aligned checkboxes
    checkboxes.sort(key=lambda w: (round(w.rect.y0/20)*20, w.rect.x0))
    for i in range(0, len(checkboxes), 3):
        group = checkboxes[i:i+3]
        print(f"Group {i//3}: {[w.field_name for w in group]}")

if __name__ == '__main__':
    test()
