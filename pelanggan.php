<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "admin") {
    echo json_encode(["success" => false]);
    exit;
}

function loadUsers() {
    $users = [];
    if (file_exists("users.txt")) {
        foreach (file("users.txt", FILE_IGNORE_NEW_LINES) as $baris) {
            $data = explode(",", $baris);
            if (count($data) === 4) {
                $users[] = ["username" => $data[0], "password" => $data[1], "nama" => $data[2], "role" => $data[3]];
            }
        }
    }
    return $users;
}

function saveUsers($users) {
    $lines = [];
    foreach ($users as $u) {
        $lines[] = implode(",", [$u["username"], $u["password"], $u["nama"], $u["role"]]);
    }
    file_put_contents("users.txt", implode(PHP_EOL, $lines) . (count($lines) ? PHP_EOL : ""));
}

$method = $_SERVER["REQUEST_METHOD"];

if ($method === "POST") {
    $input = json_decode(file_get_contents("php://input"), true);
    $action = $input["action"] ?? "";
    $users = loadUsers();

    // create pelanggan baru
    if ($action === "tambah") {
        $nama = trim($input["nama"] ?? "");
        $username = trim($input["username"] ?? "");
        $password = $input["password"] ?? "";

        if ($nama === "" || $username === "" || $password === "") {
            echo json_encode(["success" => false, "message" => "Nama, username, dan password wajib diisi."]);
            exit;
        }

        foreach ($users as $u) {
            if ($u["username"] === $username) {
                echo json_encode(["success" => false, "message" => "Username sudah dipakai."]);
                exit;
            }
        }

        $users[] = ["username" => $username, "password" => $password, "nama" => $nama, "role" => "customer"];
        saveUsers($users);
        echo json_encode(["success" => true]);
        exit;
    }

    // update nama pelanggan
    if ($action === "edit") {
        $username = $input["username"] ?? "";
        $nama = trim($input["nama"] ?? "");

        if ($nama === "") {
            echo json_encode(["success" => false, "message" => "Nama wajib diisi."]);
            exit;
        }

        $found = false;
        foreach ($users as &$u) {
            if ($u["username"] === $username && $u["role"] === "customer") {
                $u["nama"] = $nama;
                $found = true;
                break;
            }
        }
        unset($u);

        if ($found) {
            saveUsers($users);
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "message" => "Pelanggan tidak ditemukan."]);
        }
        exit;
    }

    // hapus akun pelanggan
    if ($action === "hapus") {
        $username = $input["username"] ?? "";
        $sisa = [];
        foreach ($users as $u) {
            if (!($u["username"] === $username && $u["role"] === "customer")) {
                $sisa[] = $u;
            }
        }
        saveUsers($sisa);
        echo json_encode(["success" => true]);
        exit;
    }

    echo json_encode(["success" => false, "message" => "Aksi tidak dikenal."]);
    exit;
}

// method GET: read, kirim daftar pelanggan yang daftar lewat form
$users = loadUsers();
$pelanggan = [];
foreach ($users as $u) {
    if ($u["role"] === "customer") {
        $pelanggan[] = ["username" => $u["username"], "nama" => $u["nama"]];
    }
}

echo json_encode(["success" => true, "pelanggan" => $pelanggan]);
