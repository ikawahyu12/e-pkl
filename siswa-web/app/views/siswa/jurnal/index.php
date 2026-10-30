<?php /* View: Jurnal - area konten. Ganti kartu di bawah dengan komponen fitur kamu. */ ?>
<?= ui_card(ui_empty(
    'jurnal',
    'Halaman Jurnal sedang disiapkan',
    'Fitur ini dikerjakan oleh: anggota Jurnal. Isi area ini dengan komponen fiturnya.',
    ui_button('Kembali ke Beranda', ['href' => url('beranda'), 'variant' => 'outline', 'block' => false, 'icon' => 'back'])
)) ?>
