# Sprint Backlog - TCM Digital Informed Consent App
**Sprint:** Sprint 2 (Client Feedback & Usability Refinements)  
**Status:** In Progress  
**Tanggal:** 19 Agustus 2026  
**Target:** Meningkatkan kegunaan (*usability*), keterbacaan untuk pasien lansia, dan efisiensi pengisian form berdasarkan *feedback* langsung dari klien/staf klinik.

---

## 📋 Daftar Item Sprint Backlog (Product Backlog Items)

### 1. [US-01] Penyesuaian Tata Letak Alamat & Kontak (Particulars Section)
* **Deskripsi:** Mengubah tata letak kolom *Address*, *Postal Code*, dan *Contact Number* agar lebih proporsional saat diisi di perangkat tablet/layar klinik.
* **Kebutuhan Teknis:**
  - Kolom **Address** menggunakan elemen `<textarea>` (bukan input text 1 baris sempit) dan menempati 1 baris penuh (*full width*), sehingga nyaman saat pasien memasukkan alamat lengkap (nama blok, gedung, lantai, dan nomor unit).
  - Kolom **Postal Code** dan **Contact Number** disatukan dalam satu baris berdampingan (*inline half-row*).
* **Kriteria Penerimaan (Acceptance Criteria):**
  - [x] Alamat pasien menggunakan textarea responsif dengan ruang input luas dan dapat di-resize.
  - [x] Kode pos dan nomor kontak berada di satu baris yang rapi di layar desktop & tablet.
  - [x] Responsif pada layar HP (tetap rapi saat mengecil).

---

### 2. [US-02] Peningkatan Skema Warna & Aksesibilitas Lansia (Elderly-Friendly UI)
* **Deskripsi:** Mengganti latar belakang putih polos (*pure white glare*) dengan warna klinik yang lembut, hangat, dan memiliki kontras tinggi agar nyaman dan mudah dibaca oleh pasien lansia.
* **Kebutuhan Teknis:**
  - Background luar menggunakan warna *warm soft clinic neutral* (misal: `#edf2f7` / `#f0f4f8` / aksen sage-teal lembut).
  - Kartu formulir menggunakan background putih bersih dengan bayangan lembut (*soft card shadow*) dan border pemisah yang tegas.
  - Teks utama memiliki kontras tinggi (*high contrast ratio*) sesuai standar aksesibilitas WCAG (minimal 4.5:1).
  - Ukuran font dan ruang ketuk (*tap targets*) pada radio button diperbesar agar mudah disentuh oleh jari lansia di iPad/tablet.
* **Kriteria Penerimaan (Acceptance Criteria):**
  - [ ] Tidak ada efek silau dari layar putih polos.
  - [ ] Garis batas antar bagian form terlihat jelas dan terstruktur.
  - [ ] Teks Mandarin dan Inggris terbaca dengan sangat kontras dan jelas.

---

### 3. [US-03] Nilai Default Kuesioner Medis ke "Unsure / 不确定"
* **Deskripsi:** Mengatur nilai awal (*default value*) pada seluruh 14 pertanyaan riwayat medis agar langsung terpilih pada opsi **"Unsure / 不确定"** saat formulir pertama kali dibuka.
* **Kebutuhan Teknis:**
  - Inisialisasi atribut `checked` pada elemen input radio `Unsure` untuk setiap pertanyaan `history[heart]`, `history[diabetes]`, dst.
  - Validasi JavaScript memastikan opsi default ini terbaca dengan benar saat form dikirimkan (*submit*).
* **Kriteria Penerimaan (Acceptance Criteria):**
  - [ ] Saat halaman dimuat, seluruh radio button di kolom *Unsure* otomatis aktif tercentang.
  - [ ] Pasien/staf hanya perlu mengubah pertanyaan yang mereka yakin "Yes" atau "No", sehingga mempercepat proses *check-in*.

---

### 4. [US-04] Tata Letak Sebaris (Inline Text) Kuesioner Medis & Bahasa Murni Bilingual
* **Deskripsi:** Merapatkan tampilan teks bahasa Inggris dan Mandarin pada nama kondisi penyakit agar berada dalam satu baris (*inline*), serta menghapus opsi Bahasa Indonesia agar murni dwi-bahasa (English & Chinese / 英文与中文) sesuai operasional klinik Singapura.
* **Kebutuhan Teknis:**
  - Teks kondisi ditampilkan sebaris (contoh: `a) Heart diseases 心脏病`).
  - Menghapus seluruh elemen dan opsi bahasa Indonesia dari antarmuka web, kamus JS, dan struktur form.
  - Mengurangi padding vertikal tabel tanpa mengorbankan keterbacaan.
* **Kriteria Penerimaan (Acceptance Criteria):**
  - [x] Kuesioner medis tampil lebih ringkas dan padat.
  - [x] Hanya tersedia opsi Bahasa Inggris, Mandarin, dan Campuran (Bilingual).
  - [x] Pengguna tidak perlu melakukan *scroll* yang terlalu panjang di iPad/HP.

---

### 5. [US-05] Tombol Pintas Aksi Cepat (Quick Action Buttons & Signature Clear)
* **Deskripsi:** Menyediakan tombol aksi cepat (*one-click action buttons*) untuk memudahkan staf/pasien memilih jawaban secara massal dan mengulang tanda tangan.
* **Kebutuhan Teknis:**
  - Tombol **`[ Set All to "No" / 全部选"没有" ]`**: Menandai seluruh 14 pertanyaan menjadi "No".
  - Tombol **`[ Set All to "Unsure" / 全部选"不确定" ]`**: Mengembalikan seluruh 14 pertanyaan menjadi "Unsure".
  - Tombol **`[ Clear Patient Signature ]`**: Menghapus kanvas tanda tangan pasien.
  - Tombol **`[ Clear Doctor Signature ]`**: Menghapus kanvas tanda tangan dokter/praktisi.
* **Kriteria Penerimaan (Acceptance Criteria):**
  - [ ] Klik tombol *Set All No* otomatis mengubah seluruh pilihan menjadi "No".
  - [ ] Klik tombol *Set All Unsure* otomatis mengubah seluruh pilihan menjadi "Unsure".
  - [ ] Tombol *Clear* tanda tangan bekerja secara independen untuk masing-masing kanvas.

---

## 🎯 Task Breakdown & Status

| Task ID | Deskripsi Teknis | File Terkait | Estimasi | Status |
| :--- | :--- | :--- | :---: | :---: |
| **TSK-01** | Update HTML form layout (Address full-width, Postal + Contact inline) | `public/index.php` | 30m | ⏳ To Do |
| **TSK-02** | Perbarui CSS untuk tema warna ramah lansia, kontras tinggi & compact layout | `public/css/style.css` | 45m | ⏳ To Do |
| **TSK-03** | Set default radio button checked = 'Unsure' pada 14 pertanyaan medis | `public/index.php` | 20m | ⏳ To Do |
| **TSK-04** | Tambahkan markup tombol aksi cepat (Set All No, Set All Unsure, Clear Buttons) | `public/index.php` | 30m | ⏳ To Do |
| **TSK-05** | Implementasikan event handler JS untuk tombol aksi cepat & clear pad | `public/js/app.js` | 30m | ⏳ To Do |
| **TSK-06** | Uji coba pengisian form, signature, submit data, dan hasil ekspor PDF | Browser & Server | 30m | ⏳ To Do |
