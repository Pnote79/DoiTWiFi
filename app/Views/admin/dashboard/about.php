<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Proteksi halaman (Pastikan konstanta BASE_URL sudah didefinisikan di config)
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . (defined('BASE_URL') ? BASE_URL : '') . "/login");
    exit;
}

// Link DANA Anda
$dana_id = "8u8o9e7";
$dana_url = "https://link.dana.id/qr/" . $dana_id;
// Menggunakan QuickChart.io (Lebih stabil untuk akses lokal/ISP)
$qr_api = "https://quickchart.io/qr?text=" . urlencode($dana_url) . "&size=180&margin=1";
?>

<style>
    .about-card {
        background: #161b22; 
        border: 1px solid #30363d; 
        border-radius: 12px; 
        color: #c9d1d9;
        box-shadow: 0 8px 24px rgba(0,0,0,0.2);
    }
    .badge-dev { font-size: 14px; font-weight: 500; border-radius: 6px; }
    .section-title { color: #58a6ff; font-weight: 600; margin-bottom: 15px; }
    .qr-box { 
        background: white; 
        padding: 10px; 
        border-radius: 10px; 
        display: inline-block;
        transition: transform 0.3s;
        border: 3px solid #1abc9c;
    }
    .qr-box:hover { transform: scale(1.05); }
    .tech-list li { margin-bottom: 8px; color: #8b949e; }
    .tech-list i { color: #1abc9c; width: 20px; }
    hr { border-top: 1px solid #30363d; }
</style>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-9 col-lg-8">
            <div class="card about-card">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-5">
                        <div class="mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="#1abc9c" class="bi bi-info-circle-fill" viewBox="0 0 16 16">
                                <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2"/>
                            </svg>
                        </div>
                        <h1 class="h2 font-weight-bold mb-1" style="color: #1abc9c;">About DOiTWiFi</h1>
                        <p class="text-muted">Management Platform System — v1.0.0 Stable</p>
                    </div>

                    <div class="row">
                        <div class="col-lg-7">
                            <h5 class="section-title">🚀 Apa itu DOiTWiFi?</h5>
                            <p style="font-size: 0.95rem;">
                                <strong>DOiTWiFi</strong> (KWHotspot) adalah platform manajemen ISP dan Hotspot mandiri (RT/RW Net) yang dirancang untuk efisiensi operasional MikroTik. 
                                Aplikasi ini merupakan hasil kolaborasi teknis antara <strong>pnote</strong> sebagai pengembang utama dengan asistensi kecerdasan buatan dari <strong>Gemini</strong> dan <strong>GPT</strong>.
                            </p>

                            <h5 class="section-title mt-4">🛠️ Teknologi & Fitur</h5>
                            <ul class="list-unstyled tech-list" style="font-size: 0.9rem;">
                                <li><i class="fas fa-check-circle"></i> <strong>RouterOS API:</strong> Integrasi v6 & v7.</li>
                                <li><i class="fas fa-check-circle"></i> <strong>Monitoring:</strong> Real-time traffic & resources.</li>
                                <li><i class="fas fa-check-circle"></i> <strong>Billing:</strong> Laporan pendapatan otomatis.</li>
							    <li><i class="fas fa-check-circle"></i> <strong>Hotspot:</strong> Generate dan Seller Mode</li>
                                <li><i class="fas fa-check-circle"></i> <strong>TR-069:</strong> Manajemen ONT Multi-vendor.</li>
                                <li><i class="fas fa-check-circle"></i> <strong>Telegram:</strong> Notifikasi log & status 24/7.</li>
                            </ul>
                        </div>

                        <div class="col-lg-5 text-center mt-4 mt-lg-0">
                            <div class="p-3" style="background: rgba(255,255,255,0.03); border-radius: 12px; border: 1px dashed #30363d;">
                                <h6 class="mb-3 text-warning"><i class="fas fa-coffee"></i>☕ Dukung Developer</h6>
                                
                                <div class="qr-box mb-3">
                                    <img src="<?= $qr_api ?>" alt="QR DANA" class="img-fluid" style="width: 160px; height: 160px;">
                                </div>
                                
                                <p class="mb-1 font-weight-bold text-white">DANA: 081328969125</p>
                                <p class="small text-muted mb-3">A.N: SURATNA</p>
                                <a href="<?= $dana_url ?>" target="_blank" class="btn btn-sm btn-outline-primary btn-block">
                                    <i class="fas fa-external-link-alt mr-1"></i> Buka Aplikasi DANA
                                </a>
                            </div>
                        </div>
                    </div>

                    <hr class="my-5">

                    <div class="row align-items-center">
                        <div class="col-md-12 text-center">
                            <h5 class="section-title mb-4">👥 Project Collaborators</h5>
                            <div class="d-flex flex-wrap justify-content-center">
                                <span class="badge badge-info badge-dev p-2 m-2">pnote (Lead Dev)</span>
                                <span class="badge badge-secondary badge-dev p-2 m-2">Gemini (Google AI)</span>
                                <span class="badge badge-dark badge-dev p-2 m-2" style="background: #333;">GPT (OpenAI)</span>
                            </div>
                            <p class="mt-4 small italic text-muted">"Coding with AI, Building for Community."</p>
                        </div>
                    </div>

                    <div class="text-center mt-5">
                        <p class="small text-muted mb-0">&copy; <?= date('Y') ?> <strong>DOiTWiFi</strong> Project. All Rights Reserved.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>