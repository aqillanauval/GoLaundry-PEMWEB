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

// simpan akun baru ke users.txt, formatnya: username,password,nama,role
$line = $username . "," . $password . "," . $nama . "," . $role . PHP_EOL;
file_put_contents("users.txt", $line, FILE_APPEND);

echo json_encode(["success" => true]);
