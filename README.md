<p align="center">
  <img src="https://cdn-icons-png.flaticon.com/512/3304/3304567.png" width="100" alt="MedRec Logo">
</p>

<h1 align="center">🏥 MedRec (Medical Record System)</h1>

<p align="center">
  <strong>Sistem Manajemen Rekam Medis Modern dengan Integrasi Supabase.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Project-MedRec-blue?style=for-the-badge" alt="project">
  <img src="https://img.shields.io/badge/Database-Supabase-green?style=for-the-badge&logo=supabase" alt="database">
  <img src="https://img.shields.io/badge/Academic-UDINUS-red?style=for-the-badge" alt="udinus">
</p>

---

## 📋 Tentang Proyek
**MedRec** adalah platform digital untuk mengelola data pasien dan riwayat medis secara real-time. Dengan menggunakan **Supabase**, sistem ini memiliki performa database yang lebih cepat, aman, dan mudah dikelola tanpa perlu menjalankan server database lokal.

### ✨ Fitur Utama
- 🔐 **Secure Auth** – Login menggunakan sistem autentikasi dari Supabase.
- 📂 **Manajemen Pasien** – CRUD data pasien yang tersinkronisasi langsung ke Cloud.
- 📑 **Rekam Medis** – Pencatatan riwayat penyakit dan diagnosis pasien.
- 📊 **Real-time Updates** – Data yang diinput langsung terupdate secara instan.
- 🖨️ **Cetak Laporan** – Fitur cetak ringkasan medis pasien.

---

## 🛠️ Tech Stack
| Komponen | Teknologi |
| :--- | :--- |
| **Language** | ![PHP](https://img.shields.io/badge/php-%23777BB4.svg?style=flat&logo=php&logoColor=white) |
| **Database** | ![Supabase](https://img.shields.io/badge/Supabase-3ECF8E?style=flat&logo=supabase&logoColor=white) |
| **Frontend** | ![Bootstrap](https://img.shields.io/badge/bootstrap-%23563D7C.svg?style=flat&logo=bootstrap&logoColor=white) |

---

## 🚀 Panduan Instalasi (Supabase Version)

Silakan ikuti langkah-langkah berikut untuk menghubungkan aplikasi dengan dashboard Supabase Anda:

### 1. Clone Repositori
```bash
git clone [https://github.com/nadjwasalsabila/medrec.git](https://github.com/nadjwasalsabila/medrec.git)cd medrec

2. Setup Database Supabase (Point 2)
Buat akun dan proyek baru di Supabase.com.

Buka menu SQL Editor di dashboard Supabase.

Jalankan (Run) query dari file database/schema.sql (jika tersedia di folder proyek) untuk membuat tabel secara otomatis.

Pastikan tabel pasien, rekam_medis, dan users sudah muncul di menu Table Editor.

3. Konfigurasi API Key (Point 3)
Buka file koneksi database Anda (misal: config.php atau .env).

Ambil Project URL dan Anon Key dari menu Project Settings > API di Supabase.

Masukkan ke dalam kode:

PHP

$SUPABASE_URL = "[https://id-proyek-kamu.supabase.co](https://id-proyek-kamu.supabase.co)";
$SUPABASE_KEY = "isi-dengan-anon-key-kamu";
4. Menjalankan Aplikasi (Point 4)
Jika menggunakan XAMPP, pindahkan folder proyek ke C:/xampp/htdocs/.

Buka browser dan akses: http://localhost/medrec

Atau gunakan terminal: php -S localhost:8000

💡 Tips & Catatan Tambahan
File SQL: Jika kamu melakukan perubahan struktur tabel di Supabase, jangan lupa export SQL-nya ke folder database/ agar teman setim bisa mengikuti.

RLS (Security): Pastikan kebijakan Row Level Security di Supabase sudah diatur agar data pasien tidak bisa diakses sembarang orang.

Screenshots: Tambahkan gambar aplikasi kamu di bawah ini untuk mempercantik tampilan README.
