document.addEventListener('DOMContentLoaded', function () {
  const menuToggle = document.getElementById('menuToggle');
  const sidebar = document.getElementById('sidebar');
  if (menuToggle && sidebar) {
    menuToggle.addEventListener('click', function () {
      sidebar.classList.toggle('open');
    });
  }

  const form = document.getElementById('karyawanForm');
  const idInput = document.getElementById('karyawanId');
  const namaInput = document.getElementById('nama');
  const jabatanInput = document.getElementById('jabatan');
  const noHpInput = document.getElementById('noHp');
  const errorText = document.getElementById('karyawanError');
  const submitBtn = document.getElementById('submitBtn');
  const batalEdit = document.getElementById('batalEdit');
  const tbody = document.getElementById('karyawanBody');

  let daftarKaryawan = [];

  function resetForm() {
    idInput.value = '';
    form.reset();
    submitBtn.textContent = 'Tambah Karyawan';
    batalEdit.style.display = 'none';
    errorText.textContent = '';
  }

  function tampilkan() {
    if (daftarKaryawan.length === 0) {
      tbody.innerHTML = '<tr><td colspan="4">Belum ada karyawan.</td></tr>';
      return;
    }
    let html = '';
    for (let i = 0; i < daftarKaryawan.length; i++) {
      const k = daftarKaryawan[i];
      html += '<tr>' +
        '<td>' + k.nama + '</td>' +
        '<td>' + k.jabatan + '</td>' +
        '<td>' + k.no_hp + '</td>' +
        '<td><a href="#" class="edit-link" data-id="' + k.id + '">Edit</a> | ' +
        '<a href="#" class="cancel-link hapus-link" data-id="' + k.id + '">Hapus</a></td>' +
        '</tr>';
    }
    tbody.innerHTML = html;
  }

  function ambilData() {
    fetch('karyawan.php')
      .then(function (res) {
        return res.json();
      })
      .then(function (data) {
        if (!data.success) {
          window.location.href = 'login.html';
          return;
        }
        daftarKaryawan = data.karyawan;
        tampilkan();
      });
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    errorText.textContent = '';

    const payload = {
      action: idInput.value ? 'edit' : 'tambah',
      id: idInput.value,
      nama: namaInput.value.trim(),
      jabatan: jabatanInput.value.trim(),
      no_hp: noHpInput.value.trim()
    };

    fetch('karyawan.php', {
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

  tbody.addEventListener('click', function (e) {
    const editLink = e.target.closest('.edit-link');
    if (editLink) {
      e.preventDefault();
      let k = null;
      for (let i = 0; i < daftarKaryawan.length; i++) {
        if (daftarKaryawan[i].id === editLink.dataset.id) {
          k = daftarKaryawan[i];
          break;
        }
      }
      if (k) {
        idInput.value = k.id;
        namaInput.value = k.nama;
        jabatanInput.value = k.jabatan;
        noHpInput.value = k.no_hp;
        submitBtn.textContent = 'Simpan Perubahan';
        batalEdit.style.display = 'inline-block';
      }
      return;
    }

    const hapusLink = e.target.closest('.hapus-link');
    if (hapusLink) {
      e.preventDefault();
      if (!confirm('Yakin hapus data karyawan ini?')) return;
      fetch('karyawan.php', {
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
