<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "admin") {
    echo json_encode(["success" => false]);
    exit;
}

$statusOptions = ["Diterima", "Dicuci", "Selesai", "Diambil"];

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
    $users = [
        "admin@golaundry.com" => ["password" => "admin123", "nama" => "Admin GoLaundry", "role" => "admin"],
        "pelanggan@golaundry.com" => ["password" => "pelanggan123", "nama" => "Pelanggan GoLaundry", "role" => "customer"]
    ];

    if (file_exists("users.txt")) {
        foreach (file("users.txt", FILE_IGNORE_NEW_LINES) as $baris) {
            $data = explode(",", $baris);
            if (count($data) === 4) {
                $users[$data[0]] = ["password" => $data[1], "nama" => $data[2], "role" => $data[3]];
            }
        }
    }

    return $users;
}

function rupiah($n) {
    return "Rp " . number_format($n, 0, ",", ".");
}

$method = $_SERVER["REQUEST_METHOD"];

// update status pesanan (bagian "update" dari CRUD pesanan yang dipegang admin)
if ($method === "POST") {
    $input = json_decode(file_get_contents("php://input"), true);
    $id = $input["id"] ?? "";
    $statusBaru = $input["status"] ?? "";

    if (!in_array($statusBaru, $statusOptions, true)) {
        echo json_encode(["success" => false, "message" => "Status tidak valid."]);
        exit;
    }

    $orders = loadOrders();
    $found = false;
    foreach ($orders as &$o) {
        if ($o["id"] === $id) {
            $o["status"] = $statusBaru;
            $found = true;
            break;
        }
    }
    unset($o);

    if ($found) {
        saveOrders($orders);
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => "Pesanan tidak ditemukan."]);
    }
    exit;
}

// method GET: read, kirim ringkasan statistik + daftar pesanan masuk
$orders = loadOrders();
$users = loadUsers();

$today = date("d M Y");
$pesananHariIni = 0;
$belumDiproses = 0;
$pendapatanHariIni = 0;

foreach ($orders as $o) {
    // format tanggal tersimpan "d M Y H:i", jadi 11 karakter pertama = tanggalnya aja
    if (substr($o["tanggal"], 0, 11) === $today) {
        $pesananHariIni++;
        $pendapatanHariIni += $o["harga"] * $o["qty"];
    }
    if ($o["status"] === "Diterima") {
        $belumDiproses++;
    }
}

$jumlahPelanggan = 0;
foreach ($users as $u) {
    if ($u["role"] === "customer") {
        $jumlahPelanggan++;
    }
}

$pesananMasuk = [];
foreach (array_reverse($orders) as $o) {
    $namaCustomer = isset($users[$o["username"]]) ? $users[$o["username"]]["nama"] : $o["username"];
    $pesananMasuk[] = [
        "id" => $o["id"], "customer" => $namaCustomer, "layanan" => $o["layanan"],
        "qty" => $o["qty"], "satuan" => $o["satuan"], "total" => rupiah($o["harga"] * $o["qty"]),
        "tanggal" => $o["tanggal"], "status" => $o["status"]
    ];
}

echo json_encode([
    "success" => true,
    "nama" => $_SESSION["nama"],
    "pesananHariIni" => $pesananHariIni,
    "belumDiproses" => $belumDiproses,
    "pendapatanHariIni" => rupiah($pendapatanHariIni),
    "jumlahPelanggan" => $jumlahPelanggan,
    "statusOptions" => $statusOptions,
    "pesananMasuk" => $pesananMasuk
]);
