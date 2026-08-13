# Software Requirements Specification (SRS)
## Aplikasi Digital Informed Consent — Klinik TCM

**Versi Dokumen:** 1.0
**Tanggal:** 13 Agustus 2026
**Status:** Draft

---

## 1. Pendahuluan

### 1.1 Tujuan
Dokumen ini menjelaskan kebutuhan perangkat lunak untuk aplikasi *Digital Informed Consent* pada klinik pengobatan tradisional Tiongkok (TCM), yang menggantikan formulir persetujuan kertas bilingual (Inggris/Mandarin) menjadi aplikasi web satu halaman (single-page) berbasis native PHP, dengan fitur tanda tangan digital dan penyimpanan data lokal (SQLite).

Dokumen ini menjadi acuan bagi tim pengembang, penguji (QA), dan pihak klinik (product owner) dalam memahami cakupan, batasan, dan perilaku sistem yang akan dibangun.

### 1.2 Ruang Lingkup Produk
Sistem yang dibangun bernama **TCM Consent App**, berfungsi untuk:
- Mengumpulkan data identitas pasien (dan wali/guardian bila perlu).
- Mengumpulkan riwayat kesehatan pasien melalui kuesioner terstruktur.
- Menyajikan klausul informed consent secara bilingual.
- Menangkap tanda tangan digital pasien/wali dan praktisi TCM.
- Menyimpan seluruh data secara aman di database SQLite lokal.
- Menghasilkan dokumen bukti persetujuan dalam format PDF.

**Di luar ruang lingkup (out of scope) versi pertama:**
- Integrasi dengan sistem rekam medis elektronik (EMR) pihak ketiga.
- Aplikasi mobile native (iOS/Android terpisah) — cukup web responsif.
- Notifikasi email/WhatsApp otomatis (opsional, fase berikutnya).
- Dashboard analitik/reporting lanjutan.

### 1.3 Definisi, Akronim, dan Singkatan
| Istilah | Keterangan |
|---|---|
| TCM | Traditional Chinese Medicine (Pengobatan Tradisional Tiongkok) |
| NRIC/FIN | Nomor identitas resmi warga/penduduk Singapura |
| SPA | Single Page Application |
| SRS | Software Requirements Specification |
| CSRF | Cross-Site Request Forgery |
| XSS | Cross-Site Scripting |
| WAL | Write-Ahead Logging (mode jurnal SQLite) |
| PDO | PHP Data Objects |
| i18n | Internationalization (dukungan multi-bahasa) |

### 1.4 Referensi
- Dokumen sumber: *Informed Consent for TCM Treatment and Acupuncture* (formulir kertas bilingual EN/ZH, klinik TCM Singapura).
- Dokumen Analisis Kebutuhan — TCM Consent App (v1.0).

### 1.5 Gambaran Umum Dokumen
Bagian 2 menjelaskan deskripsi umum produk. Bagian 3 merinci kebutuhan fungsional per modul. Bagian 4 kebutuhan antarmuka eksternal. Bagian 5 kebutuhan non-fungsional (kinerja, keamanan, dsb). Bagian 6 model data. Bagian 7 batasan dan asumsi.

---

## 2. Deskripsi Umum

### 2.1 Perspektif Produk
Aplikasi berdiri sendiri (standalone), berjalan di atas web server dengan PHP native (tanpa framework besar) dan database SQLite lokal (tidak bergantung server database terpisah). Aplikasi diakses melalui browser di perangkat desktop, tablet, atau smartphone milik klinik maupun pasien.

### 2.2 Fungsi Utama Produk
1. Pemilihan bahasa antarmuka (EN/ZH, dapat diperluas).
2. Pengisian data pasien dan (kondisional) data wali/guardian.
3. Pengisian kuesioner riwayat medis (14 item) dengan field kondisional.
4. Penyajian klausul informed consent bilingual dan checklist persetujuan.
5. Penangkapan tanda tangan digital pasien/wali.
6. Penangkapan tanda tangan digital praktisi TCM (dapat berbeda sesi/waktu).
7. Penyimpanan seluruh data secara terenkripsi/aman di SQLite.
8. Generate dokumen PDF hasil akhir sebagai bukti persetujuan.
9. Audit log seluruh aktivitas penting pada satu record consent.

### 2.3 Karakteristik Pengguna
| Peran | Deskripsi | Hak Akses |
|---|---|---|
| **Pasien / Wali** | Mengisi data diri, riwayat medis, membaca consent, tanda tangan | Akses via token unik, tanpa login akun |
| **Praktisi TCM** | Melakukan counter-sign atas consent yang sudah diisi pasien | Akses via token unik dan/atau login staf sederhana |
| **Staf Admin Klinik** *(opsional, model B/C)* | Membuat sesi consent baru, memantau status pending, mengelola arsip PDF | Login terautentikasi |

### 2.4 Batasan Umum
- Native PHP 8.x, tanpa framework besar (Laravel, Symfony, dll).
- Database SQLite (bukan MySQL/PostgreSQL server terpisah).
- Harus berjalan di hosting shared PHP standar (ekstensi `pdo_sqlite` wajib aktif).
- Tidak boleh menyimpan data sensitif tanpa enkripsi/proteksi akses.
- UI harus tetap satu halaman (SPA) tanpa reload penuh antar tahap.

### 2.5 Asumsi dan Ketergantungan
- Klinik menyediakan perangkat (tablet/PC) dengan browser modern yang mendukung Canvas dan Pointer Events.
- Koneksi HTTPS tersedia di lingkungan produksi.
- Composer tersedia untuk instalasi dependensi (mPDF, dsb).
- Poin-poin yang belum dikonfirmasi klinik (lihat §7.3) diasumsikan sesuai rekomendasi pada dokumen ini sampai ada keputusan resmi.

---

## 3. Kebutuhan Fungsional

Setiap kebutuhan diberi ID unik `FR-xx` untuk keperluan traceability ke pengujian.

### 3.1 Modul Bahasa (i18n)

| ID | Kebutuhan |
|---|---|
| FR-01 | Sistem **harus** menyediakan pilihan bahasa Inggris dan Mandarin pada saat halaman pertama kali dibuka. |
| FR-02 | Sistem **harus** menyimpan preferensi bahasa yang dipilih pasien pada record consent terkait. |
| FR-03 | Sistem **harus** mengganti seluruh label UI dan isi klausul consent sesuai bahasa terpilih tanpa reload halaman. |
| FR-04 | Sistem **harus** dirancang agar penambahan bahasa baru (mis. Indonesia) tidak memerlukan perubahan struktur kode, cukup penambahan file bahasa baru. |
| FR-05 | Sistem **harus** memisahkan teks UI (label/tombol) dari isi klausul consent (teks hukum) dalam struktur data/file yang berbeda. |

### 3.2 Modul Data Pasien

| ID | Kebutuhan |
|---|---|
| FR-10 | Sistem **harus** menyediakan form input: Nama, NRIC/FIN, Alamat, Kode Pos, No. Kontak, Jenis Kelamin, Tanggal Lahir. |
| FR-11 | Sistem **harus** memvalidasi seluruh field sebagai wajib diisi (required), di sisi client **dan** server. |
| FR-12 | Sistem **harus** memvalidasi format NRIC/FIN sesuai checksum yang berlaku. |
| FR-13 | Sistem **harus** memvalidasi Tanggal Lahir tidak boleh berupa tanggal di masa depan. |
| FR-14 | Sistem **harus** memvalidasi format No. Kontak sebagai nomor telepon yang valid. |

### 3.3 Modul Data Guardian / Next of Kin

| ID | Kebutuhan |
|---|---|
| FR-20 | Sistem **harus** menyediakan form input Nama, NRIC/FIN, dan Hubungan untuk wali/guardian. |
| FR-21 | Sistem **harus** menghitung usia pasien dari Tanggal Lahir dan menentukan apakah data guardian wajib diisi (usia < 21 tahun). |
| FR-22 | Sistem **harus** menegakkan aturan wajib-guardian di sisi **server**, tidak hanya mengandalkan validasi JavaScript di client. |

### 3.4 Modul Kuesioner Riwayat Medis

| ID | Kebutuhan |
|---|---|
| FR-30 | Sistem **harus** menampilkan 14 item pertanyaan riwayat medis, masing-masing dengan pilihan Ya/Tidak/Tidak Yakin. |
| FR-31 | Sistem **harus** menampilkan field keterangan tambahan ("please specify") secara otomatis saat jawaban "Ya" dipilih pada item yang relevan (mis. kanker, alergi, riwayat operasi). |
| FR-32 | Sistem **harus** memvalidasi ulang di server bahwa field keterangan terisi jika jawaban terkait adalah "Ya" dan item tersebut mewajibkan keterangan. |
| FR-33 | Sistem **harus** menyimpan struktur pertanyaan secara dinamis (bukan kolom database tetap per pertanyaan) agar item dapat ditambah/diubah tanpa migrasi skema besar. |
| FR-34 | Sistem **harus** menyediakan kolom teks bebas opsional untuk "informasi medis lain yang ingin disampaikan ke praktisi". |

### 3.5 Modul Informed Consent

| ID | Kebutuhan |
|---|---|
| FR-40 | Sistem **harus** menampilkan seluruh klausul consent (7 paragraf) sesuai bahasa dan versi consent yang berlaku. |
| FR-41 | Sistem **harus** menyediakan checkbox eksplisit "saya telah membaca dan memahami" dan "saya setuju dengan treatment yang dijelaskan" sebelum pengguna dapat melanjutkan ke tahap tanda tangan. |
| FR-42 | Sistem **harus** mencatat `consent_version` yang berlaku pada saat pasien menyetujui. |
| FR-43 | Sistem **sebaiknya** menyimpan salinan (snapshot) isi teks consent pada saat ditandatangani, terlepas dari perubahan isi consent di kemudian hari. |

### 3.6 Modul Tanda Tangan Digital

| ID | Kebutuhan |
|---|---|
| FR-50 | Sistem **harus** menyediakan area tanda tangan berbasis HTML5 Canvas yang mendukung mouse, touchscreen, dan stylus (pointer events). |
| FR-51 | Sistem **harus** menyediakan tombol "Clear/Hapus" untuk mengulang tanda tangan sebelum submit. |
| FR-52 | Sistem **harus** menangkap dua tanda tangan terpisah: Pasien/Wali dan Praktisi TCM, yang dilakukan pada satu halaman formulir yang sama. |
| FR-53 | Sistem **harus** mengonversi tanda tangan menjadi file gambar (PNG) di sisi server dan menyimpan path file-nya di database (bukan menyimpan base64 langsung di kolom database). |
| FR-54 | Sistem **harus** mencatat metadata setiap tanda tangan: nama penanda tangan, peran (pasien/wali/praktisi), dan timestamp. |
| FR-55 | Sistem **harus** memungkinkan praktisi untuk menandatangani form langsung di perangkat yang sama sesaat setelah pasien menyelesaikannya (tanpa link/sesi terpisah). |
| FR-56 | Sistem **sebaiknya** memvalidasi tipe MIME, ukuran, dan dimensi gambar tanda tangan yang diunggah ke server. |

### 3.7 Modul Status & Workflow

| ID | Kebutuhan |
|---|---|
| FR-60 | Sistem **harus** memiliki status consent yang eksplisit: `draft`, `in_progress`, `completed` (serta `cancelled`/`expired` bila diperlukan). |
| FR-61 | Sistem **harus** mencegah perubahan status secara tidak berurutan (mis. tidak bisa langsung `completed` tanpa kedua tanda tangan ada). |

### 3.8 Modul Dokumen (PDF)

| ID | Kebutuhan |
|---|---|
| FR-70 | Sistem **harus** menghasilkan dokumen PDF setelah status consent menjadi `completed`, berisi seluruh data pasien, jawaban kuesioner, teks consent, kedua tanda tangan, dan timestamp. |
| FR-71 | Sistem **harus** menghasilkan PDF berbasis data terstruktur (PDF generator), bukan screenshot HTML. |
| FR-72 | Sistem **harus** mendukung font CJK agar teks Mandarin tampil benar pada PDF. |
| FR-73 | Sistem **harus** menyimpan file PDF hasil generate di storage dan menyediakan tautan unduh bagi pengguna berwenang. |

### 3.9 Modul Audit Log

| ID | Kebutuhan |
|---|---|
| FR-80 | Sistem **harus** mencatat log untuk setiap event penting per record consent: dibuat, data pasien diperbarui, kuesioner selesai, pasien tanda tangan, praktisi tanda tangan, consent selesai, PDF dihasilkan — masing-masing dengan timestamp. |

---

## 4. Kebutuhan Antarmuka Eksternal

### 4.1 Antarmuka Pengguna (UI)
- Antarmuka berbasis web, satu halaman (SPA), dengan transisi antar tahap menggunakan JavaScript (show/hide section), tanpa reload penuh.
- Wajib **responsif**: desktop, tablet, dan smartphone.
- Area tanda tangan menyesuaikan ukuran layar tanpa terpotong.

### 4.2 Antarmuka Perangkat Keras
- Tidak ada kebutuhan perangkat keras khusus selain perangkat dengan layar sentuh/mouse/stylus dan browser modern.

### 4.3 Antarmuka Perangkat Lunak
- **Web Server**: Apache atau Nginx dengan dukungan PHP 8.x.
- **Database**: SQLite (`pdo_sqlite`), mode WAL.
- **PDF Generator**: mPDF (via Composer).
- **JS Library**: `signature_pad` untuk penangkapan tanda tangan.

### 4.4 Antarmuka Komunikasi
- Seluruh komunikasi client-server melalui **HTTPS**.
- Pertukaran data antara frontend dan endpoint API menggunakan format **JSON** melalui AJAX/fetch.

---

## 5. Kebutuhan Non-Fungsional

### 5.1 Keamanan
| ID | Kebutuhan |
|---|---|
| NFR-01 | Seluruh trafik **harus** dienkripsi menggunakan HTTPS/TLS. |
| NFR-02 | Seluruh query database **harus** menggunakan PDO dengan prepared statements. |
| NFR-03 | Seluruh output ke HTML **harus** melalui proses escaping (`htmlspecialchars()`) untuk mencegah XSS. |
| NFR-04 | Setiap form submission **harus** dilindungi CSRF token yang diverifikasi di server. |
| NFR-05 | Field sensitif (mis. NRIC/FIN) **sebaiknya** dienkripsi saat disimpan di database. |
| NFR-06 | File database SQLite **harus** ditempatkan di luar document root yang dapat diakses publik, atau diproteksi eksplisit. |
| NFR-07 | Token akses pasien pada URL **harus** bersifat acak, panjang, dan tidak mudah ditebak (bukan ID berurutan), serta idealnya memiliki masa berlaku. |
| NFR-08 | Akses staf/praktisi untuk fitur administratif **harus** melalui autentikasi terpisah. |
| NFR-09 | Sistem **harus** menerapkan rate limiting sederhana untuk mencegah spam submission. |
| NFR-10 | Session **harus** dikonfigurasi dengan `HttpOnly`, `Secure`, `SameSite`, serta regenerasi ID dan timeout session. |

### 5.2 Kinerja
| ID | Kebutuhan |
|---|---|
| NFR-20 | Transisi antar tahap form **harus** terasa instan (tanpa reload halaman penuh). |
| NFR-21 | Proses generate PDF **harus** selesai dalam waktu wajar (target < 5 detik untuk dokumen standar 1 pasien). |

### 5.3 Keandalan & Ketersediaan Data
| ID | Kebutuhan |
|---|---|
| NFR-30 | Sistem **harus** memiliki mekanisme backup berkala (database + file signature + PDF), minimal harian. |
| NFR-31 | Sistem **harus** menerapkan retention policy sesuai kebijakan klinik untuk data pasien. |

### 5.4 Usability
| ID | Kebutuhan |
|---|---|
| NFR-40 | Form panjang **sebaiknya** memiliki indikasi progres/tahap yang jelas bagi pengguna. |
| NFR-41 | Pesan error **harus** informatif namun tidak menampilkan detail teknis (stack trace, path server) ke pengguna akhir. |

### 5.5 Kompatibilitas
| ID | Kebutuhan |
|---|---|
| NFR-50 | Aplikasi **harus** berfungsi baik di Chrome, Edge, dan Safari (desktop maupun mobile/Android/iOS). |
| NFR-51 | Fitur tanda tangan **harus** berfungsi dengan mouse, touchscreen, dan stylus. |

### 5.6 Maintainability
| ID | Kebutuhan |
|---|---|
| NFR-60 | Struktur kode **harus** memisahkan logic (PHP), presentasi (HTML/JS), dan data (lang/consent) — tidak boleh menjadi satu file monolitik. |
| NFR-61 | Struktur pertanyaan kuesioner medis **harus** dapat diperluas tanpa migrasi skema besar. |

---

## 6. Model Data (Ringkas)

Skema lengkap tersedia di Dokumen Analisis Kebutuhan; ringkasan entitas utama:

- **consent_forms** — id/token, status, bahasa, consent_version, created_at, completed_at
- **patients** — data identitas pasien, relasi ke consent_forms
- **guardians** — data wali (nullable, wajib jika usia pasien < 21)
- **medical_answers** — relasi consent_id ↔ question_code ↔ answer ↔ specification
- **signatures** — consent_id, tipe (patient/practitioner), path gambar, signed_by, signed_at
- **audit_logs** — consent_id, event, timestamp

---

## 7. Batasan, Ketergantungan, dan Isu Terbuka

### 7.1 Batasan Teknis
- SQLite memiliki keterbatasan concurrency (satu writer pada satu waktu) — dapat diterima untuk skala traffic klinik kecil, namun perlu dipantau jika traffic meningkat.
- Tidak menggunakan framework PHP besar sesuai preferensi eksplisit (native PHP).

### 7.2 Ketergantungan
- Ketersediaan ekstensi `pdo_sqlite` di lingkungan hosting.
- Ketersediaan Composer untuk instalasi mPDF.

### 7.3 Isu yang Masih Perlu Dikonfirmasi Klinik (Open Items)
Item berikut memengaruhi desain akhir dan perlu keputusan sebelum implementasi penuh:
1. Model akses: pasien mengisi mandiri (publik dengan token) vs. selalu melalui staf (login).
2. Apakah bahasa Indonesia wajib ada di rilis pertama.
3. Kebijakan retensi data dan siapa saja yang berwenang mengakses data tersimpan.
4. Apakah PDF wajib bilingual penuh atau cukup sesuai bahasa yang dipilih pasien.
5. Prosedur jika tanda tangan perlu diulang/dibatalkan setelah tersimpan.
6. Kebutuhan notifikasi (email/WhatsApp) — termasuk dalam rilis pertama atau fase berikutnya.

---

## 8. Lampiran

### 8.1 Prioritas Implementasi (Ringkasan)
🔴 Wajib (MVP): Data pasien, kuesioner medis, multi-bahasa, isi consent, tanda tangan digital, validasi server, database, HTTPS, consent versioning, UI responsif.

🟠 Disarankan: Generate PDF, audit log, token akses aman.

🟡 Opsional: Autosave, dashboard admin, notifikasi email/WhatsApp.

### 8.2 Riwayat Revisi Dokumen
| Versi | Tanggal | Keterangan |
|---|---|---|
| 1.0 | 13 Agustus 2026 | Draf awal, disusun dari sintesis analisis kebutuhan |
