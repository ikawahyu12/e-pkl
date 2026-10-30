<?php /* View: Izin - area konten. Ganti kartu di bawah dengan komponen fitur kamu. */ ?>
<?= ui_card(ui_empty(
    'izin',
    'Halaman Izin sedang disiapkan',
    'Fitur ini dikerjakan oleh: anggota Izin. Isi area ini dengan komponen fiturnya.',
    ui_button('Kembali ke Beranda', ['href' => url('beranda'), 'variant' => 'outline', 'block' => false, 'icon' => 'back'])
)) ?>
