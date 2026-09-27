<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "admin") {
    echo json_encode(["success" => false]);
    exit;
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

function saveLayanan($layanan) {
    $lines = [];
    foreach ($layanan as $l) {
        $lines[] = implode(",", [$l["id"], $l["nama"], $l["harga"], $l["satuan"]]);
    }
    file_put_contents("layanan.txt", implode(PHP_EOL, $lines) . (count($lines) ? PHP_EOL : ""));
}

$method = $_SERVER["REQUEST_METHOD"];

// bagian CRUD layanan: tambah, edit, hapus
if ($method === "POST") {
    $input = json_decode(file_get_contents("php://input"), true);
    $action = $input["action"] ?? "";
    $layanan = loadLayanan();

    // create layanan baru
    if ($action === "tambah") {
        $nama = trim($input["nama"] ?? "");
        $harga = intval($input["harga"] ?? 0);
        $satuan = trim($input["satuan"] ?? "");

        if ($nama === "" || $harga <= 0 || $satuan === "") {
            echo json_encode(["success" => false, "message" => "Nama, harga, dan satuan wajib diisi dengan benar."]);
            exit;
        }

        $layanan[] = ["id" => uniqid("LYN"), "nama" => $nama, "harga" => $harga, "satuan" => $satuan];
        saveLayanan($layanan);
        echo json_encode(["success" => true]);
        exit;
    }

    // update layanan
    if ($action === "edit") {
        $id = $input["id"] ?? "";
        $nama = trim($input["nama"] ?? "");
        $harga = intval($input["harga"] ?? 0);
        $satuan = trim($input["satuan"] ?? "");

        if ($nama === "" || $harga <= 0 || $satuan === "") {
            echo json_encode(["success" => false, "message" => "Nama, harga, dan satuan wajib diisi dengan benar."]);
            exit;
        }

        $found = false;
        foreach ($layanan as &$l) {
            if ($l["id"] === $id) {
                $l["nama"] = $nama;
                $l["harga"] = $harga;
                $l["satuan"] = $satuan;
                $found = true;
                break;
            }
        }
        unset($l);

        if ($found) {
            saveLayanan($layanan);
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "message" => "Layanan tidak ditemukan."]);
        }
        exit;
    }

    // delete layanan
    if ($action === "hapus") {
        $id = $input["id"] ?? "";
        $sisa = [];
        foreach ($layanan as $l) {
            if ($l["id"] !== $id) {
                $sisa[] = $l;
            }
        }
        saveLayanan($sisa);
        echo json_encode(["success" => true]);
        exit;
    }

    echo json_encode(["success" => false, "message" => "Aksi tidak dikenal."]);
    exit;
}

// method GET: read, kirim semua layanan
echo json_encode(["success" => true, "layanan" => loadLayanan()]);
