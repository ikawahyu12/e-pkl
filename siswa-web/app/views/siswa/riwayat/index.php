<?php /* View: Riwayat - area konten. Ganti kartu di bawah dengan komponen fitur kamu. */ ?>
<?= ui_card(ui_empty(
    'riwayat',
    'Halaman Riwayat sedang disiapkan',
    'Fitur ini dikerjakan oleh: anggota Riwayat. Isi area ini dengan komponen fiturnya.',
    ui_button('Kembali ke Beranda', ['href' => url('beranda'), 'variant' => 'outline', 'block' => false, 'icon' => 'back'])
)) ?>
