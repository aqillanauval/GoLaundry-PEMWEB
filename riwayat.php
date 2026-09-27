<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "customer") {
    echo json_encode(["success" => false]);
    exit;
}

$username = $_SESSION["user"];

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

// ulasan pelanggan disimpan terpisah di ulasan.txt, formatnya: id_pesanan,rating,komentar
function loadUlasan() {
    $ulasan = [];
    if (file_exists("ulasan.txt")) {
        foreach (file("ulasan.txt", FILE_IGNORE_NEW_LINES) as $baris) {
            $data = explode(",", $baris, 3);
            if (count($data) === 3) {
                $ulasan[$data[0]] = ["rating" => $data[1], "komentar" => $data[2]];
            }
        }
    }
    return $ulasan;
}

function saveUlasan($ulasan) {
    $lines = [];
    foreach ($ulasan as $id => $u) {
        $lines[] = $id . "," . $u["rating"] . "," . $u["komentar"];
    }
    file_put_contents("ulasan.txt", implode(PHP_EOL, $lines) . (count($lines) ? PHP_EOL : ""));
}

$method = $_SERVER["REQUEST_METHOD"];

// bagian CRUD buat ulasan: bisa bikin baru, edit, sama hapus
if ($method === "POST") {
    $input = json_decode(file_get_contents("php://input"), true);
    $action = $input["action"] ?? "";
    $id = $input["id"] ?? "";

    // pesanan harus punya user ini sendiri dan statusnya sudah Selesai baru boleh diulas
    $orders = loadOrders();
    $milikSendiri = false;
    foreach ($orders as $o) {
        if ($o["id"] === $id && $o["username"] === $username && $o["status"] === "Selesai") {
            $milikSendiri = true;
            break;
        }
    }

    if (!$milikSendiri) {
        echo json_encode(["success" => false, "message" => "Pesanan tidak ditemukan atau belum selesai."]);
        exit;
    }

    $ulasan = loadUlasan();

    // create sama update ulasan caranya sama aja, tinggal timpa isinya
    if ($action === "buat_ulasan" || $action === "edit_ulasan") {
        $rating = trim($input["rating"] ?? "");
        $komentar = str_replace(",", " ", trim($input["komentar"] ?? ""));

        if ($rating === "" || $komentar === "") {
            echo json_encode(["success" => false, "message" => "Rating dan komentar wajib diisi."]);
            exit;
        }

        $ulasan[$id] = ["rating" => $rating, "komentar" => $komentar];
        saveUlasan($ulasan);
        echo json_encode(["success" => true]);
        exit;
    }

    // delete ulasan
    if ($action === "hapus_ulasan") {
        unset($ulasan[$id]);
        saveUlasan($ulasan);
        echo json_encode(["success" => true]);
        exit;
    }

    echo json_encode(["success" => false, "message" => "Aksi tidak dikenal."]);
    exit;
}

// method GET: read, kirim riwayat transaksi punya user ini beserta ulasannya kalau ada
$semuaOrder = loadOrders();
$ulasan = loadUlasan();
$myOrders = [];
foreach ($semuaOrder as $o) {
    if ($o["username"] === $username) {
        $o["rating"] = isset($ulasan[$o["id"]]) ? $ulasan[$o["id"]]["rating"] : "";
        $o["komentar"] = isset($ulasan[$o["id"]]) ? $ulasan[$o["id"]]["komentar"] : "";
        $myOrders[] = $o;
    }
}
$myOrders = array_reverse($myOrders);

echo json_encode(["success" => true, "orders" => $myOrders]);
