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

// Show the selected attachment name on activity forms.
document.querySelectorAll('[data-file-input]').forEach(function (input) {
  input.addEventListener('change', function () {
    var fileName = document.getElementById(input.getAttribute('data-file-name'));
    if (fileName && input.files && input.files.length > 0) {
      fileName.textContent = input.files[0].name;
    }
  });
});

// Filter history by record type, week, and month.
(function () {
  var list = document.getElementById('history-list');
  if (!list) return;

  var records = Array.prototype.slice.call(list.querySelectorAll('[data-history-item]'));
  var weekSelect = document.getElementById('history-week');
  var monthSelect = document.getElementById('history-month');
  var emptyState = document.getElementById('history-empty');
  var historyPage = list.closest('[data-pkl-start-date]');
  var activeType = 'all';

  function parseDate(value) {
    var parts = value.split('-').map(Number);
    if (parts.length !== 3 || parts.some(function (part) { return !Number.isInteger(part); })) return null;
    var date = new Date(Date.UTC(parts[0], parts[1] - 1, parts[2]));
    return date.getUTCFullYear() === parts[0] &&
      date.getUTCMonth() === parts[1] - 1 &&
      date.getUTCDate() === parts[2] ? date : null;
  }

  function formatDate(date) {
    return date.toLocaleDateString('id-ID', {
      timeZone: 'UTC',
      day: 'numeric',
      month: 'short',
      year: 'numeric'
    });
  }

  var parsedRecords = records.map(function (record) {
    return { element: record, date: parseDate(record.getAttribute('data-date')) };
  }).filter(function (record) {
    return record.date !== null;
  });
  var earliestAttendance = parsedRecords
    .filter(function (record) { return record.element.dataset.type === 'attendance'; })
    .reduce(function (earliest, record) {
      return !earliest || record.date < earliest ? record.date : earliest;
    }, null);
  var earliestRecord = parsedRecords.reduce(function (earliest, record) {
    return !earliest || record.date < earliest ? record.date : earliest;
  }, null);
  var requestedStart = historyPage ? parseDate(historyPage.getAttribute('data-pkl-start-date') || '') : null;
  var startDate = requestedStart && (!earliestAttendance || requestedStart <= earliestAttendance)
    ? requestedStart
    : (earliestAttendance || earliestRecord);
  var weeks = new Map();

  if (startDate) {
    parsedRecords.forEach(function (record) {
      var elapsedDays = Math.floor((record.date.getTime() - startDate.getTime()) / 86400000);
      if (elapsedDays < 0) return;

      var weekNumber = Math.floor(elapsedDays / 7) + 1;
      var weekStart = new Date(startDate);
      weekStart.setUTCDate(weekStart.getUTCDate() + ((weekNumber - 1) * 7));
      var weekEnd = new Date(weekStart);
      weekEnd.setUTCDate(weekEnd.getUTCDate() + 6);
      record.element.dataset.week = String(weekNumber);
      weeks.set(weekNumber, {
        start: weekStart,
        end: weekEnd
      });
    });
  }

  records.forEach(function (record) {
    var date = parseDate(record.getAttribute('data-date') || '');
    if (date) record.dataset.month = String(date.getUTCMonth() + 1);
  });

  Array.from(weeks.entries()).sort(function (a, b) {
    return a[0] - b[0];
  }).forEach(function (week) {
    var option = document.createElement('option');
    option.value = String(week[0]);
    option.textContent = 'Minggu ke-' + week[0] + ' (' +
      formatDate(week[1].start) + ' - ' + formatDate(week[1].end) + ')';
    weekSelect.appendChild(option);
  });

  function applyFilters() {
    var week = weekSelect.value;
    var month = monthSelect.value;
    var visibleCount = 0;

    records.forEach(function (record) {
      var isVisible = (activeType === 'all' || record.dataset.type === activeType) &&
        (week === 'all' || record.dataset.week === week) &&
        (month === 'all' || record.dataset.month === month);

      record.hidden = !isVisible;
      if (isVisible) visibleCount++;
    });

    emptyState.hidden = visibleCount > 0;
  }

  document.querySelectorAll('[data-history-type]').forEach(function (button) {
    button.addEventListener('click', function () {
      activeType = button.getAttribute('data-history-type');
      document.querySelectorAll('[data-history-type]').forEach(function (tab) {
        var isActive = tab === button;
        tab.classList.toggle('is-active', isActive);
        tab.setAttribute('aria-pressed', String(isActive));
      });
      applyFilters();
    });
  });

  weekSelect.addEventListener('change', applyFilters);
  monthSelect.addEventListener('change', applyFilters);
})();

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
