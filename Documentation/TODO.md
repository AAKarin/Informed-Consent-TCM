# TCM Digital Informed Consent App - TODO & Action Checklist

## 🎯 Sprint 2: Usability & Client Feedback (Selesai ✅ - v1.3.0)
- [x] **[TSK-01]** Penyesuaian Tata Letak Form Pasien:
  - Buat kolom `Address` (Alamat) menjadi `<textarea>` 1 baris penuh (*full-width*).
  - Satukan kolom `Postal Code` dan `Contact No` dalam 1 baris seimbang.
- [x] **[TSK-02]** Desain Warna Ramah Lansia & Aksesibilitas (Elderly-Friendly UI):
  - Ganti warna background putih silau dengan palet warna klinik yang lembut, hangat, dan ber-kontras tinggi.
  - Pertegas border input dan kartu formulir.
- [x] **[TSK-03]** Default Kuesioner Medis ke "Unsure / 不确定":
  - Set seluruh 14 pertanyaan riwayat medis default aktif pada opsi *Unsure*.
- [x] **[TSK-04]** Kompak Kuesioner Medis (Inline Text) & Hapus Bahasa Indonesia:
  - Teks kondisi Inggris & Mandarin dibuat sebaris (`a) Heart diseases 心脏病`) untuk mengurangi *scrolling*.
  - Hapus bahasa Indonesia agar murni dwibahasa (English & Chinese / 英文与中文).
- [x] **[TSK-05]** Tombol Aksi Cepat (Quick Batch Action Buttons):
  - Tambahkan tombol `[ Set All No ]` dan `[ Set All Unsure ]`.
  - Tambahkan tombol `[ Clear Patient Signature ]` dan `[ Clear Doctor Signature ]`.
- [x] **[TSK-06]** Ekspor PDF AcroForm Presisi 100% Sesuai Template Asli (12pt Font):
  - Mengisi langsung widget AcroForm bawaan `sctcm-treatment-template-read-only.pdf` tanpa merubah template asli.
  - Penempatan tanda tangan digital transparan di atas garis template.

---

## 📌 Sprint 1: Fondasi Inti (Selesai ✅)
- [x] Pembangunan Form Digital Responsive (English & Chinese)
- [x] Dual Digital Signature Pad (Pasien/Wali & Praktisi TCM)
- [x] Database Lokal SQLite & Audit Logging
- [x] Ekspor Dokumen Resmi PDF Berbasis AcroForm Template
- [x] Fitur PWA (Progressive Web App) dengan Service Worker