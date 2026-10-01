document.addEventListener('DOMContentLoaded', function () {
  const menuToggle = document.getElementById('menuToggle');
  const sidebar = document.getElementById('sidebar');
  if (menuToggle && sidebar) {
    menuToggle.addEventListener('click', function () {
      sidebar.classList.toggle('open');
    });
  }

  const form = document.getElementById('transaksiForm');
  const idInput = document.getElementById('transaksiId');
  const namaPelangganInput = document.getElementById('namaPelanggan');
  const layananSelect = document.getElementById('layananId');
  const qtyInput = document.getElementById('qty');
  const catatanInput = document.getElementById('catatan');
  const statusSelect = document.getElementById('status');
  const errorText = document.getElementById('transaksiError');
  const formTitle = document.getElementById('formTitle');
  const submitBtn = document.getElementById('submitBtn');
  const batalEdit = document.getElementById('batalEdit');
  const tbody = document.getElementById('transaksiBody');

  let daftarTransaksi = [];

  function resetForm() {
    idInput.value = '';
    form.reset();
    namaPelangganInput.disabled = false;
    formTitle.textContent = 'Tambah Transaksi Manual';
    submitBtn.textContent = 'Tambah Transaksi';
    batalEdit.style.display = 'none';
    errorText.textContent = '';
  }

  function tampilkanPilihan(selectEl, daftar, valueKey, labelFn) {
    let html = selectEl.options[0].outerHTML;
    for (let i = 0; i < daftar.length; i++) {
      const item = daftar[i];
      html += '<option value="' + item[valueKey] + '">' + labelFn(item) + '</option>';
    }
    selectEl.innerHTML = html;
  }

  function tampilkanTabel() {
    if (daftarTransaksi.length === 0) {
      tbody.innerHTML = '<tr><td colspan="8">Belum ada transaksi.</td></tr>';
      return;
    }

    let html = '';
    for (let i = 0; i < daftarTransaksi.length; i++) {
      const t = daftarTransaksi[i];
      html += '<tr>' +
        '<td><a href="nota.html?id=' + t.id + '">' + t.id + '</a></td>' +
        '<td>' + t.customer + '</td>' +
        '<td>' + t.layanan + '</td>' +
        '<td>' + t.qty + ' ' + t.satuan + '</td>' +
        '<td>' + t.total + '</td>' +
        '<td>' + t.tanggal + '</td>' +
        '<td><span class="badge">' + t.status + '</span></td>' +
        '<td>' +
        '<a href="#" class="edit-link" data-id="' + t.id + '">Edit</a> | ' +
        '<a href="#" class="hapus-link" data-id="' + t.id + '">Hapus</a>' +
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

        tampilkanPilihan(layananSelect, data.layanan, 'id', function (l) {
          return l.nama + ' - Rp ' + l.harga + ' / ' + l.satuan;
        });

        daftarTransaksi = data.transaksi;
        tampilkanTabel();
      });
  }

  // tambah/edit transaksi
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    errorText.textContent = '';

    const payload = {
      action: idInput.value ? 'edit' : 'tambah',
      id: idInput.value,
      nama_pelanggan: namaPelangganInput.value.trim(),
      layanan_id: layananSelect.value,
      qty: qtyInput.value,
      catatan: catatanInput.value.trim(),
      status: statusSelect.value
    };

    fetch('transaksi.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
      .then(function (res) {
        return res.json();
      })
      .then(function (hasil) {
        if (hasil.success) {
          resetForm();
          ambilData();
        } else {
          errorText.textContent = hasil.message;
        }
      });
  });

  // edit sama hapus, pakai delegasi soalnya isi tabel diganti-ganti terus
  tbody.addEventListener('click', function (e) {
    const editLink = e.target.closest('.edit-link');
    if (editLink) {
      e.preventDefault();
      let t = null;
      for (let i = 0; i < daftarTransaksi.length; i++) {
        if (daftarTransaksi[i].id === editLink.dataset.id) {
          t = daftarTransaksi[i];
          break;
        }
      }
      if (t) {
        idInput.value = t.id;
        namaPelangganInput.value = t.customer;
        namaPelangganInput.disabled = true; // customer di transaksi lama gak diubah, biar gak salah pindah pemilik
        qtyInput.value = t.qty;
        catatanInput.value = t.catatan;
        statusSelect.value = t.status;

        // cari layanan yang namanya sama buat di-pilih di dropdown
        for (let i = 0; i < layananSelect.options.length; i++) {
          if (layananSelect.options[i].textContent.indexOf(t.layanan) === 0) {
            layananSelect.selectedIndex = i;
            break;
          }
        }

        formTitle.textContent = 'Edit Transaksi';
        submitBtn.textContent = 'Simpan Perubahan';
        batalEdit.style.display = 'inline-block';
      }
      return;
    }

    const hapusLink = e.target.closest('.hapus-link');
    if (hapusLink) {
      e.preventDefault();
      if (!confirm('Yakin hapus transaksi ini?')) return;
      fetch('transaksi.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'hapus', id: hapusLink.dataset.id })
      })
        .then(function (res) {
          return res.json();
        })
        .then(function () {
          ambilData();
        });
    }
  });

  batalEdit.addEventListener('click', function (e) {
    e.preventDefault();
    resetForm();
  });

  ambilData();
});
