/* ==========================================================================
   laporan.js  --  Interaksi halaman Laporan & Rekapitulasi (E-PKL)
   Murni frontend. Progressive enhancement: halaman tetap utuh tanpa file ini.
   ========================================================================== */
(function () {
    'use strict';

    var root = document.querySelector('.lp-page');
    if (!root) { return; }

    var reduce   = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    var MAX_ROWS = 12; // harus sama dengan $maxRows di index.php

    var periode = document.getElementById('lpPeriode');
    var jurusan = document.getElementById('lpJurusan');
    var refresh = document.getElementById('lpRefresh');
    var tbody   = document.getElementById('lpRows');
    var count   = document.getElementById('lpCount');
    var toasts  = document.getElementById('lpToasts');
    var history = document.getElementById('lpHistory');
    var exportLinks  = Array.prototype.slice.call(document.querySelectorAll('.js-export'));
    var scopePeriode = Array.prototype.slice.call(document.querySelectorAll('.js-scope-periode'));
    var scopeJurusan = Array.prototype.slice.call(document.querySelectorAll('.js-scope-jurusan'));
    var rows = [];

    /* ------------------------------------------------------------------
       Toast
       ------------------------------------------------------------------ */
    function toast(message, kind) {
        var el = document.createElement('div');
        var ic = document.createElement('i');
        var tx = document.createElement('span');

        el.className = 'lp-toast lp-toast--' + (kind || 'info');
        el.setAttribute('role', 'status');
        ic.className = kind === 'success' ? 'fas fa-check-circle'
                     : kind === 'error'   ? 'fas fa-exclamation-circle'
                     : 'fas fa-info-circle';
        tx.textContent = message;
        el.appendChild(ic);
        el.appendChild(tx);
        toasts.appendChild(el);

        requestAnimationFrame(function () { el.classList.add('is-in'); });
        setTimeout(function () {
            el.classList.remove('is-in');
            setTimeout(function () { if (el.parentNode) { el.parentNode.removeChild(el); } }, 300);
        }, 3200);
    }

    /* ------------------------------------------------------------------
       Angka berhitung naik (count-up). Nilai akhir sudah ada di HTML.
       ------------------------------------------------------------------ */
    function formatNumber(value, decimals, separator) {
        var s = value.toFixed(decimals);
        return separator ? s.replace(/\B(?=(\d{3})+(?!\d))/g, ',') : s;
    }

    function countUp(el) {
        var target    = parseFloat(el.getAttribute('data-count'));
        var decimals  = parseInt(el.getAttribute('data-dec') || '0', 10);
        var separator = el.getAttribute('data-sep') === '1';
        var start     = null;
        var duration  = 1100;

        if (isNaN(target) || reduce) { return; }

        function step(ts) {
            if (start === null) { start = ts; }
            var p = Math.min((ts - start) / duration, 1);
            var eased = 1 - Math.pow(1 - p, 3);
            el.textContent = formatNumber(p === 1 ? target : target * eased, decimals, separator);
            if (p < 1) { requestAnimationFrame(step); }
        }
        el.textContent = formatNumber(0, decimals, separator);
        requestAnimationFrame(step);
    }

    if ('IntersectionObserver' in window && !reduce) {
        var countObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    Array.prototype.forEach.call(entry.target.querySelectorAll('.js-count'), countUp);
                    countObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.3 });
        Array.prototype.forEach.call(root.querySelectorAll('.lp-card'), function (card) { countObserver.observe(card); });

        /* Bagian yang masih di bawah layar muncul saat di-scroll. */
        var lazyObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.remove('lp-pending');
                    lazyObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.08 });
        Array.prototype.forEach.call(root.querySelectorAll('.lp-lazy'), function (el) {
            if (el.getBoundingClientRect().top > window.innerHeight * 0.9) {
                el.classList.add('lp-pending');
                lazyObserver.observe(el);
            }
        });
    }

    /* ------------------------------------------------------------------
       Efek tombol (ripple) & sorot mengikuti kursor pada kartu
       ------------------------------------------------------------------ */
    root.addEventListener('pointerdown', function (ev) {
        var btn = ev.target.closest ? ev.target.closest('.lp-btn') : null;
        if (!btn || reduce) { return; }

        var r    = btn.getBoundingClientRect();
        var size = Math.max(r.width, r.height) * 2;
        var dot  = document.createElement('span');

        dot.className = 'lp-ripple';
        dot.style.width  = size + 'px';
        dot.style.height = size + 'px';
        dot.style.left   = (ev.clientX - r.left - size / 2) + 'px';
        dot.style.top    = (ev.clientY - r.top - size / 2) + 'px';
        btn.appendChild(dot);
        setTimeout(function () { if (dot.parentNode) { dot.parentNode.removeChild(dot); } }, 650);
    });

    Array.prototype.forEach.call(root.querySelectorAll('.lp-card'), function (card) {
        card.addEventListener('pointermove', function (ev) {
            var r = card.getBoundingClientRect();
            card.style.setProperty('--mx', (ev.clientX - r.left) + 'px');
            card.style.setProperty('--my', (ev.clientY - r.top) + 'px');
        });
    });

    /* ------------------------------------------------------------------
       Tabel riwayat: filter + animasi baris
       ------------------------------------------------------------------ */
    function collectRows() {
        rows = Array.prototype.slice.call(tbody.querySelectorAll('tr[data-jurusan]'));
    }

    function selectedText(sel) { return sel.options[sel.selectedIndex].text; }

    /** Tandai chip yang sesuai filter aktif & sesuaikan tautannya. */
    function syncChips() {
        Array.prototype.forEach.call(tbody.querySelectorAll('.js-filter-chip'), function (chip) {
            var isPeriode = chip.getAttribute('data-filter') === 'periode';
            var value     = chip.getAttribute('data-value');
            var sel       = isPeriode ? periode : jurusan;

            chip.classList.toggle('is-active', value === sel.value);
            chip.setAttribute('href', '?page=laporan'
                + '&periode=' + encodeURIComponent(isPeriode ? value : periode.value)
                + '&jurusan=' + encodeURIComponent(isPeriode ? jurusan.value : value)
                + '#lpHistory');
        });
    }

    function applyFilter(animate) {
        var shown = 0;
        var empty = document.getElementById('lpEmpty');

        rows.forEach(function (row) {
            var okPeriode = row.getAttribute('data-periode') === periode.value;
            var okJurusan = jurusan.value === ''
                || row.getAttribute('data-jurusan') === 'all'
                || row.getAttribute('data-jurusan') === jurusan.value;
            var show = okPeriode && okJurusan && shown < MAX_ROWS;

            row.hidden = !show;
            row.classList.remove('lp-row-in');
            if (show) {
                if (animate && !reduce) {
                    row.style.setProperty('--i', shown);
                    void row.offsetWidth; // mulai ulang animasi
                    row.classList.add('lp-row-in');
                }
                shown++;
            }
        });

        if (empty) { empty.classList.toggle('is-visible', shown === 0); }
        count.textContent = shown === 0 ? 'Tidak ada berkas' : 'Menampilkan ' + shown + ' berkas terbaru';
        syncChips();
    }

    function syncExport() {
        exportLinks.forEach(function (a) {
            a.setAttribute('href',
                '?page=laporan-export&type=' + a.getAttribute('data-type') +
                '&format=' + a.getAttribute('data-format') +
                '&periode=' + encodeURIComponent(periode.value) +
                '&jurusan=' + encodeURIComponent(jurusan.value));
        });

        scopePeriode.forEach(function (el) { el.textContent = selectedText(periode); });
        scopeJurusan.forEach(function (el) { el.textContent = selectedText(jurusan); });
        scopePeriode.concat(scopeJurusan).forEach(function (el) {
            el.classList.remove('lp-flash');
            void el.offsetWidth;
            el.classList.add('lp-flash');
        });

        if (window.history && window.history.replaceState) {
            window.history.replaceState(null, '',
                '?page=laporan&periode=' + encodeURIComponent(periode.value) +
                '&jurusan=' + encodeURIComponent(jurusan.value));
        }
    }

    function onFilterChange() {
        syncExport();
        applyFilter(true);
    }

    /** Ambil ulang daftar riwayat dari server tanpa memuat ulang halaman. */
    function refreshHistory(highlightNewest) {
        return fetch(window.location.href, { credentials: 'same-origin', cache: 'no-store' })
            .then(function (res) { return res.text(); })
            .then(function (html) {
                var doc  = new DOMParser().parseFromString(html, 'text/html');
                var next = doc.getElementById('lpRows');
                if (!next) { return; }

                tbody.innerHTML = next.innerHTML;
                collectRows();
                applyFilter(true);

                if (highlightNewest) {
                    var first = rows.filter(function (r) { return !r.hidden; })[0];
                    if (first) { first.classList.add('is-new'); }
                }
            })
            .catch(function () { toast('Gagal memuat ulang riwayat.', 'error'); });
    }

    periode.addEventListener('change', onFilterChange);
    jurusan.addEventListener('change', onFilterChange);

    /* Klik chip Periode / Jurusan pada baris -> langsung menuju hasil filternya */
    tbody.addEventListener('click', function (ev) {
        var chip = ev.target.closest ? ev.target.closest('.js-filter-chip') : null;
        if (!chip) { return; }
        ev.preventDefault();

        var sel     = chip.getAttribute('data-filter') === 'periode' ? periode : jurusan;
        var changed = sel.value !== chip.getAttribute('data-value');

        sel.value = chip.getAttribute('data-value');
        if (changed) { onFilterChange(); }

        if (history && history.scrollIntoView) {
            history.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' });
        }
        toast('Menampilkan ' + selectedText(periode) + ' \u2022 ' + selectedText(jurusan), 'info');
    });

    refresh.addEventListener('click', function () {
        refresh.classList.remove('is-spinning');
        void refresh.offsetWidth;
        refresh.classList.add('is-spinning');
        refreshHistory(false).then(function () { toast('Riwayat diperbarui.', 'info'); });
    });

    /* ------------------------------------------------------------------
       Tombol Unduh Excel / Cetak PDF: umpan balik bertahap
       ------------------------------------------------------------------ */
    exportLinks.forEach(function (btn) {
        btn.addEventListener('click', function (ev) {
            if (btn.classList.contains('is-busy') || btn.classList.contains('is-done')) {
                ev.preventDefault();
                return;
            }
            // Biarkan browser menjalankan unduhan / membuka tab baru seperti biasa.

            var isPdf = btn.getAttribute('data-format') === 'pdf';
            var label = btn.querySelector('.lp-btn__label');
            var icon  = btn.querySelector('i');
            var oldLabel = label.textContent;
            var oldIcon  = icon.className;

            btn.classList.add('is-busy');
            label.textContent = 'Menyiapkan...';
            icon.className = 'fas fa-circle-notch fa-spin';

            setTimeout(function () {
                btn.classList.remove('is-busy');
                btn.classList.add('is-done');
                label.textContent = isPdf ? 'Terbuka' : 'Terunduh';
                icon.className = 'fas fa-check';
                toast(btn.getAttribute('data-title') + (isPdf ? ' dibuka untuk dicetak.' : ' berhasil diunduh.'), 'success');
            }, 1000);

            setTimeout(function () { refreshHistory(true); }, 2200);

            setTimeout(function () {
                btn.classList.remove('is-done');
                label.textContent = oldLabel;
                icon.className = oldIcon;
            }, 3200);
        });
    });

    collectRows();
    applyFilter(!reduce);
})();