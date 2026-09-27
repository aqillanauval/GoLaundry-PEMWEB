<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["user"])) {
    echo json_encode(["success" => false]);
    exit;
}

$username = $_SESSION["user"];

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

function saveUsers($users) {
    $lines = [];
    foreach ($users as $uname => $u) {
        $lines[] = implode(",", [$uname, $u["password"], $u["nama"], $u["role"]]);
    }
    file_put_contents("users.txt", implode(PHP_EOL, $lines) . (count($lines) ? PHP_EOL : ""));
}

$users = loadUsers();

if (!isset($users[$username])) {
    echo json_encode(["success" => false]);
    exit;
}

$method = $_SERVER["REQUEST_METHOD"];

// update data diri, cuma nama yang boleh diubah di sini
if ($method === "POST") {
    $input = json_decode(file_get_contents("php://input"), true);
    $namaBaru = trim($input["nama"] ?? "");

    if ($namaBaru === "") {
        echo json_encode(["success" => false, "message" => "Nama tidak boleh kosong."]);
        exit;
    }

    $users[$username]["nama"] = $namaBaru;
    saveUsers($users);
    $_SESSION["nama"] = $namaBaru;

    echo json_encode(["success" => true]);
    exit;
}

// method GET: read data diri buat ditampilin di form
echo json_encode([
    "success" => true,
    "username" => $username,
    "nama" => $users[$username]["nama"],
    "role" => $users[$username]["role"]
]);
