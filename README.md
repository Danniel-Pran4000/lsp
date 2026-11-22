# GarageRental

Aplikasi Laravel untuk penyewaan garasi. Penyewa dapat mendaftar, mengajukan sewa, dan melihat riwayat transaksi. Admin dapat menyetujui akun, menambah unit garasi, memperpanjang sewa, dan membuka laporan.

## Prasyarat

- PHP 8.3 atau lebih baru dengan ekstensi `pdo_mysql` dan `fileinfo`.
- Composer 2.
- Node.js 20.19+ pada jalur 20.x atau Node.js 22.12+ beserta npm.
- MySQL atau MariaDB. Jika memakai XAMPP, nyalakan **MySQL** dari XAMPP Control Panel.

Jalankan semua perintah berikut dari folder proyek yang berisi file `artisan`.

## Menyiapkan aplikasi

1. Instal dependensi PHP.

   ```sh
   composer install
   ```

2. Buat database kosong bernama `db_garage_rental` melalui phpMyAdmin atau klien SQL.

   ```sql
   CREATE DATABASE db_garage_rental CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

   Jika memakai phpMyAdmin melalui XAMPP, nyalakan juga **Apache** lalu buka [phpMyAdmin](http://localhost/phpmyadmin/).

3. Pada instalasi baru, buat file `.env` di folder proyek dengan isi berikut. Sesuaikan `DB_USERNAME` dan `DB_PASSWORD` dengan akun database lokal Anda. Jika proyek sudah memiliki `.env`, sesuaikan konfigurasi database dan URL pada file tersebut.

   ```dotenv
   APP_NAME=GarageRental
   APP_ENV=local
   APP_KEY=
   APP_DEBUG=true
   APP_URL=http://127.0.0.1:8000
   APP_LOCALE=id

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=db_garage_rental
   DB_USERNAME=root
   DB_PASSWORD=

   SESSION_DRIVER=file
   CACHE_STORE=file
   QUEUE_CONNECTION=sync
   ```

4. Jika `APP_KEY` masih kosong, buat kunci aplikasi.

   ```sh
   php artisan key:generate
   ```

   Lalu muat konfigurasi terbaru dan buat tabel aplikasi.

   ```sh
   php artisan config:clear
   php artisan migrate
   ```

5. Instal dependensi frontend dan buat aset CSS serta JavaScript.

   ```sh
   npm ci
   npm run build
   ```

6. Buat akun admin pertama. Masukkan nama, email, dan kata sandi minimal 8 karakter saat diminta.

   ```sh
   php artisan app:create-admin
   ```

## Menjalankan aplikasi

```sh
php artisan serve --host=127.0.0.1 --port=8000
```

Biarkan terminal terbuka, lalu buka [halaman masuk GarageRental](http://127.0.0.1:8000/login).

- **Admin** masuk dengan akun dari perintah `app:create-admin`. Buka **Garasi** untuk menambahkan unit dan **Pengguna** untuk menyetujui akun penyewa.
- **Penyewa** memilih **Daftar** di halaman masuk. Setelah disetujui admin, penyewa dapat membuat transaksi melalui **Sewa saya → Sewa baru**.

### Menggunakan Apache XAMPP

Jika ingin membuka aplikasi melalui `http://localhost/GarageRental/login`, aktifkan `mod_rewrite` dan tambahkan konfigurasi berikut pada Apache, misalnya di `C:/xampp/apache/conf/extra/httpd-xampp.conf`. Sesuaikan jalur jika folder proyek Anda berbeda.

```apache
Alias "/GarageRental" "C:/xampp/htdocs/microcredential/public"
<Directory "C:/xampp/htdocs/microcredential/public">
    AllowOverride All
    Require local
</Directory>
```

Ubah `APP_URL` menjadi `http://localhost/GarageRental` di `.env`, jalankan `php artisan config:clear`, lalu mulai ulang Apache dan buka [GarageRental melalui XAMPP](http://localhost/GarageRental/login).
