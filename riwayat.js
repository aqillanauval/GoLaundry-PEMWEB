document.addEventListener('DOMContentLoaded', function () {
  const menuToggle = document.getElementById('menuToggle');
  const sidebar = document.getElementById('sidebar');
  if (menuToggle && sidebar) {
    menuToggle.addEventListener('click', function () {
      sidebar.classList.toggle('open');
    });
  }

  const tbody = document.getElementById('riwayatBody');

  function rupiah(n) {
    return 'Rp ' + Number(n).toLocaleString('id-ID');
  }

  function tampilkan(orders) {
    if (orders.length === 0) {
      tbody.innerHTML = '<tr><td colspan="7">Belum ada transaksi.</td></tr>';
      return;
    }

    let html = '';
    for (let i = 0; i < orders.length; i++) {
      const o = orders[i];

      // kolom ulasan cuma aktif kalau status pesanannya udah Selesai
      let ulasanHtml = '-';
      if (o.status === 'Selesai') {
        if (o.rating) {
          ulasanHtml = 'Bintang ' + o.rating + ' - ' + o.komentar +
            '<br><a href="#" class="edit-ulasan" data-id="' + o.id + '">Edit</a> | ' +
            '<a href="#" class="hapus-ulasan" data-id="' + o.id + '">Hapus</a>';
        } else {
          ulasanHtml = '<a href="#" class="buat-ulasan" data-id="' + o.id + '">Beri Ulasan</a>';
        }
      }

      html += '<tr>' +
        '<td><a href="nota.html?id=' + o.id + '">' + o.id + '</a></td>' +
        '<td>' + o.tanggal + '</td>' +
        '<td>' + o.layanan + '</td>' +
        '<td>' + o.qty + ' ' + o.satuan + '</td>' +
        '<td>' + rupiah(o.harga * o.qty) + '</td>' +
        '<td><span class="badge">' + o.status + '</span></td>' +
        '<td>' + ulasanHtml + '</td>' +
        '</tr>';
    }
    tbody.innerHTML = html;
  }

  function ambilData() {
    fetch('riwayat.php')
      .then(function (res) {
        return res.json();
      })
      .then(function (data) {
        if (!data.success) {
          window.location.href = 'login.html';
          return;
        }
        tampilkan(data.orders);
      });
  }

  // dipakai buat kirim ulasan baru, edit, atau hapus ke server
  function kirimUlasan(action, id, rating, komentar) {
    fetch('riwayat.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: action, id: id, rating: rating, komentar: komentar })
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
  }

  tbody.addEventListener('click', function (e) {
    // create ulasan
    const buatLink = e.target.closest('.buat-ulasan');
    if (buatLink) {
      e.preventDefault();
      const rating = prompt('Kasih rating berapa (1-5)?');
      if (rating === null) return;
      const komentar = prompt('Tulis komentarnya:');
      if (komentar === null) return;
      kirimUlasan('buat_ulasan', buatLink.dataset.id, rating, komentar);
      return;
    }

    // update ulasan
    const editLink = e.target.closest('.edit-ulasan');
    if (editLink) {
      e.preventDefault();
      const rating = prompt('Ubah rating jadi berapa (1-5)?');
      if (rating === null) return;
      const komentar = prompt('Ubah komentarnya jadi apa?');
      if (komentar === null) return;
      kirimUlasan('edit_ulasan', editLink.dataset.id, rating, komentar);
      return;
    }

    // delete ulasan
    const hapusLink = e.target.closest('.hapus-ulasan');
    if (hapusLink) {
      e.preventDefault();
      if (!confirm('Yakin hapus ulasan ini?')) return;
      kirimUlasan('hapus_ulasan', hapusLink.dataset.id, '', '');
    }
  });

  ambilData();
});
