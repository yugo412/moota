
# Moota.co Package untuk Laravel
 
[Moota.co](https://moota.co) adalah layanan untuk mengelola mutasi bank dalam satu dasbor dan cek transaksi secara otomatis. Moota.co mendukung berbagai bank lokal seperti Mandiri, BCA, BNI, Bank Muamalat, dan Bank BRI.

Repositori ini menyediakan package (tidak resmi) ditujukan pada framework Laravel untuk kemudahan penggunaan layanan yang disediakan oleh API Moota.co.
 
## Fitur yang Tersedia dalam Package 

- Cek profil.
- Cek balance (saldo pengguna).
- Bank yang didaftarkan.
- Rincian bank terdaftar.
- Mutasi pada bulan saat ini.
- Mutasi terakhir.
- Pencarian mutasi berdasarkan nominal.
- Pencarian mutasi berdasarkan deskripsi.

## Kebutuhan Sistem

- Laravel framework versi >= 5.2.
- PHP versi >= 7.0. Beberapa kode menggunakan [return type declaration](http://php.net/manual/en/functions.returning-values.php#functions.returning-values.type-declaration) yang hanya tersedia di PHP 7.
 
## Instalasi

Tambahkan package Moota untuk Laravel dengan menjalankan perintah berikut:
  
```
composer require yugo/moota -vvv
```

Laravel memiliki fitur [Package Discovery](https://laravel.com/docs/5.7/packages#package-discovery), yang memungkinkan package didaftarkan secara otomatis. Sehingga kamu dapat menggunakan facade Moota tanpa harus meregistrasikannya pada config.

Jika kamu mendapati pesan kesalahan class Moota tidak ditemukan, daftarkan provider dan facade Moota secara manual pada berkas `config/app.php`.

```
/*
* Package Service Providers...
*/
Yugo\Moota\Providers\MootaServiceProvider::class,
```

Tambahkan juga alias facade Moota jika diperlukan.

```
/*
|--------------------------------------------------------------------------
| Class Aliases
|--------------------------------------------------------------------------
|
| This array of class aliases will be registered when this application
| is started. However, feel free to register as many as you wish as
| the aliases are "lazy" loaded so they don't hinder performance.
|
*/
`Moota` => Yugo\Moota\Facades\Moota::class,
```
 
Pada berkas `.env`, tambahkan konfigurasi baru dengan nama seperti berikut:  

```
MOOTA_HOST="https://app.moota.co/api/v1/"
MOOTA_TOKEN="token-kamu"
MOOTA_TIMEMOUT=30
```  

Token bisa didapatkan melalui menu berikut: [https://app.moota.co/settings?tab=api](https://app.moota.co/settings?tab=api).  

Konfigurasi `MOOTA_HOST` bersifat opsional dan otomatis akan menggunakan endpoint terbaru jika tidak tersedia dalam berkas `.env`.

Konfigurasi `MOOTA_TIMEMOUT` juga bersifat opsional. Konfigurasi ini mengindikasikan berapa lama (dalam satuan detik) aplikasi akan memanggil API Moota sampai akhirnya mengembalikan exception. Pastikan kamu mengisi nilainya dengan tipe data integer.

## Penggunaan  

Setelah package berhasil diinstal, impor facade Moota terlebih dahulu.  

```
use Yugo\Moota\Facades\Moota;

// atau cukup seperti di bawah jika didaftarkan sebagai alias pada config/app.php
use Moota;
```  

Selanjutnya, facade Moota dapat digunakan untuk mengambil informasi dari API yang disediakan Moota.co. Di bawah beberapa contoh fungsi yang tersedia.  

#### Mendapatkan profil pengguna

Bisa digunakan untuk melihat rincian profil kamu.

```
Moota::profile();
```  

#### Mendapatkan saldo pengguna

Bisa digunakan untuk melihat saldo yang dimiliki oleh kamu (sebelum dipotong tagihan).

```
Moota::balance();
```  

#### Mendapatkan semua bank yang telah didaftarkan

```
Moota::banks();
```  

#### Mendapatkan rincian satu bank berdasarkan ID
ID bank bisa dilihat pada saat data bank diambil menggunakan fungsi di atas.  

```
Moota::bank($bankId = 'XXX123');
```  

#### Mendapatkan data mutasi bulan ini

Untuk mendapatkan mutasi pada bank, maka ID bank diperlukan untuk argumen fungsi. Sebagai contoh:  

```
Moota::mutation($bankId = 'XXX123')->month();
```  

#### Mendapatkan mutasi terakhir dari suatu bank

ID bank juga dibutuhkan untuk argumen fungsi ini. 

```
Moota::mutation($bankId = 'XXX123')->latest();
  
// menampilkan mutasi terakhir dengan jumlah tertentu
Moota::mutation($bankId = 'XXX123')->latest(15);
```  

#### Pencarian mutasi

Mutasi juga dapat dicari berdasarkan nominal dan deskripsi pada transaksi. Sama seperti dua fungsi di atas, fungsi ini juga membutuhkan ID bank sebagai argumennya.  

```
// cari mutasi berdasar nominal
Moota::mutation($bankId = 'XXX123')->amount(10000);  

// cari mutasi berdasar deskripsi
Moota::mutation($bankId = 'XXX123')->description('Transfer dari...');
```  

Semua nilai kembali (return) dari fungsi di atas berbentuk [Collection Laravel](https://laravel.com/docs/5.7/collections). Format datanya sama persis dengan respons dari [API Moota.co](https://app.moota.co/developer/docs).

Jika kamu ingin mengembalikan dalam bentuk array, cukup tambahkan method `toArray()` di akhir rantai penggunaan fungsi. Sebagai contoh:  

```
Moota::profile()->toArray();  

// contoh nilai kembalian dalam bentuk array
array:5 [▼
    "name" => "Dedy Yugo Purwanto"
    "email" => "xx-sample@email-fake.com"
    "address" => null
    "city" => null
    "join_at" => "2018-09-19 16:47:42"
]

is_array(Moota::mutation($bankId)->month()->toArray()) // true
```

## Cache

Beberapa metode yang tersedia di facade mendukung cache. Hal ini memungkinkan respons dari API disimpan pada cache di lokal server. Ketika fungsi yang sama dipanggil untuk kesekian kali, aplikasi tidak perlu memanggil secara langsung ke API Moota.

Untuk mengaktifkan fitur cache, kamu hanya perlu menambahkan metode `cache()` pada pemanggilan metode tertentu. Sebagai contoh:

```
Moota::cache()->profile();

// cache selama 24 jam
Moota::cache(60 * 24)->banks();

// cache selama 15 menit
Moota::cache(15)->bank($bankId = 'XXX123');
```

Argumen pertama pada cache bertipe data `integer` dan mengindikasikan berapa lama (dalam satuan menit) data akan disimpan ke dalam cache.

Khusus untuk method `amount()` dan `description()` tidak mendukung fitur cache. Dan, tentu saja kamu dapat menyimpannya secara manual menggunakan [cache bawaan Laravel](https://laravel.com/docs/5.6/cache).
 
## Lisensi  

MIT.
