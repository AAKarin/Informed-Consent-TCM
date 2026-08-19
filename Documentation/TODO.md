# TCM Digital Informed Consent App - TODO & Action Checklist

## 🎯 Sprint 2: Usability & Client Feedback (Aktif)
- [ ] **[TSK-01]** Penyesuaian Tata Letak Form Pasien:
  - Buat kolom `Address` (Alamat) menjadi 1 baris penuh (*full-width*).
  - Satukan kolom `Postal Code` dan `Contact No` dalam 1 baris seimbang.
- [ ] **[TSK-02]** Desain Warna Ramah Lansia & Aksesibilitas (Elderly-Friendly UI):
  - Ganti warna background putih silau dengan palet warna klinik yang lembut, hangat, dan ber-kontras tinggi.
  - Pertegas border input dan kartu formulir.
- [ ] **[TSK-03]** Default Kuesioner Medis ke "Unsure / 不确定":
  - Set seluruh 14 pertanyaan riwayat medis default aktif pada opsi *Unsure*.
- [ ] **[TSK-04]** Kompak Kuesioner Medis (Inline Text):
  - Teks kondisi Inggris & Mandarin dibuat sebaris (`a) Heart diseases 心脏病`) untuk mengurangi *scrolling*.
- [ ] **[TSK-05]** Tombol Aksi Cepat (Quick Batch Action Buttons):
  - Tambahkan tombol `[ Set All No ]` dan `[ Set All Unsure ]`.
  - Tambahkan tombol `[ Clear Patient Signature ]` dan `[ Clear Doctor Signature ]`.
- [ ] **[TSK-06]** Pengujian & Validasi:
  - Uji alur pengisian form, signature canvas, dan unduh PDF resmi.

---

## 📌 Sprint 1: Fondasi Inti (Selesai ✅)
- [x] Pembangunan Form Digital Responsive (English & Chinese)
- [x] Dual Digital Signature Pad (Pasien/Wali & Praktisi TCM)
- [x] Database Lokal SQLite & Audit Logging
- [x] Ekspor Dokumen Resmi PDF Berbasis AcroForm Template
- [x] Fitur PWA (Progressive Web App) dengan Service Worker