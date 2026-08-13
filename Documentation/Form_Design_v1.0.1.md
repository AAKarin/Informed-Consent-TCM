# Desain Antarmuka TCM Consent Form v1.0.1

**Status Dokumen:** Final (v1.0.1)
**Tujuan:** Panduan tata letak (layout) dan desain UI agar penambahan fungsionalitas di masa depan tidak merusak atau merubah tampilan form persetujuan (Informed Consent) TCM.

---

## 1. Tata Letak Global (Global Layout)
- **Container Utama:** Form dibungkus dalam `.form-container` dengan lebar maksimum 850px agar nyaman dibaca di desktop maupun mobile.
- **Background & Font:** Background luar abu-abu muda (`#f9f9f9`), background form putih (`#fff`). Font utama menggunakan Arial, Helvetica, Microsoft JhengHei, Microsoft YaHei.
- **Responsivitas:** Pada layar di bawah 600px, form otomatis menyesuaikan menjadi satu kolom (stacked).

## 2. Struktur Header
Header berisi nama klinik dan judul dokumen (bilingual).
- Menggunakan class `.header` dengan border bawah (garis solid 2px).
- Penempatan teks rata tengah (center-aligned).

## 3. Komponen Form Dasar (Fieldsets)
Seluruh grup input dibungkus dalam tag `<fieldset>` tanpa border untuk menjaga semantik. Label kategori menggunakan tag `<legend>` dengan border bawah tipis.

### 3.1. Field Input Standar (Teks, Nomor, Tanggal)
- Menggunakan flexbox (`.form-group`) dengan label statis di sebelah kiri (max 200px) dan input box di kanan.
- Input box menggunakan padding 6px dan border tipis.
- *Aturan Desain:* Dilarang mengganti ukuran lebar label secara acak agar form tetap rata kiri.

### 3.2. Field Input Sebaris (Inline Inputs)
- Untuk "Address" dan "Postal Code" berada di satu baris yang sama (menggunakan class `.inline-inputs`).
- *Aturan Desain:* Proporsi flex (Address: 2, Postal: 1) tidak boleh dirubah agar rasio lebarnya tetap enak dipandang.

### 3.3. Input Radio Button (Gender)
- Dikelompokkan dengan `.radio-group`.
- *Aturan Desain:* Harus menggunakan display flex dan menempatkan opsi berjejer secara horizontal di layar besar.

## 4. Tabel Riwayat Medis (Medical History Table)
Tabel kuesioner medis memiliki desain khusus agar interaktif dan mudah dibaca:
- Menggunakan class `.medical-history-table`.
- **Kolom Kiri (Condition):** Lebar 45%, berisi kondisi medis beserta kolom input *Specify* (bila ada).
- **Kolom Kanan (Yes/No/Unsure):** Menggunakan rasio lebar yang seimbang dan rata tengah.
- **Responsivitas Mobile:** Pada mobile, tabel berubah menjadi block layout. Label (Yes/No/Unsure) akan diinjeksikan secara dinamis menggunakan pseudo-element CSS (`:before`). 
- *Aturan Desain:* Dilarang merubah struktur tag `<table>`, `<thead>`, dan `<tbody>` karena sangat bergantung pada CSS mobile responsive.

## 5. Area Tanda Tangan (Signature Pads)
Formulir menggunakan tata letak berbasis **Flexbox** (`.signature-flex`) agar responsif (stacking pada mobile) dan rapi di desktop.
- **Struktur Kolom:**
  - Kolom Kiri (`.sig-pad-col`): Memuat area canvas interaktif dengan tombol *Clear*, dan label teks identitas yang memiliki garis atas (`border-top`).
  - Kolom Kanan (`.sig-date-col`): Memuat input tanggal (date) dan label teks "Date / 日期" yang memiliki garis atas (`border-top`).
- **Pemisahan Teks Keterangan:** Teks keterangan wali untuk pasien di bawah umur 21 tahun ditempatkan di luar flex container utama (span full width) agar tidak menumpuk di area tanda tangan, dengan penekanan pada frasa spesifik umur (`<b>`).
- **Spesifikasi Canvas:** Harus menggunakan library `signature_pad` dan canvas memiliki inline script untuk resizing yang menyesuaikan resolusi pixel (terutama di layar retina/mobile).
- *Aturan Desain:* Dimensi area tanda tangan tidak boleh lebih kecil dari 120px tingginya. Dilarang merubah struktur flexbox (`.signature-flex`) yang membungkus elemen-elemen ini agar desain form pada layar mobile tetap berjalan mulus.

## 6. Tombol Aksi (Submit)
- Berada di dalam `.submit-container` (rata tengah).
- Tombol menggunakan class `.submit-btn` dengan warna latar biru (`#0056b3`) dan border radius 5px. Hover akan merubah warna menjadi biru gelap.

## 7. Aturan Modifikasi oleh Developer
1. **Dilarang menambah class eksternal** (seperti Bootstrap atau Tailwind) yang dapat berkonflik dengan CSS bawaan.
2. Saat mengikat event DOM di JavaScript (misal untuk validasi AJAX), gunakan **ID** (`id="..."`) dan jangan merubah class presentasi.
3. Struktur teks bilingual Inggris/Mandarin telah fix, jangan merubah urutannya.
