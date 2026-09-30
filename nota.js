document.addEventListener('DOMContentLoaded', function () {
  const printBtn = document.getElementById('printBtn');
  const backLink = document.getElementById('backLink');

  // teks yang diketik bebas (layanan, catatan) diamankan dulu sebelum masuk innerHTML
  function esc(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }

  // ambil id pesanan dari url, contoh nota.html?id=ORD123
  const params = new URLSearchParams(window.location.search);
  const id = params.get('id');

  fetch('nota.php?id=' + encodeURIComponent(id))
    .then(function (res) {
      return res.json();
    })
    .then(function (data) {
      if (!data.success) {
        alert(data.message || 'Nota tidak ditemukan.');
        window.location.href = 'login.html';
        return;
      }

      document.getElementById('notaId').textContent = data.id;
      document.getElementById('notaTanggal').textContent = data.tanggal;
      document.getElementById('notaCustomer').textContent = data.customer;
      document.getElementById('notaStatus').textContent = data.status;
      document.getElementById('notaTotal').textContent = data.total;

      document.getElementById('notaBody').innerHTML =
        '<tr><td>' + esc(data.layanan) + '</td>' +
        '<td>' + esc(data.qty + ' ' + data.satuan) + '</td>' +
        '<td>' + data.harga + '</td>' +
        '<td>' + data.total + '</td></tr>';

      if (data.catatan) {
        const catatanEl = document.getElementById('notaCatatan');
        catatanEl.innerHTML = '<strong>Catatan:</strong> ' + esc(data.catatan);
        catatanEl.style.display = 'block';
      }

      // tombol kembali diarahkan beda tergantung yang login admin atau customer
      backLink.href = data.role === 'admin' ? 'transaksi.html' : 'riwayat.html';
    })
    .catch(function () {
      alert('Terjadi kesalahan waktu ambil data nota.');
    });

  // tombol cetak/unduh, tinggal panggil print bawaan browser
  printBtn.addEventListener('click', function () {
    window.print();
  });
});