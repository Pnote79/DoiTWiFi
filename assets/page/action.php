<?php

session_start();

if (isset($_GET['logout'])) {
    // Hapus semua data sesi
    session_unset();
    session_destroy();

    // Redirect ke halaman login
    header("Location:../../index.php");
    exit();
}

// Tambahan action lainnya bisa di-handle di bawah ini...
// Misal: reboot, reset, dll.

?>


