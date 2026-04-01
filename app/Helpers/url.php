<?php 
function base_url($path = ''){
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];

    // Otomatis deteksi folder project (DoITWiFi/public)
    $script = $_SERVER['SCRIPT_NAME'];
    $dir = str_replace('/index.php', '', $script);

    return $protocol . $host . $dir . '/' . ltrim($path, '/');
}

function asset($path){
    // Hapus 'assets/' jika user tidak sengaja menulisnya di parameter
    $cleanPath = ltrim($path, '/');
    if (strpos($cleanPath, 'assets/') === 0) {
        return base_url($cleanPath);
    }
    // Jika tidak ada kata assets, tambahkan otomatis
    return base_url('assets/' . $cleanPath);
}