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

// bagian kelola transaksi punya admin: hapus data yang salah input
if ($method === "POST") {
    $input = json_decode(file_get_contents("php://input"), true);
    $id = $input["id"] ?? "";

    $orders = loadOrders();
    $sisaOrder = [];
    foreach ($orders as $o) {
        if ($o["id"] !== $id) {
            $sisaOrder[] = $o;
        }
    }
    saveOrders($sisaOrder);

    echo json_encode(["success" => true]);
    exit;
}

// method GET: read, kirim semua transaksi dari semua pelanggan buat direkap admin
$orders = loadOrders();
$users = loadUsers();

$totalPendapatan = 0;
$transaksi = [];
foreach (array_reverse($orders) as $o) {
    $namaCustomer = isset($users[$o["username"]]) ? $users[$o["username"]]["nama"] : $o["username"];
    $subtotal = $o["harga"] * $o["qty"];
    $totalPendapatan += $subtotal;

    $transaksi[] = [
        "id" => $o["id"], "customer" => $namaCustomer, "layanan" => $o["layanan"],
        "qty" => $o["qty"], "satuan" => $o["satuan"], "total" => rupiah($subtotal),
        "tanggal" => $o["tanggal"], "status" => $o["status"]
    ];
}

echo json_encode([
    "success" => true,
    "totalTransaksi" => count($transaksi),
    "totalPendapatan" => rupiah($totalPendapatan),
    "transaksi" => $transaksi
]);
