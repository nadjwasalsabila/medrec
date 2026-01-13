<p align="center">
  <img src="https://cdn-icons-png.flaticon.com/512/3304/3304567.png" width="100" alt="MedRec Logo">
</p>

<h1 align="center">🏥 MedRec (Medical Record System)</h1>

<p align="center">
  <strong>Sistem Manajemen Rekam Medis Berbasis Web untuk Efisiensi Pelayanan Kesehatan.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Project-MedRec-blue?style=for-the-badge" alt="project">
  <img src="https://img.shields.io/badge/Status-Development-orange?style=for-the-badge" alt="status">
  <img src="https://img.shields.io/badge/Academic-UDINUS-red?style=for-the-badge" alt="udinus">
</p>

---

## 📋 Tentang Proyek
**MedRec** adalah platform digital yang dirancang untuk mengelola data pasien dan riwayat medis secara aman dan terorganisir. Proyek ini bertujuan untuk menggantikan pencatatan manual menjadi sistem digital yang lebih cepat dan akurat.

### ✨ Fitur Utama
- 🔐 **Secure Login** – Akses masuk untuk Admin dan Tenaga Medis.
- 📂 **Manajemen Pasien** – Kelola data identitas pasien (Tambah, Edit, Hapus).
- 📑 **Rekam Medis** – Pencatatan diagnosis, resep obat, dan riwayat kunjungan.
- 📊 **Dashboard** – Visualisasi data kunjungan pasien secara ringkas.
- 🖨️ **Cetak Laporan** – Fitur untuk mencetak riwayat medis pasien.

---

## 🛠️ Tech Stack
Teknologi yang digunakan dalam pengembangan sistem ini:

| Komponen | Teknologi |
| :--- | :--- |
| **Language** | ![PHP](https://img.shields.io/badge/php-%23777BB4.svg?style=flat&logo=php&logoColor=white) |
| **Database** | ![MySQL](https://img.shields.io/badge/mysql-%2300f.svg?style=flat&logo=mysql&logoColor=white) |
| **Frontend** | ![Bootstrap](https://img.shields.io/badge/bootstrap-%23563D7C.svg?style=flat&logo=bootstrap&logoColor=white) ![JavaScript](https://img.shields.io/badge/javascript-%23323330.svg?style=flat&logo=javascript&logoColor=%23F7DF1E) |

---

## 🚀 Cara Instalasi
Ikuti langkah berikut untuk menjalankan di komputer lokal:

1. **Clone Repositori**
   ```bash
   git clone [https://github.com/nadjwasalsabila/medrec.git](https://github.com/nadjwasalsabila/medrec.git)

2. Persiapan Database (Point 2)
    - Pastikan XAMPP/Laragon Anda sudah aktif (Apache & MySQL).
    - Buka phpMyAdmin di browser Anda.
    - Buat database baru dengan nama db_medrec.
    - Cari file bernama medrec.sql di dalam folder database/ pada proyek ini, lalu Import ke database yang baru dibuat.

3. Konfigurasi Koneksi (Point 3)
    - Buka file koneksi database (biasanya bernama config.php, koneksi.php, atau di folder config/).
      Sesuaikan pengaturannya dengan server lokal Anda:
       - $host = "localhost";
       - $user = "root";
       - $pass = "";
       - $db   = "db_medrec";

4. Menjalankan Aplikasi (Point 4)
    - Pastikan folder proyek berada di dalam direktori htdocs (XAMPP) atau www (Laragon).
    - Buka browser dan akses alamat berikut: http://localhost/medrec
    - Gunakan akun demo (jika ada) untuk masuk ke sistem.

💡 Tips Pengembangan
    - File SQL: Selalu pastikan file database terbaru sudah di-export ke folder database/ agar kolaborator lain bisa menggunakannya.
    - Screenshots: Untuk tampilan yang lebih menarik, tambahkan gambar screenshot aplikasi di bawah ini.

📸 Cuplikan Antarmuka
<p align="center"> <img src="https://www.google.com/search?q=https://via.placeholder.com/700x400%3Ftext%3DTambah%2BScreenshot%2BAplikasi%2BDisini" alt="MedRec Preview"> </p>
