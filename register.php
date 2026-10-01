<?php
header('Content-Type: application/json');

$input = json_decode(file_get_contents("php://input"), true);
$nama = trim($input["nama"] ?? "");
$username = trim($input["username"] ?? "");
$password = $input["password"] ?? "";
$role = $input["role"] ?? "";

if ($nama === "" || $username === "" || $password === "") {
    echo json_encode(["success" => false, "message" => "Semua data wajib diisi."]);
    exit;
}

if ($role !== "customer" && $role !== "admin") {
    echo json_encode(["success" => false, "message" => "Role tidak valid."]);
    exit;
}

// cek dulu username-nya udah kepake belum, termasuk 2 akun bawaan (admin & pelanggan)
$usernameDipakai = ($username === "admin@golaundry.com" || $username === "pelanggan@golaundry.com");

if (!$usernameDipakai && file_exists("users.txt")) {
    foreach (file("users.txt", FILE_IGNORE_NEW_LINES) as $baris) {
        $data = explode(",", $baris);
        if (count($data) === 4 && $data[0] === $username) {
            $usernameDipakai = true;
            break;
        }
    }
}

if ($usernameDipakai) {
    echo json_encode(["success" => false, "message" => "Email/No. HP sudah terdaftar."]);
    exit;
}

// simpan akun baru ke users.txt, formatnya: username,password,nama,role
$line = $username . "," . $password . "," . $nama . "," . $role . PHP_EOL;
file_put_contents("users.txt", $line, FILE_APPEND);

echo json_encode(["success" => true]);
