document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('registerForm');
  const errorText = document.getElementById('registerError');

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    errorText.textContent = '';

    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    // cek dulu password sama konfirmasinya sudah cocok apa belum
    if (password !== confirmPassword) {
      errorText.textContent = 'Password dan konfirmasi password tidak sama.';
      return;
    }

    const data = {
      nama: document.getElementById('nama').value.trim(),
      username: document.getElementById('username').value.trim(),
      password: password,
      role: document.getElementById('role').value
    };

    fetch('register.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    })
      .then(function (res) {
        return res.json();
      })
      .then(function (hasil) {
        if (hasil.success) {
          window.location.href = 'login.html?registered=1';
        } else {
          errorText.textContent = hasil.message;
        }
      })
      .catch(function () {
        errorText.textContent = 'Terjadi kesalahan, silakan coba lagi.';
      });
  });
});
