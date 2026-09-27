<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["user"])) {
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

// ambil id pesanan yang mau ditampilkan notanya dari url, contoh: nota.php?id=ORD123
$id = $_GET["id"] ?? "";

$orders = loadOrders();
$order = null;
foreach ($orders as $o) {
    if ($o["id"] === $id) {
        $order = $o;
        break;
    }
}

if (!$order) {
    echo json_encode(["success" => false, "message" => "Nota tidak ditemukan."]);
    exit;
}

// customer cuma boleh buka nota pesanan dia sendiri, kalau admin boleh buka semua
if ($_SESSION["role"] === "customer" && $order["username"] !== $_SESSION["user"]) {
    echo json_encode(["success" => false, "message" => "Nota tidak ditemukan."]);
    exit;
}

$users = loadUsers();
$namaCustomer = isset($users[$order["username"]]) ? $users[$order["username"]]["nama"] : $order["username"];
$total = $order["harga"] * $order["qty"];

echo json_encode([
    "success" => true,
    "role" => $_SESSION["role"],
    "id" => $order["id"],
    "tanggal" => $order["tanggal"],
    "customer" => $namaCustomer,
    "status" => $order["status"],
    "layanan" => $order["layanan"],
    "qty" => $order["qty"],
    "satuan" => $order["satuan"],
    "harga" => rupiah($order["harga"]),
    "catatan" => $order["catatan"],
    "total" => rupiah($total)
]);
