# Dokumen Analisis Kebutuhan
## Aplikasi Informed Consent Klinik TCM (Single Page, Native PHP)

**Sumber:** Sintesis dari 3 hasil analisis AI (Deepseek, Claude, GPT) terhadap dokumen *Informed Consent* bilingual (Inggris/Mandarin) klinik pengobatan tradisional Tiongkok (TCM), konteks Singapura (terdapat field NRIC/FIN).

---

## 1. Latar Belakang & Tujuan

Aplikasi yang akan dibangun bukan sekadar "form HTML + PHP + signature", melainkan secara fungsional setara dengan **sistem manajemen persetujuan digital (Digital Consent Management System)** sederhana — mencakup identitas pasien, kuesioner medis, klausul persetujuan, tanda tangan digital dua pihak, versioning, penyimpanan aman, dan bukti dokumen (PDF).

**Ketentuan arsitektur yang sudah disepakati di awal:**
- **"Satu halaman"** = single-page application (1 URL, transisi antar tahap via JavaScript tanpa reload) — **bukan** satu file `.php` tunggal.
- **Database** = SQLite (file lokal, tidak bergantung pada server database terpisah), dengan `journal_mode = WAL` untuk menghindari blocking read/write.
- **Stack**: Native PHP (tanpa framework besar), HTML, CSS, Vanilla JavaScript.

---

## 2. Gambaran Alur Aplikasi (User Flow)

```
Buka halaman
   ↓
Pilih bahasa (EN / ZH, terstruktur agar mudah tambah ID nanti)
   ↓
Isi data pasien
   ↓
Isi data guardian / next of kin (WAJIB jika usia pasien < 21 tahun)
   ↓
Isi kuesioner riwayat medis (14 item, Yes/No/Unsure + keterangan kondisional)
   ↓
Isi kolom informasi medis lain (opsional, free text)
   ↓
Baca klausul informed consent (7 paragraf, bilingual)
   ↓
Checklist persetujuan ("saya telah membaca & memahami")
   ↓
Tanda tangan digital PASIEN/WALI (+ timestamp)
   ↓
--- (opsional beda sesi/perangkat) ---
   ↓
Tanda tangan digital PRAKTISI TCM (+ timestamp)
   ↓
Review data
   ↓
Submit & validasi server
   ↓
Simpan ke SQLite
   ↓
Generate PDF (struktur, bukan screenshot)
   ↓
Notifikasi sukses + unduh PDF
```

Karena tanda tangan praktisi kemungkinan terjadi di **sesi/perangkat berbeda** dari pasien (pasien tanda tangan dulu, praktisi counter-sign belakangan), sistem butuh **mekanisme resume berbasis token unik**, bukan satu siklus submit tunggal.

---

## 3. Kebutuhan Fungsional

### 3.1 Multi-Bahasa (i18n)
- Minimal **English** dan **Chinese**, sesuai dokumen sumber; struktur dibuat agar bahasa lain (mis. Indonesia, Melayu, Tamil) mudah ditambahkan kemudian.
- Gunakan **file bahasa terpisah** (`lang/en.php`, `lang/zh.php`) berisi array asosiatif — bukan hardcode teks di HTML.
- **Pisahkan dua jenis teks**:
  - Teks UI (label, tombol) → `/lang/`
  - Isi klausul consent (paragraf hukum) → `/consent/` (perlu **versioning**, lihat §3.6)
- Bahasa yang dipilih pasien **ikut disimpan** di database, dan PDF akhir harus dibuat dalam bahasa yang sama.
- Perhatikan tipografi: font harus tetap rapi untuk karakter Han (lebih padat dibanding Latin).
- Toggle bahasa tanpa reload halaman (via JS + parameter/session).

### 3.2 Data Pasien
| Field | Tipe | Validasi |
|---|---|---|
| Nama | text | wajib |
| NRIC/FIN | text | wajib, format checksum Singapura |
| Alamat | textarea | wajib |
| Kode Pos | text | wajib |
| No. Kontak | tel | wajib, format nomor telepon |
| Jenis Kelamin | radio | wajib |
| Tanggal Lahir | date | wajib, tidak boleh tanggal masa depan |

### 3.3 Data Guardian / Next of Kin
- Field: Nama, NRIC/FIN, Hubungan.
- **Business rule wajib diterapkan di server (bukan hanya JS):**
  ```
  IF usia_pasien < 21 THEN guardian = WAJIB
  ELSE guardian = OPSIONAL
  ```

### 3.4 Kuesioner Riwayat Medis
14 item (jantung, pacemaker, diabetes, hipertensi, kolesterol tinggi, kanker, kulit sensitif, alergi, HIV/AIDS, kejang, konsumsi antikoagulan, riwayat operasi, perdarahan abnormal, kehamilan):
- Setiap item: pilihan **Ya / Tidak / Tidak Yakin**.
- Beberapa item butuh **field keterangan tambahan** ("please specify") yang **muncul secara kondisional** hanya saat "Ya" dipilih — ditangani JS di frontend **dan divalidasi ulang di backend**.
- Rancang sebagai **struktur data dinamis** (array `question_code`), bukan variabel PHP terpisah per pertanyaan — agar mudah menambah/mengubah item tanpa migrasi besar.
- Sediakan kolom bebas "informasi medis lain" (textarea, opsional).

### 3.5 Klausul Informed Consent
- 7 paragraf teks statis bilingual (cakupan treatment, risiko, tidak ada jaminan hasil, penggunaan data pribadi, hak bertanya, dsb).
- UI: tampilkan teks + checkbox eksplisit ("saya telah membaca dan memahami", "saya setuju dengan treatment yang dijelaskan") sebelum lanjut ke tanda tangan.

### 3.6 Versioning Consent (penting, sering terlewat)
- Simpan `consent_version` (mis. `2026.01`), bukan hanya `consent_id`.
- Sebaiknya simpan juga **snapshot isi consent** pada saat ditandatangani (`consent_content_snapshot`), agar jika teks consent berubah di kemudian hari, dokumen lama tetap menampilkan versi yang benar-benar disetujui pasien saat itu.

### 3.7 Tanda Tangan Digital (Signature)
- **Dua tanda tangan terpisah**: Pasien/Wali dan Praktisi TCM — kemungkinan ditandatangani pada waktu berbeda.
- **Frontend**: HTML5 Canvas + library `signature_pad`, mendukung **pointer events** (mouse, touch, stylus) — bukan hanya `mousedown/mouseup`.
- **Alur pengiriman**: Canvas → PNG → dikirim via AJAX/fetch (base64) → server decode → simpan sebagai **file PNG di storage**, path-nya yang disimpan ke database (bukan base64 langsung di kolom DB).
- **Setiap signature wajib menyertakan metadata**:
  - `signed_by` (nama)
  - `signed_role` (patient / practitioner)
  - `signed_at` (timestamp)
  - opsional: IP address / device info untuk audit
- **(Opsional, level lanjut)** Integritas dokumen: gunakan ekstensi `openssl` PHP untuk membuat hash + digital signature atas seluruh data form (bukan hanya gambar tanda tangan), agar dapat diverifikasi bahwa data tidak diubah setelah ditandatangani.

### 3.8 Status Workflow Consent
Gunakan state machine eksplisit di database, misalnya:
```
draft → in_progress → awaiting_patient_signature →
awaiting_practitioner_signature → completed
(+ cancelled, expired sebagai status tambahan)
```

### 3.9 Dokumen Akhir (PDF)
- Setelah status `completed`, generate PDF berisi: data pasien, jawaban kuesioner medis, teks consent (sesuai versi & bahasa yang dipilih), kedua gambar tanda tangan, timestamp, dan nomor/versi dokumen.
- Gunakan **PDF generator berbasis struktur data** (mis. **mPDF** — lebih baik dari `dompdf` untuk dukungan font CJK), **bukan** screenshot HTML → PDF, agar hasil konsisten dan layak cetak.
- PDF disimpan di storage dengan nama berbasis ID unik.

### 3.10 Audit Log
- Catat event penting: consent dibuat, data pasien diperbarui, kuesioner selesai, pasien tanda tangan, praktisi tanda tangan, consent selesai, PDF digenerate — minimal timestamp + jenis event.

---

## 4. Kebutuhan Teknis & Arsitektur

### 4.1 Pola Arsitektur
- **Single-page UI**: satu `index.html` + `app.js` menampilkan seluruh tahap form, dikontrol JS berdasarkan status yang di-fetch dari backend — tanpa reload halaman penuh.
- **Backend berupa endpoint API kecil dan fokus** (dipanggil via fetch/AJAX dari `app.js`), misalnya:
  - `submit_patient.php`
  - `submit_history.php`
  - `submit_signature.php`
  - `get_consent.php`
  - `generate_pdf.php`
- Praktisi mengakses tahap counter-sign melalui **token unik di URL** (mis. `index.html?consent=<token>&step=practitioner`), yang memuat data existing dari SQLite dan menampilkan hanya tahap tanda tangan praktisi.

### 4.2 Struktur Folder yang Disarankan
```
/project
├── public/            → index.html, app.js, css/
├── api/                → endpoint PHP (submit_*.php, get_*.php, generate_pdf.php)
├── app/ (opsional)    → Controllers/, Services/, Validators/, Helpers/
├── lang/               → en.php, zh.php (teks UI)
├── consent/            → en.php, zh.php per versi (isi klausul consent)
├── storage/            → DI LUAR akses publik langsung
│   ├── consent.db      → SQLite, WAL mode
│   ├── signatures/      → file PNG tanda tangan
│   ├── pdf/             → hasil generate PDF
│   └── logs/
├── config/             → database.php, app.php
└── vendor/              → Composer (mPDF, dsb.)
```
Prinsip: tetap "native PHP", tapi logic tetap dipisah dari presentasi — hindari satu file berisi ribuan baris kode.

### 4.3 Database (SQLite)
**Alasan pemilihan SQLite:**
- Tidak perlu server database terpisah, cukup satu file `.db` — cocok klinik kecil/single-location.
- Deployment ringan, backup tinggal copy file.

**Yang wajib diperhatikan:**
- Aktifkan `PRAGMA journal_mode=WAL;` agar read tidak memblokir write dan sebaliknya (concurrency SQLite terbatas, tapi cukup untuk traffic form consent yang rendah).
- File `.db` **harus ditempatkan di luar document root** (di luar `public/`) atau diberi proteksi `.htaccess deny` — karena isinya data medis sensitif.
- Pastikan ekstensi `pdo_sqlite` aktif di hosting.
- Gambar signature sebaiknya disimpan sebagai **file terpisah**, bukan BLOB di SQLite, agar file `.db` tidak membengkak dan lebih mudah dirawat.

**Skema tabel inti (disarankan tabel relasi untuk kuesioner medis, bukan 14 kolom terpisah):**

| Tabel | Isi |
|---|---|
| `consent_forms` | id (token unik/UUID, acak & tidak mudah ditebak), status, bahasa, consent_version, created_at, completed_at |
| `patients` | nama, NRIC/FIN, alamat, kode pos, kontak, jenis kelamin, tgl lahir, consent_id |
| `guardians` | nama, NRIC/FIN, hubungan (nullable, hanya wajib jika pasien < 21) |
| `medical_answers` | consent_id, question_code, answer, specification |
| `signatures` | consent_id, tipe (patient/practitioner), path gambar, signed_by, signed_at |
| `audit_logs` | consent_id, event, timestamp |

### 4.4 Dependensi (via Composer, tetap "native" tanpa framework besar)
- `mPDF` — generate PDF dengan dukungan font CJK.
- `signature_pad` (JS, client-side) — capture tanda tangan.
- Tidak perlu library i18n besar — array bahasa PHP sederhana sudah cukup.

### 4.5 Kompatibilitas & Responsif
- Signature berbasis Canvas wajib diuji di: Chrome, Edge, Safari, Android Chrome, iOS Safari — dengan input mouse, touchscreen, dan stylus (gunakan **pointer events**).
- UI harus responsif untuk desktop, tablet, dan smartphone (area tanda tangan juga menyesuaikan).
- **Autosave** (opsional/prioritas menengah): berguna jika form diisi di tablet klinik dan bisa ter-refresh tanpa sengaja.

---

## 5. Keamanan & Kepatuhan

Karena aplikasi menangani **data kesehatan dan identitas (NRIC/FIN)**, keamanan harus masuk sejak tahap desain:

- **Transport**: HTTPS/TLS wajib untuk seluruh komunikasi.
- **Enkripsi data sensitif**: field seperti NRIC/FIN sebaiknya dienkripsi saat disimpan (`openssl_encrypt`), bukan disimpan sebagai plaintext.
- **SQL Injection**: gunakan PDO + prepared statements untuk seluruh query — tidak ada string SQL yang dirakit manual dari input user.
- **XSS**: seluruh output ke HTML/PDF di-escape (`htmlspecialchars()`), khususnya field bebas seperti "informasi medis lain".
- **CSRF**: token unik per sesi form, diverifikasi di server sebelum data diterima.
- **Rate limiting** sederhana untuk mencegah spam submission.
- **Keamanan file signature**: validasi MIME type, ukuran file, ekstensi, dan dimensi gambar; simpan di lokasi yang tidak bisa dieksekusi sebagai script.
- **Token akses pasien**: harus panjang, acak, tidak dapat ditebak (bukan ID auto-increment berurutan), dan idealnya punya masa berlaku (expiration).
- **Akses staf/praktisi** (melihat daftar consent pending, counter-sign, dashboard) harus di belakang **autentikasi terpisah** — form pasien tidak boleh sepenuhnya publik tanpa proteksi apa pun.
- **Session handling**: `HttpOnly`, `Secure`, `SameSite`, regenerasi session ID, timeout session.
- **Error handling**: jangan tampilkan stack trace/error SQL ke user; log detail ke file log internal, tampilkan pesan generik ke user.
- **Kepatuhan data medis**: data minimization (hanya kumpulkan field yang benar-benar perlu), access control ketat, serta kebijakan retensi dan backup data (harian, dengan retention policy).

---

## 6. Model Autentikasi/Akses yang Perlu Diputuskan

Tiga model yang mungkin diterapkan (perlu dipilih sebelum development):

| Model | Alur | Catatan |
|---|---|---|
| A | Pasien buka link publik, isi sendiri | Paling terbuka, risiko lebih tinggi jika token lemah |
| B | Staf login dahulu → buat consent → pasien tanda tangan | Lebih terkontrol |
| C | Staf generate sesi consent → pasien buka secure link → tanda tangan | Kombinasi kontrol staf + kemudahan pasien |

Model B atau C lebih disarankan dibanding form yang benar-benar publik tanpa proteksi.

---

## 7. Prioritas Kebutuhan (MoSCoW ringkas)

| Area | Prioritas |
|---|---|
| Data Pasien | 🔴 Wajib |
| Kuesioner Medis | 🔴 Wajib |
| Multi-bahasa | 🔴 Wajib |
| Isi Consent | 🔴 Wajib |
| Tanda Tangan Digital | 🔴 Wajib |
| Validasi Server-side | 🔴 Wajib |
| Database (SQLite) | 🔴 Wajib |
| HTTPS | 🔴 Wajib |
| Consent Versioning | 🔴 Wajib |
| UI Responsif | 🔴 Wajib |
| Generate PDF | 🟠 Disarankan |
| Audit Log | 🟠 Disarankan |
| Token Akses Aman | 🟠 Disarankan |
| Autosave | 🟡 Opsional |
| Dashboard Admin | 🟡 Opsional |
| Notifikasi Email/WhatsApp | 🟡 Opsional |

---

## 8. Pertanyaan yang Perlu Dikonfirmasi Sebelum Mulai Coding

**Bisnis**
1. Apakah pasien mengisi sendiri, atau selalu didampingi/dibuat oleh staf?
2. Apakah pasien bisa mengisi dari ponsel pribadi, atau hanya perangkat klinik?
3. Apakah satu consent berlaku untuk seluruh jenis treatment, atau per treatment?
4. Bagaimana proses pembatalan/pembaruan consent yang sudah ditandatangani?

**Bahasa**
5. Apakah Indonesia perlu ditambahkan di versi pertama, atau menyusul?
6. Siapa yang akan mengelola/memperbarui teks terjemahan dan klausul consent?

**Tanda Tangan**
7. Apakah perlu menyimpan IP/device info untuk audit tanda tangan?
8. Apa prosedurnya jika tanda tangan perlu diulang (salah tanda tangan)?

**Data**
9. Berapa lama data disimpan (retensi)?
10. Siapa saja yang berhak mengakses data setelah tersimpan?
11. Apakah data yang sudah ditandatangani boleh diedit?

**Dokumen**
12. Apakah PDF wajib bilingual (dua bahasa sekaligus) atau sesuai bahasa yang dipilih saja?
13. Apakah PDF perlu nomor dokumen resmi dan/atau dikirim otomatis via email?

---

## 9. Urutan Kerja yang Disarankan

```
1. Business Requirement (jawab pertanyaan §8)
2. Functional Requirement (dokumen ini)
3. User Flow & Wireframe UI
4. Data Model / Skema Database
5. Security Requirement
6. Technical Architecture
7. Development
8. Testing (lintas browser & device, khusus signature)
9. Deployment (HTTPS, backup, retensi)
```

**Kesimpulan:** Sebelum mulai menulis `index.html`/`app.js` dan endpoint PHP, disarankan menyelesaikan dahulu tiga dokumen turunan dari analisis ini: (1) SRS ringkas, (2) Skema Data final, dan (3) Spesifikasi Teknis (routing, signature, PDF, keamanan, deployment) — agar pengembangan tidak berjalan tambal-sulam.
