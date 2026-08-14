import pdfplumber
import sys

pdf_path = "public/template/INFORMED-CONSENT.pdf"
try:
    with pdfplumber.open(pdf_path) as pdf:
        for i, page in enumerate(pdf.pages):
            print(f"--- PAGE {i+1} ---")
            words = page.extract_words()
            for word in words:
                if any(x in word['text'] for x in ['Name', 'NRIC', 'Date', 'Gender', 'Address', 'Contact', 'Guardian', 'Yes', 'No', 'Unsure', 'Signature']):
                    print(f"{word['text']}: x={word['x0']:.2f}, y={word['top']:.2f}")
except Exception as e:
    print("Error:", e)
