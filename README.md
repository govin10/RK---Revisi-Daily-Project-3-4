# Sistem Pelacakan Alumni Berbasis OSINT

## Deskripsi

Aplikasi ini digunakan untuk melacak data alumni dengan memanfaatkan teknik OSINT (Open Source Intelligence).
Sistem dibuat menggunakan PHP untuk web utama dan Python untuk proses pencarian data dari internet.

---

## Fitur

* Login
* Tambah data alumni
* Import & export data
* Pelacakan alumni (OSINT)
* Pelacakan massal (bulk)
* Dashboard statistik

---

## Pengujian Sistem

### Pengujian Fitur

| No | Fitur         | Skenario          | Hasil                       | Status   |
| -- | ------------- | ----------------- | --------------------------- | -------- |
| 1  | Login         | Input data benar  | Berhasil masuk dashboard    | Berhasil |
| 2  | Login         | Password salah    | Muncul pesan error          | Berhasil |
| 3  | Tambah Alumni | Isi semua data    | Data tersimpan              | Berhasil |
| 4  | Import Data   | Upload file CSV   | Data berhasil masuk         | Berhasil |
| 5  | OSINT         | Input nama alumni | Data hasil pencarian tampil | Berhasil |
| 6  | OSINT         | Input kosong      | Muncul validasi             | Berhasil |
| 7  | Export        | Klik export       | File terdownload            | Berhasil |
| 8  | Bulk Tracking | Banyak data       | Diproses satu per satu      | Berhasil |

---

### Pengujian API

| No | Endpoint | Input  | Output               | Status   |
| -- | -------- | ------ | -------------------- | -------- |
| 1  | /osint   | nama   | JSON hasil pencarian | Berhasil |
| 2  | /osint   | kosong | Error                | Berhasil |

---

### Pengujian Waktu Respon

| No | Skenario      | Waktu       | Keterangan          |
| -- | ------------- | ----------- | ------------------- |
| 1  | 1 data alumni | ±3 detik    | Normal              |
| 2  | Multi sumber  | ±5–10 detik | Masih wajar         |
| 3  | Banyak data   | >10 detik   | Tergantung jaringan |

---

## Cara Menjalankan

1. Upload file PHP ke hosting
2. Import database
3. Jalankan Python OSINT (API)
4. Akses melalui browser

---

## Catatan

Karena keterbatasan hosting gratis (hanya mendukung PHP), proses OSINT dengan Python dijalankan sebagai service terpisah dan diakses melalui API.

---

## Author

Nama: (isi nama kamu)
NIM: (isi NIM kamu)
