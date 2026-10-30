// Dialog: buka dengan data-modal-open="idModal", tutup dengan data-modal-close
document.addEventListener('click', function (e) {
  var opener = e.target.closest('[data-modal-open]');
  if (opener) {
    var m = document.getElementById(opener.getAttribute('data-modal-open'));
    if (m) m.classList.add('show');
    return;
  }
  if (e.target.closest('[data-modal-close]') || e.target.classList.contains('modal')) {
    var box = e.target.closest('.modal');
    if (box) box.classList.remove('show');
  }
});

// Esc menutup dialog
document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') document.querySelectorAll('.modal.show').forEach(function (m) { m.classList.remove('show'); });
});

// Toast hilang otomatis
document.querySelectorAll('.toast').forEach(function (t) {
  setTimeout(function () { t.classList.add('hide'); setTimeout(function () { t.remove(); }, 400); }, 4000);
});

// Jam & tanggal live (WIB) - dipakai di Beranda: [data-clock] dan [data-clock-date]
(function () {
  var clock = document.querySelector('[data-clock]');
  var date = document.querySelector('[data-clock-date]');
  if (!clock) return;
  function tick() {
    var n = new Date();
    clock.textContent = n.toLocaleTimeString('id-ID', { timeZone: 'Asia/Jakarta', hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' }).replace(/\./g, ':') + ' WIB';
    if (date) date.textContent = n.toLocaleDateString('id-ID', { timeZone: 'Asia/Jakarta', weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
  }
  tick();
  setInterval(tick, 1000);
})();
