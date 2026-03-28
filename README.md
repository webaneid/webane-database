# Webane Database

Webane Database adalah plugin WordPress untuk pengelolaan database pesantren dan alumni dalam satu sistem yang ringan, modern, dan siap dikembangkan lebih lanjut.

Plugin ini dikembangkan oleh Webane Indonesia.
Website resmi: [https://webane.com](https://webane.com)

## Fitur Utama

- Database pesantren dan alumni dengan relasi data alamat, kontak, sosial media, dan pekerjaan.
- Form admin dan frontend untuk input serta pembaruan data.
- Dashboard frontend alumni dengan modul yang siap berkembang.
- Arsip publik pesantren dan alumni.
- Detail publik pesantren dan alumni.
- Statistik publik organisasi.
- Import wilayah Indonesia sampai provinsi, kabupaten, kecamatan, dan desa.
- Autocomplete reusable untuk field referensi panjang.
- Upload gambar terkontrol untuk pasphoto alumni dan photo andalan pesantren.
- Update plugin melalui GitHub Releases.

## Modul Saat Ini

- Pesantren
- Alumni
- Alamat
- Kontak
- Sosial Media
- Pekerjaan
- Statistik

## Roadmap Pengembangan

- Modul usaha
- Modul pendidikan
- Dashboard frontend yang lebih modular
- Statistik lanjutan dan visualisasi data yang lebih dalam
- Approval workflow dan kontrol publikasi yang lebih lengkap
- Penyempurnaan distribusi update plugin

## Rilis dan Update

Plugin ini membaca update dari GitHub Releases repository:

- Repository: [https://github.com/webaneid/webane-database](https://github.com/webaneid/webane-database)

Alur rilis yang dipakai:

1. Ubah versi plugin di `webane-database.php`.
2. Commit dan push ke branch `main`.
3. Buat tag versi, misalnya `v0.0.4`.
4. Push tag ke GitHub.
5. GitHub Actions otomatis akan:
   - memvalidasi versi plugin sama dengan tag,
   - membuat ZIP plugin dari repository,
   - membuat GitHub Release,
   - mengunggah asset `webane-database.zip`.

Dengan alur ini, ZIP release tidak perlu dibuat manual.

Di halaman plugin WordPress tersedia tombol `Check Update` untuk memaksa pengecekan update dari GitHub.
