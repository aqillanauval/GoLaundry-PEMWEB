document.addEventListener('DOMContentLoaded', function () {
  const menuToggle = document.getElementById('menuToggle');
  const sidebar = document.getElementById('sidebar');
  if (menuToggle && sidebar) {
    menuToggle.addEventListener('click', function () {
      sidebar.classList.toggle('open');
    });
  }

  const form = document.getElementById('layananForm');
  const idInput = document.getElementById('layananId');
  const namaInput = document.getElementById('nama');
  const hargaInput = document.getElementById('harga');
  const satuanInput = document.getElementById('satuan');
  const errorText = document.getElementById('layananError');
  const submitBtn = document.getElementById('submitBtn');
  const batalEdit = document.getElementById('batalEdit');
  const tbody = document.getElementById('layananBody');

  let daftarLayanan = [];

  function rupiah(n) {
    return 'Rp ' + Number(n).toLocaleString('id-ID');
  }

  function resetForm() {
    idInput.value = '';
    form.reset();
    submitBtn.textContent = 'Tambah Layanan';
    batalEdit.style.display = 'none';
    errorText.textContent = '';
  }

  function tampilkan() {
    if (daftarLayanan.length === 0) {
      tbody.innerHTML = '<tr><td colspan="4">Belum ada layanan.</td></tr>';
      return;
    }
    let html = '';
    for (let i = 0; i < daftarLayanan.length; i++) {
      const l = daftarLayanan[i];
      html += '<tr>' +
        '<td>' + l.nama + '</td>' +
        '<td>' + rupiah(l.harga) + '</td>' +
        '<td>' + l.satuan + '</td>' +
        '<td><a href="#" class="edit-link" data-id="' + l.id + '">Edit</a> | ' +
        '<a href="#" class="cancel-link hapus-link" data-id="' + l.id + '">Hapus</a></td>' +
        '</tr>';
    }
    tbody.innerHTML = html;
  }

  function ambilData() {
    fetch('layanan.php')
      .then(function (res) {
        return res.json();
      })
      .then(function (data) {
        if (!data.success) {
          window.location.href = 'login.html';
          return;
        }
        daftarLayanan = data.layanan;
        tampilkan();
      });
  }

  // create/update layanan
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    errorText.textContent = '';

    const payload = {
      action: idInput.value ? 'edit' : 'tambah',
      id: idInput.value,
      nama: namaInput.value.trim(),
      harga: hargaInput.value,
      satuan: satuanInput.value.trim()
    };

    fetch('layanan.php', {
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
      let l = null;
      for (let i = 0; i < daftarLayanan.length; i++) {
        if (daftarLayanan[i].id === editLink.dataset.id) {
          l = daftarLayanan[i];
          break;
        }
      }
      if (l) {
        idInput.value = l.id;
        namaInput.value = l.nama;
        hargaInput.value = l.harga;
        satuanInput.value = l.satuan;
        submitBtn.textContent = 'Simpan Perubahan';
        batalEdit.style.display = 'inline-block';
      }
      return;
    }

    const hapusLink = e.target.closest('.hapus-link');
    if (hapusLink) {
      e.preventDefault();
      if (!confirm('Yakin hapus layanan ini?')) return;
      fetch('layanan.php', {
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
