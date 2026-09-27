document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('loginForm');
  const errorText = document.getElementById('loginError');
  const usernameInput = document.getElementById('username');
  const ingatCheckbox = document.getElementById('ingatSaya');

  // kalau sebelumnya pernah centang "ingat saya", username-nya ada di cookie
  const cookieUser = getCookie('username');
  if (cookieUser) {
    usernameInput.value = cookieUser;
    ingatCheckbox.checked = true;
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    errorText.textContent = '';

    const data = {
      username: usernameInput.value.trim(),
      password: document.getElementById('password').value,
      role: document.getElementById('role').value,
      ingat: ingatCheckbox.checked
    };

    fetch('login.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    })
      .then(function (res) {
        return res.json();
      })
      .then(function (hasil) {
        if (hasil.success) {
          window.location.href = hasil.redirect;
        } else {
          errorText.textContent = hasil.message;
        }
      })
      .catch(function () {
        errorText.textContent = 'Terjadi kesalahan, silakan coba lagi.';
      });
  });

  // fungsi kecil buat ambil isi cookie berdasarkan namanya
  function getCookie(nama) {
    const semuaCookie = document.cookie.split('; ');
    for (let i = 0; i < semuaCookie.length; i++) {
      const bagian = semuaCookie[i].split('=');
      if (bagian[0] === nama) {
        return decodeURIComponent(bagian[1]);
      }
    }
    return '';
  }
});
