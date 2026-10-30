<?php
/**
 * KOMPONEN UI BERSAMA - dipakai semua anggota tim.
 * Semua fungsi mengembalikan string HTML, pakai dengan:  <?= ui_button('Simpan') ?>
 */

// ---------- util ----------
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function url($path = '') { return BASE_URL . '/' . ltrim($path, '/'); }
function asset($path) { return ASSET_URL . '/' . ltrim($path, '/'); }

// Data siswa yang sedang login (sementara dummy bila belum ada session login)
function current_user()
{
    $u    = $_SESSION['user'] ?? [];
    $nama = $u['nama'] ?? 'Rian Hidayat';
    $p    = preg_split('/\s+/', trim($nama));
    $ini  = strtoupper(substr($p[0], 0, 1) . (isset($p[1]) ? substr($p[1], 0, 1) : ''));
    return ['nama' => $nama, 'inisial' => $ini, 'instansi' => $u['instansi'] ?? 'PT Sinergi Teknologi Indonesia'];
}

// ---------- ikon (inline SVG) ----------
function icon($name, $size = 22)
{
    $p = [
        'home'     => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
        'presensi' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
        'jurnal'   => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>',
        'izin'     => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>',
        'riwayat'  => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'user'     => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'back'     => '<polyline points="15 18 9 12 15 6"/>',
        'arrow'    => '<line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>',
        'check'    => '<polyline points="20 6 9 17 4 12"/>',
        'alert'    => '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
        'tools'    => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
    ];
    $d = $p[$name] ?? '';
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
}

// ---------- menu fitur (dipakai kartu menu di Beranda) ----------
// tone: blue | teal | orange | purple | pink
function menu_items()
{
    return [
        'presensi' => ['label' => 'Presensi', 'desc' => 'Check in & check out harian', 'icon' => 'presensi', 'tone' => 'blue'],
        'jurnal'   => ['label' => 'Isi Jurnal', 'desc' => 'Catat kegiatan magang harian', 'icon' => 'jurnal', 'tone' => 'teal'],
        'izin'     => ['label' => 'Ajukan Izin', 'desc' => 'Izin atau sakit, lengkap bukti', 'icon' => 'izin', 'tone' => 'orange'],
        'riwayat'  => ['label' => 'Riwayat', 'desc' => 'Rekap presensi & jurnal', 'icon' => 'riwayat', 'tone' => 'purple'],
        'profil'   => ['label' => 'Profil', 'desc' => 'Data diri & instansi PKL', 'icon' => 'user', 'tone' => 'pink'],
    ];
}

// kartu menu besar di dashboard
function ui_menu_card($key, $index = 0)
{
    $m = menu_items()[$key] ?? null;
    if (!$m) return '';
    return '<a class="menu-card reveal" style="--d:' . ((int)$index * 70) . 'ms" href="' . e(url($key)) . '">'
        . '<span class="tile tone-' . e($m['tone']) . '">' . icon($m['icon'], 24) . '</span>'
        . '<span class="mc-body"><strong>' . e($m['label']) . '</strong><small>' . e($m['desc']) . '</small></span>'
        . '<span class="mc-arrow">' . icon('arrow', 18) . '</span></a>';
}

// ---------- tombol ----------
// ui_button('Simpan', ['variant'=>'primary|outline|ghost|danger|light', 'href'=>url('x'), 'type'=>'submit', 'block'=>true, 'icon'=>'check', 'attrs'=>'data-modal-open="m1"'])
function ui_button($label, $o = [])
{
    $o += ['variant' => 'primary', 'href' => null, 'type' => 'button', 'block' => true, 'attrs' => '', 'icon' => ''];
    $cls = 'btn btn-' . $o['variant'] . ($o['block'] ? ' btn-block' : '');
    $inner = ($o['icon'] ? icon($o['icon'], 18) : '') . '<span>' . e($label) . '</span>';
    if ($o['href']) {
        return '<a class="' . $cls . '" href="' . e($o['href']) . '" ' . $o['attrs'] . '>' . $inner . '</a>';
    }
    return '<button type="' . e($o['type']) . '" class="' . $cls . '" ' . $o['attrs'] . '>' . $inner . '</button>';
}

// ---------- kartu ----------
// $body = HTML (sudah aman, escape sendiri bila dari user)
function ui_card($body, $title = null, $o = [])
{
    $o += ['class' => '', 'action' => ''];
    $head = $title !== null
        ? '<div class="card-head"><h3>' . e($title) . '</h3>' . $o['action'] . '</div>' : '';
    return '<section class="card reveal ' . e($o['class']) . '">' . $head . $body . '</section>';
}

// kartu ringkasan kecil (Hadir 18 Hari, Terlambat 2 Kali, dst.)
// $color: blue | green | red | yellow   $icon: nama ikon (opsional)
function ui_stat($label, $value, $color = 'blue', $icon = '')
{
    $ic = $icon ? '<i class="stat-ic">' . icon($icon, 20) . '</i>' : '';
    return '<div class="stat stat-' . e($color) . '">' . $ic . '<div><span class="stat-label">' . e($label) . '</span><strong>' . e($value) . '</strong></div></div>';
}

// label status kecil: info | ok | warn | danger
function ui_badge($text, $type = 'info')
{
    return '<span class="pill pill-' . e($type) . '">' . e($text) . '</span>';
}

// tampilan kosong / belum tersedia
function ui_empty($icon, $title, $text = '', $actionHtml = '')
{
    return '<div class="empty"><span class="tile tone-blue">' . icon($icon, 26) . '</span><h3>' . e($title) . '</h3>'
        . ($text ? '<p>' . e($text) . '</p>' : '') . $actionHtml . '</div>';
}

// ---------- form ----------
function ui_input($o)
{
    $o += ['name' => '', 'label' => '', 'type' => 'text', 'value' => '', 'placeholder' => '', 'required' => false, 'readonly' => false, 'error' => '', 'id' => null];
    $id = $o['id'] ?: 'f_' . $o['name'];
    $h  = '<div class="field' . ($o['error'] ? ' has-error' : '') . '">';
    if ($o['label']) $h .= '<label for="' . e($id) . '">' . e($o['label']) . '</label>';
    $h .= '<input id="' . e($id) . '" name="' . e($o['name']) . '" type="' . e($o['type']) . '" value="' . e($o['value']) . '" placeholder="' . e($o['placeholder']) . '"'
        . ($o['required'] ? ' required' : '') . ($o['readonly'] ? ' readonly' : '') . '>';
    if ($o['error']) $h .= '<small class="err">' . e($o['error']) . '</small>';
    return $h . '</div>';
}

function ui_textarea($o)
{
    $o += ['name' => '', 'label' => '', 'value' => '', 'placeholder' => '', 'rows' => 4, 'required' => false, 'error' => ''];
    $id = 'f_' . $o['name'];
    $h  = '<div class="field' . ($o['error'] ? ' has-error' : '') . '">';
    if ($o['label']) $h .= '<label for="' . e($id) . '">' . e($o['label']) . '</label>';
    $h .= '<textarea id="' . e($id) . '" name="' . e($o['name']) . '" rows="' . (int)$o['rows'] . '" placeholder="' . e($o['placeholder']) . '"' . ($o['required'] ? ' required' : '') . '>' . e($o['value']) . '</textarea>';
    if ($o['error']) $h .= '<small class="err">' . e($o['error']) . '</small>';
    return $h . '</div>';
}

function ui_select($name, $label, $options, $selected = '')
{
    $h = '<div class="field"><label for="f_' . e($name) . '">' . e($label) . '</label><select id="f_' . e($name) . '" name="' . e($name) . '">';
    foreach ($options as $val => $text) {
        $h .= '<option value="' . e($val) . '"' . ((string)$val === (string)$selected ? ' selected' : '') . '>' . e($text) . '</option>';
    }
    return $h . '</select></div>';
}

// area upload foto dokumentasi / surat bukti
function ui_upload($name, $label, $hint = 'JPG, PNG max 5MB')
{
    return '<div class="field"><label>' . e($label) . '</label>'
        . '<label class="upload" for="f_' . e($name) . '"><strong>Tarik &amp; lepas foto di sini</strong><small>' . e($hint) . '</small></label>'
        . '<input class="sr-only" id="f_' . e($name) . '" name="' . e($name) . '" type="file" accept="image/*,application/pdf"></div>';
}

// ---------- alert & notifikasi ----------
function ui_alert($msg, $type = 'danger')
{
    return '<div class="alert alert-' . e($type) . '" role="alert">' . icon('alert', 18) . '<span>' . e($msg) . '</span></div>';
}

// Flash message (toast). Di controller:  flash_set('success', 'Jurnal berhasil dikirim');
function flash_set($type, $msg) { $_SESSION['flash'] = ['type' => $type, 'msg' => $msg]; }

function flash_render()
{
    if (empty($_SESSION['flash'])) return '';
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return '<div class="toast toast-' . e($f['type']) . '" role="status">' . e($f['msg']) . '</div>';
}

// ---------- dialog / modal ----------
// Buka dengan tombol:  ui_button('Check In', ['attrs'=>'data-modal-open="m-ok"'])
// $type: success | warning | danger
function ui_modal($id, $title, $message, $o = [])
{
    $o += ['type' => 'success', 'confirm' => 'OK', 'confirmHref' => null, 'cancel' => null];
    $ic = $o['type'] === 'success' ? 'check' : 'alert';
    $h  = '<div class="modal" id="' . e($id) . '" role="dialog" aria-modal="true"><div class="modal-box">';
    $h .= '<div class="modal-ic modal-' . e($o['type']) . '">' . icon($ic, 28) . '</div>';
    $h .= '<h4>' . e($title) . '</h4><p>' . e($message) . '</p><div class="modal-actions">';
    if ($o['cancel']) $h .= ui_button($o['cancel'], ['variant' => 'outline', 'attrs' => 'data-modal-close']);
    $h .= $o['confirmHref']
        ? ui_button($o['confirm'], ['href' => $o['confirmHref']])
        : ui_button($o['confirm'], ['attrs' => 'data-modal-close']);
    return $h . '</div></div></div>';
}
