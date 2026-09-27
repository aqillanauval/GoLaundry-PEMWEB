<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "customer") {
    echo json_encode(["success" => false]);
    exit;
}

$username = $_SESSION["user"];

// ambil daftar layanan dari data yang diisi admin di menu Manajemen Layanan
function loadLayananList() {
    $layananList = [];
    if (file_exists("layanan.txt")) {
        foreach (file("layanan.txt", FILE_IGNORE_NEW_LINES) as $baris) {
            $data = explode(",", $baris);
            if (count($data) === 4) {
                $layananList[$data[0]] = ["nama" => $data[1], "harga" => (int)$data[2], "satuan" => $data[3]];
            }
        }
    }
    return $layananList;
}

$layananList = loadLayananList();

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

$method = $_SERVER["REQUEST_METHOD"];

// bagian ini yang isinya CRUD buat pesanan: create, update, sama delete (batalkan)
if ($method === "POST") {
    $input = json_decode(file_get_contents("php://input"), true);
    $action = $input["action"] ?? "create";
    $orders = loadOrders();

    if ($action === "create" || $action === "update") {
        $layananKey = $input["layanan"] ?? "";
        $qty = floatval($input["qty"] ?? 0);
        $catatan = str_replace(",", " ", trim($input["catatan"] ?? ""));

        if (!isset($layananList[$layananKey])) {
            echo json_encode(["success" => false, "message" => "Silakan pilih layanan yang valid."]);
            exit;
        }
        if ($qty <= 0) {
            echo json_encode(["success" => false, "message" => "Jumlah harus lebih dari 0."]);
            exit;
        }

        $layanan = $layananList[$layananKey];

        // create: bikin pesanan baru
        if ($action === "create") {
            $orders[] = [
                "id" => uniqid("ORD"), "username" => $username, "layanan" => $layanan["nama"],
                "harga" => $layanan["harga"], "satuan" => $layanan["satuan"], "qty" => $qty,
                "catatan" => $catatan, "status" => "Diterima", "tanggal" => date("d M Y H:i")
            ];
            saveOrders($orders);
            echo json_encode(["success" => true]);
        } else {
            // update: ubah pesanan yang sudah ada, cuma boleh kalau statusnya masih Diterima
            $id = $input["id"] ?? "";
            $found = false;
            foreach ($orders as &$o) {
                if ($o["id"] === $id && $o["username"] === $username && $o["status"] === "Diterima") {
                    $o["layanan"] = $layanan["nama"];
                    $o["harga"] = $layanan["harga"];
                    $o["satuan"] = $layanan["satuan"];
                    $o["qty"] = $qty;
                    $o["catatan"] = $catatan;
                    $found = true;
                    break;
                }
            }
            unset($o);
            if ($found) {
                saveOrders($orders);
                echo json_encode(["success" => true]);
            } else {
                echo json_encode(["success" => false, "message" => "Pesanan tidak ditemukan atau sudah diproses."]);
            }
        }
        exit;
    }

    // delete: batalkan pesanan (cuma boleh kalau masih Diterima)
    if ($action === "cancel") {
        $id = $input["id"] ?? "";
        $sisaOrder = [];
        foreach ($orders as $o) {
            $hapus = ($o["id"] === $id && $o["username"] === $username && $o["status"] === "Diterima");
            if (!$hapus) {
                $sisaOrder[] = $o;
            }
        }
        saveOrders($sisaOrder);
        echo json_encode(["success" => true]);
        exit;
    }

    echo json_encode(["success" => false, "message" => "Aksi tidak dikenal."]);
    exit;
}

// method GET: read, kirim daftar layanan + pesanan punya user ini
$semuaOrder = loadOrders();
$myOrders = [];
foreach ($semuaOrder as $o) {
    if ($o["username"] === $username) {
        $myOrders[] = $o;
    }
}
$myOrders = array_reverse($myOrders);

echo json_encode([
    "success" => true,
    "nama" => $_SESSION["nama"],
    "layanan" => $layananList,
    "orders" => $myOrders
]);
