document.addEventListener('DOMContentLoaded', function () {
  const menuToggle = document.getElementById('menuToggle');
  const sidebar = document.getElementById('sidebar');
  if (menuToggle && sidebar) {
    menuToggle.addEventListener('click', function () {
      sidebar.classList.toggle('open');
    });
  }

  // ambil data dashboard dari server
  fetch('dashboard-customer.php')
    .then(function (res) {
      return res.json();
    })
    .then(function (data) {
      if (!data.success) {
        // belum login, lempar ke halaman login
        window.location.href = 'login.html';
        return;
      }

      document.getElementById('greeting').textContent = 'Halo, ' + data.nama + ' \u{1F44B}';

      const statusCard = document.getElementById('statusCard');
      if (!data.hasActiveOrder) {
        statusCard.style.display = 'none';
      } else {
        let stepsHtml = '';
        for (let i = 0; i < data.statusSteps.length; i++) {
          let kelas = '';
          if (i < data.currentStep) {
            kelas = 'done';
          } else if (i === data.currentStep) {
            kelas = 'active';
          }
          stepsHtml += '<div class="step ' + kelas + '"><span class="dot"></span><span class="label">' + data.statusSteps[i] + '</span></div>';
        }
        document.getElementById('steps').innerHTML = stepsHtml;
      }

      const tbody = document.getElementById('recentBody');
      if (data.recent.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4">Belum ada transaksi.</td></tr>';
      } else {
        let rows = '';
        for (let i = 0; i < data.recent.length; i++) {
          const t = data.recent[i];
          rows += '<tr><td>' + t.tanggal + '</td><td>' + t.layanan + '</td><td>' + t.total + '</td><td><span class="badge">' + t.status + '</span></td></tr>';
        }
        tbody.innerHTML = rows;
      }
    });
});
