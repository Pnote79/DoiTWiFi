<?php
require('../class/routeros_api.class.php');

// Ambil input dari form
$adminname     = isset($_POST['adminname']) ? trim($_POST['adminname']) : '';
$adminpass     = isset($_POST['adminpass']) ? trim($_POST['adminpass']) : '';
$sellername    = isset($_POST['sellname']) ? trim($_POST['sellname']) : '';
$sellerpasswd  = isset($_POST['sellpass']) ? trim($_POST['sellpass']) : '';
$sellerphone   = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$sellerbalance = isset($_POST['sellerbalance']) ? trim($_POST['sellerbalance']) : '';

// Lokasi file data
$dataFile = "../json/sellerdata.json";
$userList = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];

if (!is_array($userList)) {
    $userList = [];
}

// 🔐 Gunakan hash untuk password (optional, bisa di-nonaktifkan jika belum support login verification)
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// ==== 1️⃣ Overwrite Admin ====
if (!empty($adminname) && !empty($adminpass)) {
    $newAdmin = [
        "sellername"    => $adminname,
        "sellerpasswd"  => hashPassword($adminpass),
        "profile"       => "admin",
        "sellerphone"   => "-",
        "sellerbalance" => "-",
        "id"            => 0
    ];

    $adminReplaced = false;
    foreach ($userList as $index => $user) {
        if ($user['profile'] === 'admin') {
            $userList[$index] = $newAdmin;
            $adminReplaced = true;
            break;
        }
    }

    if (!$adminReplaced) {
        $userList[] = $newAdmin;
    }
}

// ==== 2️⃣ Tambahkan Seller jika belum ada ====
elseif (!empty($sellername) && !empty($sellerpasswd)) {
    // Validasi balance
    if (!is_numeric($sellerbalance)) {
        echo "<script>alert('❌ Balance harus berupa angka.'); window.history.back();</script>";
        exit;
    }

    // Cek duplikat seller
    $duplicate = false;
    foreach ($userList as $user) {
        if ($user['sellername'] === $sellername && $user['profile'] === 'seller') {
            $duplicate = true;
            break;
        }
    }

    if ($duplicate) {
        echo "<script>alert('⚠️ Seller dengan nama \"$sellername\" sudah ada!'); window.history.back();</script>";
        exit;
    }

    // Cari ID tertinggi yang sudah ada
    $maxId = 0;
    foreach ($userList as $user) {
        if (isset($user['id']) && is_numeric($user['id']) && $user['id'] > $maxId) {
            $maxId = $user['id'];
        }
    }
    $newId = $maxId + 1;

    $newSeller = [
        "sellername"    => $sellername,
        "sellerpasswd"  => hashPassword($sellerpasswd),
        "profile"       => "seller",
        "sellerphone"   => $sellerphone,
        "sellerbalance" => $sellerbalance,
        "id"            => $newId
    ];

    $userList[] = $newSeller;
}

// ==== 3️⃣ Simpan ke file ====
if (file_put_contents($dataFile, json_encode($userList, JSON_PRETTY_PRINT))) {
    header('Location: ../admin/admin.php');
    exit;
} else {
    echo "❌ Gagal menyimpan data.";
}
?>
