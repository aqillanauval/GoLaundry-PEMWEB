<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "admin") {
    echo json_encode(["success" => false]);
    exit;
}

function loadOrders() {
    $orders = [];
    if (file_exists("orders.txt")) {
        foreach (file("orders.txt", FILE_IGNORE_NEW_LINES) as $baris) {
            $data = explode(",", $baris);
            if (count($data) === 9) {
                $orders[] = [
                    "id" => $data[0], "username" => $data[1], "layanan" => $data[2],
                    "harga" => (int)$data[3], "satuan" => $data[4], "qty" => (float)$data[5],
                    "catatan" => $data[6], "status" => $data[7], "tanggal" => $data[8]
                ];
            }
        }
    }
    return $orders;
}

function saveOrders($orders) {
    $lines = [];
    foreach ($orders as $o) {
        $lines[] = implode(",", [
            $o["id"], $o["username"], $o["layanan"], $o["harga"],
            $o["satuan"], $o["qty"], $o["catatan"], $o["status"], $o["tanggal"]
        ]);
    }
    file_put_contents("orders.txt", implode(PHP_EOL, $lines) . (count($lines) ? PHP_EOL : ""));
}

function loadUsers() {
    $users = [];
    if (file_exists("users.txt")) {
        foreach (file("users.txt", FILE_IGNORE_NEW_LINES) as $baris) {
            $data = explode(",", $baris);
            if (count($data) === 4) {
                $users[] = ["username" => $data[0], "nama" => $data[2], "role" => $data[3]];
            }
        }
    }
    return $users;
}

function loadLayanan() {
    $layanan = [];
    if (file_exists("layanan.txt")) {
        foreach (file("layanan.txt", FILE_IGNORE_NEW_LINES) as $baris) {
            $data = explode(",", $baris);
            if (count($data) === 4) {
                $layanan[] = ["id" => $data[0], "nama" => $data[1], "harga" => (int)$data[2], "satuan" => $data[3]];
            }
        }
    }
    return $layanan;
}

function rupiah($n) {
    return "Rp " . number_format($n, 0, ",", ".");
}

$statusList = ["Diterima", "Dicuci", "Selesai", "Diambil"];

$method = $_SERVER["REQUEST_METHOD"];

// bagian CRUD transaksi: tambah (buat transaksi manual/walk-in), edit, hapus
if ($method === "POST") {
    $input = json_decode(file_get_contents("php://input"), true);
    $action = $input["action"] ?? "";
    $orders = loadOrders();

    // tambah transaksi manual, dipakai kalau ada pelanggan yang bayar langsung di tempat
    // pelanggan cukup diketik namanya, jadi yang belum punya akun pun tetap bisa dicatat transaksinya
    if ($action === "tambah") {
        $namaPelanggan = trim($input["nama_pelanggan"] ?? "");
        $layananId = $input["layanan_id"] ?? "";
        $qty = floatval($input["qty"] ?? 0);
        $catatan = str_replace(",", " ", trim($input["catatan"] ?? ""));
        $status = $input["status"] ?? "Diterima";

        if ($namaPelanggan === "" || $layananId === "" || $qty <= 0) {
            echo json_encode(["success" => false, "message" => "Nama pelanggan, layanan, dan jumlah wajib diisi dengan benar."]);
            exit;
        }

        $layananDipilih = null;
        foreach (loadLayanan() as $l) {
            if ($l["id"] === $layananId) {
                $layananDipilih = $l;
                break;
            }
        }

        if (!$layananDipilih) {
            echo json_encode(["success" => false, "message" => "Layanan tidak ditemukan."]);
            exit;
        }

        if (!in_array($status, $statusList)) {
            $status = "Diterima";
        }

        $namaPelanggan = str_replace(",", " ", $namaPelanggan);

        $orders[] = [
            "id" => uniqid("ORD"), "username" => $namaPelanggan, "layanan" => $layananDipilih["nama"],
            "harga" => $layananDipilih["harga"], "satuan" => $layananDipilih["satuan"], "qty" => $qty,
            "catatan" => $catatan, "status" => $status, "tanggal" => date("d M Y H:i")
        ];
        saveOrders($orders);
        echo json_encode(["success" => true]);
        exit;
    }

    // edit transaksi yang sudah ada
    if ($action === "edit") {
        $id = $input["id"] ?? "";
        $layananId = $input["layanan_id"] ?? "";
        $qty = floatval($input["qty"] ?? 0);
        $catatan = str_replace(",", " ", trim($input["catatan"] ?? ""));
        $status = $input["status"] ?? "";

        if ($layananId === "" || $qty <= 0 || !in_array($status, $statusList)) {
            echo json_encode(["success" => false, "message" => "Layanan, jumlah, dan status wajib diisi dengan benar."]);
            exit;
        }

        $layananDipilih = null;
        foreach (loadLayanan() as $l) {
            if ($l["id"] === $layananId) {
                $layananDipilih = $l;
                break;
            }
        }

        if (!$layananDipilih) {
            echo json_encode(["success" => false, "message" => "Layanan tidak ditemukan."]);
            exit;
        }

        $found = false;
        foreach ($orders as &$o) {
            if ($o["id"] === $id) {
                $o["layanan"] = $layananDipilih["nama"];
                $o["harga"] = $layananDipilih["harga"];
                $o["satuan"] = $layananDipilih["satuan"];
                $o["qty"] = $qty;
                $o["catatan"] = $catatan;
                $o["status"] = $status;
                $found = true;
                break;
            }
        }
        unset($o);

        if ($found) {
            saveOrders($orders);
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "message" => "Transaksi tidak ditemukan."]);
        }
        exit;
    }

    // hapus transaksi, buat jaga-jaga kalau ada data yang salah input/duplikat
    if ($action === "hapus") {
        $id = $input["id"] ?? "";
        $sisa = [];
        foreach ($orders as $o) {
            if ($o["id"] !== $id) {
                $sisa[] = $o;
            }
        }
        saveOrders($sisa);
        echo json_encode(["success" => true]);
        exit;
    }

    echo json_encode(["success" => false, "message" => "Aksi tidak dikenal."]);
    exit;
}

// method GET: read, kirim semua transaksi + daftar layanan buat pilihan di form
$orders = loadOrders();
$users = loadUsers();
$layananList = loadLayanan();

// dari daftar user, ambil nama buat ditempel ke tiap transaksi (kalau transaksinya punya akun terdaftar)
$namaPerUsername = [];
foreach ($users as $u) {
    $namaPerUsername[$u["username"]] = $u["nama"];
}

$totalPendapatan = 0;
$transaksi = [];
foreach (array_reverse($orders) as $o) {
    $namaCustomer = isset($namaPerUsername[$o["username"]]) ? $namaPerUsername[$o["username"]] : $o["username"];
    $subtotal = $o["harga"] * $o["qty"];
    $totalPendapatan += $subtotal;

    $transaksi[] = [
        "id" => $o["id"], "username" => $o["username"], "customer" => $namaCustomer,
        "layanan" => $o["layanan"], "qty" => $o["qty"], "satuan" => $o["satuan"],
        "catatan" => $o["catatan"], "total" => rupiah($subtotal),
        "tanggal" => $o["tanggal"], "status" => $o["status"]
    ];
}

echo json_encode([
    "success" => true,
    "totalTransaksi" => count($transaksi),
    "totalPendapatan" => rupiah($totalPendapatan),
    "transaksi" => $transaksi,
    "layanan" => $layananList
]);
