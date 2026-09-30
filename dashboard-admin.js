document.addEventListener('DOMContentLoaded', function () {
  const menuToggle = document.getElementById('menuToggle');
  const sidebar = document.getElementById('sidebar');
  if (menuToggle && sidebar) {
    menuToggle.addEventListener('click', function () {
      sidebar.classList.toggle('open');
    });
  }

  const tbody = document.getElementById('pesananBody');
  let statusOptions = [];

  // nama customer walk-in diketik bebas oleh admin, jadi diamankan dulu
  function esc(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }

  function ambilData() {
    fetch('dashboard-admin.php')
      .then(function (res) {
        return res.json();
      })
      .then(function (data) {
        if (!data.success) {
          window.location.href = 'login.html';
          return;
        }

        document.getElementById('greeting').textContent = 'Halo, ' + data.nama + ' \u{1F44B}';
        document.getElementById('statPesananHariIni').textContent = data.pesananHariIni;
        document.getElementById('statBelumDiproses').textContent = data.belumDiproses;
        document.getElementById('statPendapatan').textContent = data.pendapatanHariIni;
        document.getElementById('statPelanggan').textContent = data.jumlahPelanggan;

        statusOptions = data.statusOptions;
        tampilkan(data.pesananMasuk);
      });
  }

  function tampilkan(pesananMasuk) {
    if (pesananMasuk.length === 0) {
      tbody.innerHTML = '<tr><td colspan="8">Belum ada pesanan masuk.</td></tr>';
      return;
    }

    let html = '';
    for (let i = 0; i < pesananMasuk.length; i++) {
      const o = pesananMasuk[i];

      let opsiStatus = '';
      for (let j = 0; j < statusOptions.length; j++) {
        const dipilih = statusOptions[j] === o.status ? ' selected' : '';
        opsiStatus += '<option value="' + statusOptions[j] + '"' + dipilih + '>' + statusOptions[j] + '</option>';
      }

      html += '<tr>' +
        '<td><a href="nota.html?id=' + o.id + '">' + o.id + '</a></td>' +
        '<td>' + esc(o.customer) + '</td>' +
        '<td>' + o.layanan + '</td>' +
        '<td>' + o.qty + ' ' + o.satuan + '</td>' +
        '<td>' + o.total + '</td>' +
        '<td>' + o.tanggal + '</td>' +
        '<td><span class="badge">' + o.status + '</span></td>' +
        '<td>' +
        '<div class="inline-form">' +
        '<select class="status-select" data-id="' + o.id + '">' + opsiStatus + '</select>' +
        '<button type="button" class="btn-update" data-id="' + o.id + '">Update</button>' +
        '</div>' +
        '</td>' +
        '</tr>';
    }
    tbody.innerHTML = html;
  }

  // update status pesanan, ini bagian "update" dari CRUD yang dipegang admin
  tbody.addEventListener('click', function (e) {
    const tombol = e.target.closest('.btn-update');
    if (!tombol) return;

    const id = tombol.dataset.id;
    const select = tbody.querySelector('.status-select[data-id="' + id + '"]');
    const statusBaru = select.value;

    fetch('dashboard-admin.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: id, status: statusBaru })
    })
      .then(function (res) {
        return res.json();
      })
      .then(function (hasil) {
        if (hasil.success) {
          ambilData();
        } else {
          alert(hasil.message);
        }
      });
  });

  ambilData();
});