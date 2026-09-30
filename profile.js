document.addEventListener('DOMContentLoaded', function () {
  const menuToggle = document.getElementById('menuToggle');
  const sidebar = document.getElementById('sidebar');
  if (menuToggle && sidebar) {
    menuToggle.addEventListener('click', function () {
      sidebar.classList.toggle('open');
    });
  }

  const navMenu = document.getElementById('navMenu');
  const form = document.getElementById('profileForm');
  const usernameInput = document.getElementById('username');
  const namaInput = document.getElementById('nama');
  const roleInput = document.getElementById('role');
  const errorText = document.getElementById('profileError');
  const successText = document.getElementById('profileSuccess');

  // isi menu sidebar sesuai role, soalnya menu admin sama customer beda
  function tampilkanMenu(role) {
    let html = '';
    if (role === 'admin') {
      html += '<a href="dashboard-admin.html">Dashboard</a>';
      html += '<a href="layanan.html">Manajemen Layanan</a>';
      html += '<a href="pelanggan.html">Manajemen Pelanggan</a>';
      html += '<a href="karyawan.html">Manajemen Karyawan</a>';
      html += '<a href="transaksi.html">Riwayat Transaksi</a>';
    } else {
      html += '<a href="dashboard-customer.html">Dashboard</a>';
      html += '<a href="order.html">Buat Pesanan</a>';
      html += '<a href="riwayat.html">Riwayat Transaksi</a>';
    }
    html += '<a href="profile.html" class="active">Profil</a>';
    html += '<a href="login.php?logout=1">Keluar</a>';
    navMenu.innerHTML = html;
  }

  function ambilData() {
    fetch('profile.php')
      .then(function (res) {
        return res.json();
      })
      .then(function (data) {
        if (!data.success) {
          window.location.href = 'login.html';
          return;
        }
        usernameInput.value = data.username;
        namaInput.value = data.nama;
        roleInput.value = data.role === 'admin' ? 'Admin/Staff' : 'Customer';
        tampilkanMenu(data.role);
      });
  }

  // update data diri (cuma nama yang bisa diubah)
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    errorText.textContent = '';
    successText.textContent = '';

    fetch('profile.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ nama: namaInput.value.trim() })
    })
      .then(function (res) {
        return res.json();
      })
      .then(function (hasil) {
        if (hasil.success) {
          successText.textContent = 'Data diri berhasil diperbarui.';
        } else {
          errorText.textContent = hasil.message;
        }
      });
  });

  ambilData();
});
