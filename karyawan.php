<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "admin") {
    echo json_encode(["success" => false]);
    exit;
}

function loadKaryawan() {
    $karyawan = [];
    if (file_exists("karyawan.txt")) {
        foreach (file("karyawan.txt", FILE_IGNORE_NEW_LINES) as $baris) {
            $data = explode(",", $baris);
            if (count($data) === 4) {
                $karyawan[] = ["id" => $data[0], "nama" => $data[1], "jabatan" => $data[2], "no_hp" => $data[3]];
            }
        }
    }
    return $karyawan;
}

function saveKaryawan($karyawan) {
    $lines = [];
    foreach ($karyawan as $k) {
        $lines[] = implode(",", [$k["id"], $k["nama"], $k["jabatan"], $k["no_hp"]]);
    }
    file_put_contents("karyawan.txt", implode(PHP_EOL, $lines) . (count($lines) ? PHP_EOL : ""));
}

$method = $_SERVER["REQUEST_METHOD"];

if ($method === "POST") {
    $input = json_decode(file_get_contents("php://input"), true);
    $action = $input["action"] ?? "";
    $karyawan = loadKaryawan();

    if ($action === "tambah") {
        $nama = trim($input["nama"] ?? "");
        $jabatan = trim($input["jabatan"] ?? "");
        $noHp = trim($input["no_hp"] ?? "");

        if ($nama === "" || $jabatan === "" || $noHp === "") {
            echo json_encode(["success" => false, "message" => "Nama, jabatan, dan no HP wajib diisi."]);
            exit;
        }

        $karyawan[] = ["id" => uniqid("KRY"), "nama" => $nama, "jabatan" => $jabatan, "no_hp" => $noHp];
        saveKaryawan($karyawan);
        echo json_encode(["success" => true]);
        exit;
    }

    if ($action === "edit") {
        $id = $input["id"] ?? "";
        $nama = trim($input["nama"] ?? "");
        $jabatan = trim($input["jabatan"] ?? "");
        $noHp = trim($input["no_hp"] ?? "");

        if ($nama === "" || $jabatan === "" || $noHp === "") {
            echo json_encode(["success" => false, "message" => "Nama, jabatan, dan no HP wajib diisi."]);
            exit;
        }

        $found = false;
        foreach ($karyawan as &$k) {
            if ($k["id"] === $id) {
                $k["nama"] = $nama;
                $k["jabatan"] = $jabatan;
                $k["no_hp"] = $noHp;
                $found = true;
                break;
            }
        }
        unset($k);

        if ($found) {
            saveKaryawan($karyawan);
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "message" => "Karyawan tidak ditemukan."]);
        }
        exit;
    }

    if ($action === "hapus") {
        $id = $input["id"] ?? "";
        $sisa = [];
        foreach ($karyawan as $k) {
            if ($k["id"] !== $id) {
                $sisa[] = $k;
            }
        }
        saveKaryawan($sisa);
        echo json_encode(["success" => true]);
        exit;
    }

    echo json_encode(["success" => false, "message" => "Aksi tidak dikenal."]);
    exit;
}

echo json_encode(["success" => true, "karyawan" => loadKaryawan()]);
