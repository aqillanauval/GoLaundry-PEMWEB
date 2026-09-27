document.addEventListener('DOMContentLoaded', function () {
  const menuToggle = document.getElementById('menuToggle');
  const sidebar = document.getElementById('sidebar');
  if (menuToggle && sidebar) {
    menuToggle.addEventListener('click', function () {
      sidebar.classList.toggle('open');
    });
  }

  const form = document.getElementById('pelangganForm');
  const usernameLamaInput = document.getElementById('usernameLama');
  const namaInput = document.getElementById('nama');
  const usernameInput = document.getElementById('username');
  const passwordInput = document.getElementById('password');
  const errorText = document.getElementById('pelangganError');
  const submitBtn = document.getElementById('submitBtn');
  const batalEdit = document.getElementById('batalEdit');
  const tbody = document.getElementById('pelangganBody');

  let daftarPelanggan = [];

  function resetForm() {
    usernameLamaInput.value = '';
    form.reset();
    usernameInput.disabled = false;
    passwordInput.style.display = 'block';
    passwordInput.required = true;
    submitBtn.textContent = 'Tambah Pelanggan';
    batalEdit.style.display = 'none';
    errorText.textContent = '';
  }

  function tampilkan() {
    if (daftarPelanggan.length === 0) {
      tbody.innerHTML = '<tr><td colspan="3">Belum ada pelanggan yang daftar.</td></tr>';
      return;
    }
    let html = '';
    for (let i = 0; i < daftarPelanggan.length; i++) {
      const p = daftarPelanggan[i];
      html += '<tr>' +
        '<td>' + p.nama + '</td>' +
        '<td>' + p.username + '</td>' +
        '<td><a href="#" class="edit-link" data-username="' + p.username + '">Edit</a> | ' +
        '<a href="#" class="cancel-link hapus-link" data-username="' + p.username + '">Hapus</a></td>' +
        '</tr>';
    }
    tbody.innerHTML = html;
  }

  function ambilData() {
    fetch('pelanggan.php')
      .then(function (res) {
        return res.json();
      })
      .then(function (data) {
        if (!data.success) {
          window.location.href = 'login.html';
          return;
        }
        daftarPelanggan = data.pelanggan;
        tampilkan();
      });
  }

  // create pelanggan baru atau update nama pelanggan lama
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    errorText.textContent = '';

    const sedangEdit = usernameLamaInput.value !== '';
    const payload = {
      action: sedangEdit ? 'edit' : 'tambah',
      username: sedangEdit ? usernameLamaInput.value : usernameInput.value.trim(),
      nama: namaInput.value.trim(),
      password: passwordInput.value
    };

    fetch('pelanggan.php', {
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
      let p = null;
      for (let i = 0; i < daftarPelanggan.length; i++) {
        if (daftarPelanggan[i].username === editLink.dataset.username) {
          p = daftarPelanggan[i];
          break;
        }
      }
      if (p) {
        usernameLamaInput.value = p.username;
        namaInput.value = p.nama;
        usernameInput.value = p.username;
        usernameInput.disabled = true;
        passwordInput.value = '';
        passwordInput.style.display = 'none';
        passwordInput.required = false;
        submitBtn.textContent = 'Simpan Perubahan';
        batalEdit.style.display = 'inline-block';
      }
      return;
    }

    const hapusLink = e.target.closest('.hapus-link');
    if (hapusLink) {
      e.preventDefault();
      if (!confirm('Yakin hapus akun pelanggan ini?')) return;
      fetch('pelanggan.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'hapus', username: hapusLink.dataset.username })
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
