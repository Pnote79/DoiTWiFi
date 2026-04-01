<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Mulai tangkap output untuk dimasukkan ke variabel $content

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Proteksi halaman
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/login");
    exit;
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card" style="background: #161b22; border: 1px solid #30363d; border-radius: 12px; color: #c9d1d9;">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" fill="#1abc9c" class="bi bi-info-circle-fill mb-3" viewBox="0 0 16 16">
                        <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2"/>
                    </svg>
                    <h2 style="color: #1abc9c; font-weight: bold;">About DOiTWiFi</h2>
                    <p class="text-muted">Versi 1.0.0-Stable</p>
                </div>

                <hr style="border-top: 1px solid #30363d;">

                <div class="mt-4">
                    <h5>🚀 Apa itu DOiTWiFi?</h5>
                    <p>
                        <strong>DOiTWiFi</strong> adalah platform manajemen ISP dan Hotspot mandiri (RT/RW Net) yang dirancang untuk efisiensi operasional MikroTik. 
                        Aplikasi ini merupakan hasil kolaborasi teknis antara <strong>pnote</strong> sebagai pengembang utama, 
                        dengan asistensi kecerdasan buatan dari <strong>Gemini (Google)</strong> dan <strong>GPT (OpenAI)</strong>.
                    </p>
                </div>

                <div class="mt-4">
                    <h5>🛠️ Teknologi & Fitur</h5>
                    <ul style="padding-left: 20px; color: #8b949e;">
                        <li><strong>RouterOS API:</strong> Integrasi penuh dengan MikroTik v6 & v7.</li>
                        <li><strong>Monitoring:</strong> Real-time traffic, CPU, dan RAM resources.</li>
                        <li><strong>Billing:</strong> Laporan pendapatan harian & bulanan otomatis.</li>
                        <li><strong>TR-069:</strong> Manajemen ONT (ZTE, Huawei, Fiberhome) via ACS.</li>
                        <li><strong>Bot Telegram:</strong> Notifikasi log & status jaringan 24/7.</li>
                    </ul>
                </div>

                <div class="row mt-5">
                    <div class="col-md-6 text-center border-right" style="border-color: #30363d !important;">
                        <h5 class="mb-3">☕ Dukung Developer</h5>
                        <p class="small text-muted">Donasi Anda membantu pengembangan </p>
                        
                        <div class="p-3 d-inline-block bg-white mb-2" style="border-radius: 10px;">
                            <img src="https://chart.googleapis.com/chart?chs=180x180&cht=qr&chl=https://link.dana.id/qr/8u8o9e7&choe=UTF-8" 
                                 alt="QR DANA" class="img-fluid">
                        </div>
                        <p class="mb-0 font-weight-bold" style="color: #f1c40f;">DANA: 081328969125</p>
                        <small class="text-muted">A.N: (SURATNA)</small>
                    </div>

                    <div class="col-md-6 text-center">
                        <h5 class="mb-3">👥 Collaborators</h5>
                        <div class="d-flex flex-column align-items-center">
                            <div class="badge badge-info p-2 mb-2 w-75">pnote KWHotspot  (Lead Dev)</div>
                            <div class="badge badge-secondary p-2 mb-2 w-75">Gemini (Google AI)</div>
                            <div class="badge badge-dark p-2 mb-2 w-75" style="background: #444;">GPT (OpenAI)</div>
                        </div>
                        <p class="mt-3 small text-muted">"Coding with AI, Building for Community."</p>
                    </div>
                </div>

                <hr style="border-top: 1px solid #30363d;" class="mt-5">
                <div class="text-center">
                    <p class="small text-muted">&copy; <?= date('Y') ?> <strong>DOiTWiFi</strong> Project. All Rights Reserved.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .badge { font-size: 14px; font-weight: 500; }
    h5 { color: #58a6ff; font-weight: 600; }
    p { line-height: 1.6; }
</style>

