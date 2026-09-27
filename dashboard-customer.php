<?php
session_start();
header('Content-Type: application/json');

// kalau belum login atau bukan customer, tolak
if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "customer") {
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

function rupiah($n) {
    return "Rp " . number_format($n, 0, ",", ".");
}

$username = $_SESSION["user"];

// ambil pesanan punya user ini saja, urutkan dari yang terbaru
$semuaOrder = loadOrders();
$myOrders = [];
foreach ($semuaOrder as $o) {
    if ($o["username"] === $username) {
        $myOrders[] = $o;
    }
}
$myOrders = array_reverse($myOrders);

// cari pesanan yang masih aktif (belum selesai/diambil) buat ditampilin statusnya
$statusSteps = ["Diterima", "Dicuci", "Selesai", "Diambil"];
$activeOrder = null;
foreach ($myOrders as $o) {
    if ($o["status"] !== "Selesai" && $o["status"] !== "Diambil") {
        $activeOrder = $o;
        break;
    }
}

$currentStep = 0;
if ($activeOrder) {
    $cari = array_search($activeOrder["status"], $statusSteps);
    if ($cari !== false) {
        $currentStep = $cari;
    }
}

// ambil 3 transaksi terakhir buat ditampilin di dashboard
$recent = array_slice($myOrders, 0, 3);
$recentFormatted = [];
foreach ($recent as $o) {
    $recentFormatted[] = [
        "tanggal" => $o["tanggal"],
        "layanan" => $o["layanan"],
        "total" => rupiah($o["harga"] * $o["qty"]),
        "status" => $o["status"]
    ];
}

echo json_encode([
    "success" => true,
    "nama" => $_SESSION["nama"],
    "statusSteps" => $statusSteps,
    "currentStep" => $currentStep,
    "hasActiveOrder" => $activeOrder !== null,
    "recent" => $recentFormatted
]);
