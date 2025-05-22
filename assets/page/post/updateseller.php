<?php
session_start();

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id = $_POST['id'];
    $sellername = $_POST['sellername'] ?? null;
    $sellerpass = $_POST['sellpass'] ?? null;
    $sellerphone = $_POST['sellerphone'] ?? null;
    $sellerbalance = $_POST['sellerbalance'] ?? null;

    $path = "../json/sellerdata.json";
    $json = file_get_contents($path);
    $data = json_decode($json, true);
    $updated = false;

    foreach ($data as &$entry) {
        if ($entry['id'] == $id) {
            // Update hanya jika ada input
            if (!empty($sellername)) {
                $entry['sellername'] = $sellername;
            }
            if (!empty($sellerpass)) {
                $entry['sellerpasswd'] = hashPassword($sellerpass);
            }
            if (!empty($sellerphone)) {
                $entry['sellerphone'] = $sellerphone;
            }
            if (!empty($sellerbalance)) {
                $entry['sellerbalance'] = $sellerbalance;
            }
            $updated = true;
            break;
        }
    }

    if ($updated) {
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
        $_SESSION['success'] = "Data seller berhasil diperbarui!";
    } else {
        $_SESSION['error'] = "Data seller tidak ditemukan!";
    }

    header("Location: ../admin/seller.php");
    exit;
}
?>
