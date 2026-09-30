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
    $ok = file_put_contents("orders.txt", implode(PHP_EOL, $lines) . (count($lines) ? PHP_EOL : ""), LOCK_EX);
    return $ok !== false;
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

// koma dan baris baru dibuang karena orders.txt memakai koma sebagai pemisah kolom
function bersih($s) {
    return str_replace([",", "\r", "\n"], " ", trim((string)$s));
}

// format tanggal di orders.txt: "26 Sep 2026 16:43". Input form (type=date) berformat YYYY-MM-DD
function tanggalDariForm($inputTgl, $tanggalLama = "") {
    if ($inputTgl === "") {
        return $tanggalLama !== "" ? $tanggalLama : date("d M Y H:i");
    }
    $ts = strtotime($inputTgl);
    if ($ts === false) {
        return $tanggalLama !== "" ? $tanggalLama : date("d M Y H:i");
    }
    // edit tapi harinya tidak diubah: pertahankan tanggal + jam aslinya
    if ($tanggalLama !== "") {
        $tsLama = strtotime($tanggalLama);
        if ($tsLama !== false && date("Y-m-d", $tsLama) === date("Y-m-d", $ts)) {
            return $tanggalLama;
        }
    }
    return date("d M Y", $ts) . " " . date("H:i");
}

function tanggalUntukForm($tanggal) {
    $ts = strtotime($tanggal);
    return $ts === false ? "" : date("Y-m-d", $ts);
}

$method = $_SERVER["REQUEST_METHOD"];

// ===== POST: tambah, ubah, hapus =====
if ($method === "POST") {
    $input = json_decode(file_get_contents("php://input"), true);
    $action = $input["action"] ?? "";
    $id = $input["id"] ?? "";

    $orders = loadOrders();

    // HAPUS (kode asli): request lama hanya mengirim {id} tanpa action, jadi dianggap hapus
    if ($action === "" || $action === "hapus") {
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

    // CREATE & UPDATE: ambil dan validasi input form
    if ($action === "tambah" || $action === "ubah") {
        $username = bersih($input["username"] ?? "");
        $layanan  = bersih($input["layanan"] ?? "");
        $harga    = (int)($input["harga"] ?? 0);
        $satuan   = bersih($input["satuan"] ?? "");
        $qty      = (float)($input["qty"] ?? 0);
        $catatan  = bersih($input["catatan"] ?? "");
        $status   = bersih($input["status"] ?? "");
        $tanggalInput = bersih($input["tanggal"] ?? "");

        if ($username === "" || $layanan === "" || $satuan === "" || $status === "" || $harga <= 0 || $qty <= 0) {
            echo json_encode(["success" => false, "message" => "Semua kolom wajib diisi (kecuali catatan), harga dan qty harus lebih dari 0."]);
            exit;
        }

        // username = nama customer bebas (boleh walk-in); kalau diisi email pelanggan terdaftar,
        // nama lengkapnya otomatis dipakai di tabel oleh bagian GET di bawah

        // CREATE
        if ($action === "tambah") {
            $orders[] = [
                "id" => "ORD" . uniqid(),
                "username" => $username, "layanan" => $layanan, "harga" => $harga,
                "satuan" => $satuan, "qty" => $qty, "catatan" => $catatan,
                "status" => $status, "tanggal" => tanggalDariForm($tanggalInput)
            ];
            if (!saveOrders($orders)) {
                echo json_encode(["success" => false, "message" => "Gagal menulis orders.txt, cek izin tulis (permission) folder/file-nya."]);
                exit;
            }
            echo json_encode(["success" => true]);
            exit;
        }

        // UPDATE
        foreach ($orders as $i => $o) {
            if ($o["id"] === $id) {
                $orders[$i] = [
                    "id" => $id,
                    "username" => $username, "layanan" => $layanan, "harga" => $harga,
                    "satuan" => $satuan, "qty" => $qty, "catatan" => $catatan,
                    "status" => $status, "tanggal" => tanggalDariForm($tanggalInput, $o["tanggal"])
                ];
                if (!saveOrders($orders)) {
                    echo json_encode(["success" => false, "message" => "Gagal menulis orders.txt, cek izin tulis (permission) folder/file-nya."]);
                    exit;
                }
                echo json_encode(["success" => true]);
                exit;
            }
        }
        echo json_encode(["success" => false, "message" => "Transaksi tidak ditemukan."]);
        exit;
    }

    echo json_encode(["success" => false, "message" => "Aksi tidak dikenal."]);
    exit;
}

// ===== GET ?id=... : ambil 1 transaksi lengkap buat mengisi form edit =====
if (isset($_GET["id"])) {
    foreach (loadOrders() as $o) {
        if ($o["id"] === $_GET["id"]) {
            $o["tanggal"] = tanggalUntukForm($o["tanggal"]);
            echo json_encode(["success" => true, "order" => $o]);
            exit;
        }
    }
    echo json_encode(["success" => false, "message" => "Transaksi tidak ditemukan."]);
    exit;
}

// ===== GET biasa: read, kirim semua transaksi dari semua pelanggan buat direkap admin =====
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
        "qty" => $o["qty"], "satuan" => $o["satuan"], "catatan" => $o["catatan"], "total" => rupiah($subtotal),
        "tanggal" => $o["tanggal"], "status" => $o["status"]
    ];
}

echo json_encode([
    "success" => true,
    "totalTransaksi" => count($transaksi),
    "totalPendapatan" => rupiah($totalPendapatan),
    "transaksi" => $transaksi
]);