<p align="center">
  <img src="https://cdn-icons-png.flaticon.com/512/3304/3304567.png" width="100" alt="MedRec Logo">
</p>

<h1 align="center">🏥 MedRec (Medical Record System)</h1>

<p align="center">
  <strong>Sistem Manajemen Rekam Medis Modern Terintegrasi Supabase Cloud.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Project-MedRec-blue?style=for-the-badge" alt="project">
  <img src="https://img.shields.io/badge/Database-Supabase-3ECF8E?style=for-the-badge&logo=supabase&logoColor=white" alt="database">
  <img src="https://img.shields.io/badge/Status-Active-orange?style=for-the-badge" alt="status">
</p>

---

## 📋 Tentang Proyek
**MedRec** adalah platform digital untuk mengelola data rekam medis pasien secara real-time. Proyek ini menggunakan **Supabase** sebagai backend untuk memastikan penyimpanan data yang aman, cepat, dan mudah diakses.

### ✨ Fitur Utama
* 🔐 **Secure Authentication** – Login aman menggunakan layanan Auth dari Supabase.
* 📂 **Manajemen Pasien** – Kelola data pasien (Tambah, Edit, Hapus) dengan sinkronisasi cloud.
* 📑 **Catatan Medis** – Riwayat diagnosis dan resep obat yang tersimpan rapi.
* 📊 **Dashboard Statistik** – Pantau jumlah pasien dan kunjungan secara visual.

---

## 🛠️ Tech Stack
| Komponen | Teknologi |
| :--- | :--- |
| **Language** | ![PHP](https://img.shields.io/badge/php-%23777BB4.svg?style=flat&logo=php&logoColor=white) |
| **Database** | ![Supabase](https://img.shields.io/badge/Supabase-3ECF8E?style=flat&logo=supabase&logoColor=white) |
| **Frontend** | ![Bootstrap](https://img.shields.io/badge/bootstrap-%23563D7C.svg?style=flat&logo=bootstrap&logoColor=white) |

---

## 🚀 Panduan Instalasi & Konfigurasi

### 1. Clone Repositori
Langkah pertama, unduh proyek ini ke komputer Anda:
```bash
git clone [https://github.com/nadjwasalsabila/medrec.git](https://github.com/nadjwasalsabila/medrec.git)
cd medrec
```
### 2. Setup Database Supabase
  - Buat akun dan proyek baru di Supabase.com.
  - Buka menu SQL Editor di dashboard Supabase Anda.
  - Jalankan query dari file database/schema.sql untuk membuat tabel secara otomatis.
  - Pastikan tabel pasien dan rekam_medis sudah muncul di Table Editor.

### 3. Konfigurasi API Key
  Buka file koneksi database Anda (misal: config.php) dan masukkan Project URL serta Anon Key dari menu Settings > API di Supabase:
  ```
  $SUPABASE_URL = "[https://id-proyek-kamu.supabase.co](https://id-proyek-kamu.supabase.co)";
  $SUPABASE_KEY = "isi-dengan-anon-key-kamu";
  ```

### 4. Menjalankan Aplikasi
   Anda bisa menjalankan aplikasi menggunakan server lokal (XAMPP) atau PHP built-in server:
    - Via XAMPP: Pindahkan folder ke ``` htdocs ``` dan akses ``` http://localhost/medrec.```
    - Via Terminal: Gunakan perintah ``` php -S localhost:8000.```
  
## 📸 Cuplikan Antarmuka
<p align="center"> <img src="https://www.google.com/search?q=https://via.placeholder.com/800x450%3Ftext%3DUpload%2BScreenshot%2BAplikasi%2BKelompok%2BDisini" alt="MedRec Preview"> </p>

Tim Pengembang (Kontributor)
Proyek ini disusun oleh mahasiswa Teknik Informatika Universitas Dian Nuswantoro:
  - Raffael Ezra Nugroho - @Renz-Amamiya
  - Nadjwa Salsabila - @nadjwasalsabila
  - Timothy Giovanny - @Moty-G
  - Naia Syafina - @naiasyafina
  - micaxsz - @micaxsz
<p align="center"> Projek mata kuliah KRIPTOGRAFI </p>
