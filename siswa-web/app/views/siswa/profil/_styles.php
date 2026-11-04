<?php
/** Style + ikon halaman profil. CSS pakai prefix "pf-" agar tidak bentrok. Ikon: echo pf_icon('user'); */
if (!function_exists('pf_icon')) {
    function pf_icon(string $name, int $size = 18): string
    {
        $paths = [
            'user'      => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
            'briefcase' => '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
            'phone'     => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/>',
            'mail'      => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 5L2 7"/>',
            'pin'       => '<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
            'layers'    => '<path d="m12 2 10 5-10 5L2 7z"/><path d="m2 17 10 5 10-5"/><path d="m2 12 10 5 10-5"/>',
            'usercheck' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m16 11 2 2 4-4"/>',
            'calendar'  => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
            'camera'    => '<path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/>',
            'lock'      => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
            'logout'    => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',
            'chevron'   => '<path d="m9 18 6-6-6-6"/>',
        ];
        return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? '') . '</svg>';
    }
}
?>
<style>
    .pf-stack { display: flex; flex-direction: column; gap: 16px; }

    /* Kartu */
    .pf-card { background: #fff; border-radius: 20px; border: 1px solid #e6edf7; padding: 20px 22px; box-shadow: 0 2px 10px rgba(30, 64, 175, .04); }
    .pf-card-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
    .pf-card-title { font-size: 16px; font-weight: 700; margin: 0; }
    .pf-link { font-size: 14px; font-weight: 700; color: #1d6fe8; text-decoration: none; }

    /* Hero */
    .pf-hero { position: relative; overflow: hidden; border-radius: 20px; background: #fff; border: 1px solid #e6edf7; box-shadow: 0 2px 10px rgba(30, 64, 175, .04); }
    .pf-banner { height: 84px; background: linear-gradient(100deg, #1d4ed8 0%, #2b8cff 100%); }
    .pf-hero-body { padding: 0 20px 22px; margin-top: -48px; text-align: center; }
    .pf-avatar-wrap { position: relative; width: 96px; height: 96px; margin: 0 auto 10px; }
    .pf-avatar { width: 96px; height: 96px; border-radius: 50%; border: 4px solid #fff; box-sizing: border-box; object-fit: cover; display: block; background: #e2e8f0; }
    .pf-avatar-btn { position: absolute; right: 0; bottom: 2px; width: 30px; height: 30px; border-radius: 50%; background: #1d6fe8; border: 3px solid #fff; color: #fff; display: flex; align-items: center; justify-content: center; text-decoration: none; }
    .pf-hero h2 { font-size: 20px; font-weight: 700; margin: 6px 0 4px; }
    .pf-hero p { font-size: 14px; opacity: .6; margin: 0; }

    /* Baris info dengan ikon */
    .pf-row { display: flex; align-items: flex-start; gap: 14px; padding: 12px 0; border-bottom: 1px solid #eaf0f8; }
    .pf-row:first-of-type { padding-top: 0; }
    .pf-row:last-child { border-bottom: 0; padding-bottom: 0; }
    .pf-row .pf-ico { color: #4b5d78; margin-top: 2px; flex: none; display: flex; }
    .pf-row small { display: block; font-size: 13px; color: #5b6b85; }
    .pf-row strong { display: block; font-size: 15px; font-weight: 700; margin-top: 3px; line-height: 1.35; }

    .pf-badge { font-size: 12px; font-weight: 700; padding: 6px 14px; border-radius: 99px; background: #d9f7e5; color: #12935a; }
    .pf-badge-selesai { background: #dbeafe; color: #1d4ed8; }
    .pf-badge-belum { background: #fef3c7; color: #b45309; }

    /* Menu bawah */
    .pf-menu { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-radius: 18px; background: #fff; border: 1px solid #e6edf7; text-decoration: none; color: inherit; font-size: 15px; font-weight: 600; }
    .pf-menu .l { display: flex; align-items: center; gap: 14px; }
    .pf-menu.pf-danger { color: #e11d48; }

    /* Form */
    .pf-group { margin-bottom: 14px; }
    .pf-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
    .pf-input { width: 100%; padding: 13px 14px; font: inherit; font-size: 14px; border-radius: 12px; border: 1.5px solid #bcd9fb; background: #fff; outline: none; box-sizing: border-box; }
    .pf-input:focus { border-color: #2b8cff; box-shadow: 0 0 0 3px rgba(43, 140, 255, .15); }
    .pf-input[readonly] { background: #f1f5f9; border-color: #e2e8f0; color: #94a3b8; }
    textarea.pf-input { min-height: 96px; resize: vertical; }

    .pf-btn { display: block; width: 100%; padding: 14px; border-radius: 12px; font: inherit; font-size: 14px; font-weight: 600; text-align: center; text-decoration: none; cursor: pointer; box-sizing: border-box; }
    .pf-btn-primary { border: 0; color: #fff; background: linear-gradient(90deg, #1d4ed8, #2b8cff); }
    .pf-btn-outline { border: 1.5px solid #cbd5e1; color: #475569; background: #fff; }
    .pf-btn-sm { display: inline-block; width: auto; padding: 6px 16px; font-size: 12px; border-radius: 8px; border: 1.2px solid #1d4ed8; color: #1d4ed8; background: #fff; font-weight: 600; cursor: pointer; }
    .pf-actions { display: flex; flex-direction: column; gap: 10px; }

    .pf-photo { text-align: center; }
    .pf-photo img { width: 72px; height: 72px; border-radius: 50%; object-fit: cover; border: 3px solid #e2e8f0; display: block; margin: 0 auto 12px; }

    .pf-pw { position: relative; }
    .pf-pw .pf-input { background: #eaf1ff; border-color: transparent; padding-right: 76px; }
    .pf-pw button { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); border: 0; background: none; cursor: pointer; font-size: 12px; font-weight: 700; color: #1d4ed8; }

    .pf-alert { padding: 12px 16px; border-radius: 12px; font-size: 13px; font-weight: 600; }
    .pf-alert-success { background: #dcfce7; color: #16a34a; }
    .pf-alert-error { background: #ffe4e6; color: #e11d48; }

    @media (min-width: 900px) {
        .pf-overview {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            align-items: start;
        }

        .pf-overview > .pf-hero,
        .pf-overview > .pf-menu {
            grid-column: 1 / -1;
        }

        .pf-actions {
            flex-direction: row-reverse;
        }

        .pf-actions > * {
            width: auto;
            min-width: 180px;
        }
    }

    @media (max-width: 560px) {
        .pf-card {
            padding: 17px;
            border-radius: 16px;
        }

        .pf-row {
            gap: 10px;
        }

        .pf-row strong {
            overflow-wrap: anywhere;
        }

        .pf-card-head {
            align-items: flex-start;
            gap: 10px;
        }

        .pf-badge {
            white-space: nowrap;
        }
    }
</style>