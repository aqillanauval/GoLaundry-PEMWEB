document.addEventListener('DOMContentLoaded', function () {
  const menuToggle = document.getElementById('menuToggle');
  const sidebar = document.getElementById('sidebar');
  if (menuToggle && sidebar) {
    menuToggle.addEventListener('click', function () {
      sidebar.classList.toggle('open');
    });
  }

  const tbody = document.getElementById('transaksiBody');

  // catatan diketik bebas, jadi karakter HTML-nya diamankan dulu sebelum masuk tabel
  function esc(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }

  function tampilkan(transaksi) {
    if (transaksi.length === 0) {
      tbody.innerHTML = '<tr><td colspan="9">Belum ada transaksi.</td></tr>';
      return;
    }

    let html = '';
    for (let i = 0; i < transaksi.length; i++) {
      const t = transaksi[i];
      html += '<tr>' +
        '<td><a href="nota.html?id=' + t.id + '">' + t.id + '</a></td>' +
        '<td>' + esc(t.customer) + '</td>' +
        '<td>' + esc(t.layanan) + '</td>' +
        '<td>' + t.qty + ' ' + t.satuan + '</td>' +
        '<td>' + (t.catatan ? esc(t.catatan) : '-') + '</td>' +
        '<td>' + t.total + '</td>' +
        '<td>' + t.tanggal + '</td>' +
        '<td><span class="badge">' + t.status + '</span></td>' +
        '<td>' +
        '<a href="nota.html?id=' + t.id + '">Lihat Nota</a>' +
        ' | ' +
        '<a href="#" class="edit-transaksi" data-id="' + t.id + '">Edit</a>' +
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

  // ===== CREATE & UPDATE =====
  const formCard = document.getElementById('formCard');
  const form = document.getElementById('formTransaksi');
  const formJudul = document.getElementById('formJudul');

  function bukaForm(judul) {
    formJudul.textContent = judul;
    formCard.style.display = 'block';
    formCard.scrollIntoView({ behavior: 'smooth' });
  }

  function tutupForm() {
    form.reset();
    document.getElementById('fId').value = '';
    formCard.style.display = 'none';
  }

  document.getElementById('btnTambah').addEventListener('click', function () {
    form.reset();
    document.getElementById('fId').value = '';
    document.getElementById('fTanggal').value = new Date().toISOString().slice(0, 10);
    bukaForm('Tambah Transaksi');
  });

  document.getElementById('btnBatal').addEventListener('click', tutupForm);

  // klik Edit: ambil data lengkap transaksi dari server, isi ke form
  tbody.addEventListener('click', function (e) {
    const editLink = e.target.closest('.edit-transaksi');
    if (!editLink) return;
    e.preventDefault();

    fetch('transaksi.php?id=' + encodeURIComponent(editLink.dataset.id))
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (!data.success) {
          alert(data.message);
          return;
        }
        const o = data.order;
        document.getElementById('fId').value = o.id;
        document.getElementById('fUsername').value = o.username;
        document.getElementById('fLayanan').value = o.layanan;
        document.getElementById('fHarga').value = o.harga;
        document.getElementById('fSatuan').value = o.satuan;
        document.getElementById('fQty').value = o.qty;
        document.getElementById('fCatatan').value = o.catatan;
        document.getElementById('fStatus').value = o.status;
        document.getElementById('fTanggal').value = o.tanggal;
        bukaForm('Edit Transaksi ' + o.id);
      });
  });

  // submit form: kalau fId kosong berarti tambah, kalau ada isinya berarti ubah
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    const id = document.getElementById('fId').value;

    fetch('transaksi.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: id ? 'ubah' : 'tambah',
        id: id,
        username: document.getElementById('fUsername').value,
        layanan: document.getElementById('fLayanan').value,
        harga: document.getElementById('fHarga').value,
        satuan: document.getElementById('fSatuan').value,
        qty: document.getElementById('fQty').value,
        catatan: document.getElementById('fCatatan').value,
        status: document.getElementById('fStatus').value,
        tanggal: document.getElementById('fTanggal').value
      })
    })
      .then(function (res) { return res.json(); })
      .then(function (hasil) {
        if (hasil.success) {
          tutupForm();
          ambilData();
        } else {
          alert(hasil.message || 'Gagal menyimpan transaksi.');
        }
      })
      .catch(function () {
        alert('Gagal menyimpan: respon server tidak valid. Cek error PHP di transaksi.php.');
      });
  });

  ambilData();
});