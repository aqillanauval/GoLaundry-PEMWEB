document.addEventListener('DOMContentLoaded', function () {
  const menuToggle = document.getElementById('menuToggle');
  const sidebar = document.getElementById('sidebar');
  if (menuToggle && sidebar) {
    menuToggle.addEventListener('click', function () {
      sidebar.classList.toggle('open');
    });
  }

  const tbody = document.getElementById('transaksiBody');

  function tampilkan(transaksi) {
    if (transaksi.length === 0) {
      tbody.innerHTML = '<tr><td colspan="8">Belum ada transaksi.</td></tr>';
      return;
    }

    let html = '';
    for (let i = 0; i < transaksi.length; i++) {
      const t = transaksi[i];
      html += '<tr>' +
        '<td><a href="nota.html?id=' + t.id + '">' + t.id + '</a></td>' +
        '<td>' + t.customer + '</td>' +
        '<td>' + t.layanan + '</td>' +
        '<td>' + t.qty + ' ' + t.satuan + '</td>' +
        '<td>' + t.total + '</td>' +
        '<td>' + t.tanggal + '</td>' +
        '<td><span class="badge">' + t.status + '</span></td>' +
        '<td>' +
        '<a href="nota.html?id=' + t.id + '">Lihat Nota</a>' +
        ' | ' +
        '<a href="#" class="hapus-transaksi" data-id="' + t.id + '">Hapus</a>' +
        '</td>' +
        '</tr>';
    }
    tbody.innerHTML = html;
  }

  function ambilData() {
    fetch('transaksi.php')
      .then(function (res) {
        return res.json();
      })
      .then(function (data) {
        if (!data.success) {
          window.location.href = 'login.html';
          return;
        }

        document.getElementById('statTotalTransaksi').textContent = data.totalTransaksi;
        document.getElementById('statTotalPendapatan').textContent = data.totalPendapatan;
        tampilkan(data.transaksi);
      });
  }

  // hapus data transaksi, buat jaga-jaga kalau ada input yang salah/duplikat
  tbody.addEventListener('click', function (e) {
    const hapusLink = e.target.closest('.hapus-transaksi');
    if (!hapusLink) return;
    e.preventDefault();

    if (!confirm('Yakin hapus data transaksi ini?')) return;

    fetch('transaksi.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: hapusLink.dataset.id })
    })
      .then(function (res) {
        return res.json();
      })
      .then(function () {
        ambilData();
      });
  });

  ambilData();
});
