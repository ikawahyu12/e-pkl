# E-PKL - Web Siswa (PHP MVC)

## Menjalankan
- **Laragon/XAMPP**: taruh folder di `www`/`htdocs`, nyalakan Apache, buka `http://localhost/<nama-folder>/`
- **PHP built-in**: `php -S localhost:8000 -t public` lalu buka `http://localhost:8000`

## Struktur
```
public/            index.php (front controller), .htaccess, assets/
app/config/        config.php (BASE_URL, DEV_MODE, DB)
app/core/          App.php (router), Controller.php (base)
app/helpers/ui.php komponen UI bersama (ui_button, ui_card, ui_stat, ui_input, ui_modal, flash_*)
app/controllers/   satu controller per fitur
app/models/        model database
app/views/         layouts/, components/, siswa/<fitur>/
```

## Navigasi
Tidak ada menu bawah/sidebar (sesuai desain web siswa). Pindah fitur lewat **kartu menu di Beranda**.
Setiap halaman dalam punya tombol **Kembali** ke Beranda, dan header punya tombol profil.
Menu fitur diatur di `menu_items()` pada `app/helpers/ui.php`.

## Aturan tim
- Routing otomatis: `/jurnal/kirim` -> `JurnalController::kirim()`
- Tambah halaman: `$this->render('siswa/jurnal/form', ['title'=>'Jurnal','showBack'=>true]);`
- Pakai komponen di `helpers/ui.php`; jangan bikin gaya sendiri. Butuh komponen baru? Tambahkan di sana + CSS di `app.css`.
- Hanya edit controller/view/model fitur milik sendiri.
- `DEV_MODE=true` melewati login selama pengembangan.

## Git
```
git checkout -b fitur/nama-fitur
git add . && git commit -m "feat: ..."
git push origin fitur/nama-fitur   # lalu Pull Request
```
