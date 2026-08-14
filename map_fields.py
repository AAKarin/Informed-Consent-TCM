import pymupdf

def map_fields(path):
    doc = pymupdf.open(path)
    for p_idx, page in enumerate(doc):
        widgets = []
        for widget in page.widgets():
            rect = widget.rect
            # Store (y, x, name, type)
            widgets.append((rect.y0, rect.x0, widget.field_name, widget.field_type_string))
        
        # Sort by Y (top to bottom), then by X (left to right). 
        # Give a small tolerance to Y (e.g., round to nearest 10) to group items on the same line.
        widgets.sort(key=lambda w: (round(w[0]/10)*10, w[1]))
        
        print(f"--- PAGE {p_idx+1} ---")
        for w in widgets:
            print(f"Y: {w[0]:.1f}, X: {w[1]:.1f} | Name: {w[2]} | Type: {w[3]}")

if __name__ == '__main__':
    map_fields('c:/Users/hawki/Documents/KerjaPraktik/InformedConsentTCM/public/template/INFORMED-CONSENT.pdf')
