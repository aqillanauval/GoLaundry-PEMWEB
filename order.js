document.addEventListener('DOMContentLoaded', function () {
  const menuToggle = document.getElementById('menuToggle');
  const sidebar = document.getElementById('sidebar');
  if (menuToggle && sidebar) {
    menuToggle.addEventListener('click', function () {
      sidebar.classList.toggle('open');
    });
  }

  const form = document.getElementById('orderForm');
  const layananSelect = document.getElementById('layanan');
  const qtyInput = document.getElementById('qty');
  const catatanInput = document.getElementById('catatan');
  const orderIdInput = document.getElementById('orderId');
  const errorText = document.getElementById('orderError');
  const submitBtn = document.getElementById('submitBtn');
  const cancelEdit = document.getElementById('cancelEdit');
  const formTitle = document.getElementById('formTitle');
  const orderBody = document.getElementById('orderBody');

  let layananList = {};
  let orders = [];

  function rupiah(n) {
    return 'Rp ' + Number(n).toLocaleString('id-ID');
  }

  function resetForm() {
    orderIdInput.value = '';
    form.reset();
    submitBtn.textContent = 'Buat Pesanan';
    formTitle.textContent = 'Buat Pesanan Baru';
    cancelEdit.style.display = 'none';
    errorText.textContent = '';
  }

  function tampilkanOrder() {
    if (orders.length === 0) {
      orderBody.innerHTML = '<tr><td colspan="6">Belum ada pesanan.</td></tr>';
      return;
    }

    let html = '';
    for (let i = 0; i < orders.length; i++) {
      const o = orders[i];
      let aksi = '-';
      if (o.status === 'Diterima') {
        aksi = '<a href="#" class="edit-link" data-id="' + o.id + '">Edit</a> | ' +
               '<a href="#" class="cancel-link" data-id="' + o.id + '">Batalkan</a>';
      }
      html += '<tr>' +
        '<td>' + o.tanggal + '</td>' +
        '<td>' + o.layanan + '</td>' +
        '<td>' + o.qty + ' ' + o.satuan + '</td>' +
        '<td>' + rupiah(o.harga * o.qty) + '</td>' +
        '<td><span class="badge">' + o.status + '</span></td>' +
        '<td>' + aksi + '</td>' +
        '</tr>';
    }
    orderBody.innerHTML = html;
  }

  function ambilData() {
    fetch('order.php')
      .then(function (res) {
        return res.json();
      })
      .then(function (data) {
        if (!data.success) {
          window.location.href = 'login.html';
          return;
        }
        layananList = data.layanan;
        orders = data.orders;

        let options = '<option value="">-- Pilih Layanan --</option>';
        for (const key in layananList) {
          const l = layananList[key];
          options += '<option value="' + key + '">' + l.nama + ' - ' + rupiah(l.harga) + ' / ' + l.satuan + '</option>';
        }
        layananSelect.innerHTML = options;

        tampilkanOrder();
      });
  }

  // create/update pesanan
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    errorText.textContent = '';

    const payload = {
      action: orderIdInput.value ? 'update' : 'create',
      id: orderIdInput.value,
      layanan: layananSelect.value,
      qty: qtyInput.value,
      catatan: catatanInput.value
    };

    fetch('order.php', {
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

  // event buat tombol edit sama batalkan, pakai delegasi soalnya isi tabel diganti-ganti terus
  orderBody.addEventListener('click', function (e) {
    const editLink = e.target.closest('.edit-link');
    if (editLink) {
      e.preventDefault();
      let order = null;
      for (let i = 0; i < orders.length; i++) {
        if (orders[i].id === editLink.dataset.id) {
          order = orders[i];
          break;
        }
      }
      if (order) {
        orderIdInput.value = order.id;
        let matchedKey = '';
        for (const key in layananList) {
          if (layananList[key].nama === order.layanan) {
            matchedKey = key;
            break;
          }
        }
        layananSelect.value = matchedKey;
        qtyInput.value = order.qty;
        catatanInput.value = order.catatan;
        submitBtn.textContent = 'Simpan Perubahan';
        formTitle.textContent = 'Edit Pesanan';
        cancelEdit.style.display = 'inline-block';
      }
      return;
    }

    const cancelLink = e.target.closest('.cancel-link');
    if (cancelLink) {
      e.preventDefault();
      if (!confirm('Yakin batalkan pesanan ini?')) return;
      fetch('order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'cancel', id: cancelLink.dataset.id })
      })
        .then(function (res) {
          return res.json();
        })
        .then(function () {
          ambilData();
        });
    }
  });

  cancelEdit.addEventListener('click', function (e) {
    e.preventDefault();
    resetForm();
  });

  ambilData();
});
