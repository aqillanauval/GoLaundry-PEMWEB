<?php
session_start();

// buat logout, tinggal akses login.php?logout=1
if (isset($_GET["logout"])) {
    session_destroy();
    header("Location: login.html");
    exit;
}

header('Content-Type: application/json');

$users = [
    "admin@golaundry.com" => ["password" => "admin123", "nama" => "Admin GoLaundry", "role" => "admin"],
    "pelanggan@golaundry.com" => ["password" => "pelanggan123", "nama" => "Pelanggan GoLaundry", "role" => "customer"]
];

// Tambahkan akun yang sudah daftar lewat register.php
if (file_exists("users.txt")) {
    foreach (file("users.txt", FILE_IGNORE_NEW_LINES) as $baris) {
        $data = explode(",", $baris);
        if (count($data) === 4) {
            $users[$data[0]] = ["password" => $data[1], "nama" => $data[2], "role" => $data[3]];
        }
    }
}

$input = json_decode(file_get_contents("php://input"), true);
$username = trim($input["username"] ?? "");
$password = $input["password"] ?? "";
$role = $input["role"] ?? "";
$ingat = $input["ingat"] ?? false;

if (isset($users[$username]) && $users[$username]["password"] === $password && $users[$username]["role"] === $role) {
    // simpan status login di session
    $_SESSION["user"] = $username;
    $_SESSION["nama"] = $users[$username]["nama"];
    $_SESSION["role"] = $role;

    // kalau centang "ingat saya", simpan username di cookie selama 30 hari
    if ($ingat) {
        setcookie("username", $username, time() + 60 * 60 * 24 * 30, "/");
    }

    echo json_encode([
        "success" => true,
        "redirect" => $role === "admin" ? "dashboard-admin.html" : "dashboard-customer.html"
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Email, password, atau role salah."]);
}
