<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/login");
    exit;
}
$monthNames = [
    '01'=>'Januari', '02'=>'Februari', '03'=>'Maret', '04'=>'April', 
    '05'=>'Mei', '06'=>'Juni', '07'=>'Juli', '08'=>'Agustus', 
    '09'=>'September', '10'=>'Oktober', '11'=>'November', '12'=>'Desember'
];
// Ambil nama bulan berdasarkan filter (pastikan filter['month'] adalah angka 01-12)
$currentMonthName = $monthNames[$filter['month']] ?? $monthNames[date('m')];
?>

    <style>
        body { background-color: #0d1117; color: #c9d1d9; }
        .card-report { background: #161b22; border: 1px solid #30363d; border-radius: 10px; }
        .bg-tunggakan { background: linear-gradient(135deg, #842029 0%, #da3633 100%); }
        .form-control { background-color: #0d1117; border: 1px solid #30363d; color: white; }
        .nav-tabs .nav-link { color: #8b949e; border: none; font-weight: 600; }
        .nav-tabs .nav-link.active { background: transparent; color: #58a6ff; border-bottom: 3px solid #58a6ff; }
        .icon-circle { width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: rgba(88, 166, 255, 0.1); }
    </style>
	

<div class="container-fluid pt-4">
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card card-report bg-tunggakan text-white p-3 shadow">
                <small class="text-white-50 font-weight-bold">TOTAL BELUM BAYAR (DOITWIFI)</small>
                <h2 class="mb-0 font-weight-bold">Rp <?= number_format($unpaid, 0, ',', '.') ?></h2>
            </div>
        </div>
        <div class="col-md-8 mb-3">
            <div class="card card-report p-3">
                <form method="GET" action="" class="form-row align-items-end">
                    <div class="col-md-3">
                        <label class="small font-weight-bold text-muted">TANGGAL</label>
                        <select name="date" class="form-control">
                            <?php for($i=1; $i<=31; $i++): $d = str_pad($i, 2, '0', STR_PAD_LEFT); ?>
                                <option value="<?= $d ?>" <?= $filter['date'] == $d ? 'selected' : '' ?>><?= $d ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="small font-weight-bold text-muted">BULAN</label>
                        <select name="month" class="form-control">
                            <?php foreach($monthNames as $num => $name): ?>
                                <option value="<?= $num ?>" <?= $filter['month'] == $num ? 'selected' : '' ?>><?= $name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="small font-weight-bold text-muted">TAHUN</label>
                        <select name="year" class="form-control">
                            <?php for($y=date('Y'); $y>=2023; $y--): ?>
                                <option value="<?= $y ?>" <?= $filter['year'] == $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary btn-block mt-2"><i class="fas fa-filter"></i> FILTER</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs mb-4" id="reportTab" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#harian">HARIAN (<?= $filter['date'].' '.$currentMonthName ?>)</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#bulanan">BULANAN (<?= $currentMonthName.' '.$filter['year'] ?>)</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tahunan">TAHUNAN (<?= $filter['year'] ?>)</a></li>
    </ul>

    <div class="tab-content">
        <?php 
        $periods = [
            'harian' => $daily, 
            'bulanan' => $monthly, 
            'tahunan' => $yearly
        ];
        foreach ($periods as $id => $val): 
        ?>
        <div class="tab-pane fade <?= $id == 'harian' ? 'show active' : '' ?>" id="<?= $id ?>">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="card card-report p-3 border-left-primary" style="border-left: 4px solid #007bff !important;">
                        <div class="d-flex align-items-center">
                            <div class="icon-circle mr-3"><i class="fas fa-ticket-alt text-primary"></i></div>
                            <div>
                                <small class="text-muted font-weight-bold">MIKHMON</small>
                                <h4 class="mb-0">Rp <?= number_format($val['mikhmon'], 0, ',', '.') ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card card-report p-3 border-left-success" style="border-left: 4px solid #28a745 !important;">
                        <div class="d-flex align-items-center">
                            <div class="icon-circle mr-3"><i class="fas fa-wifi text-success"></i></div>
                            <div>
                                <small class="text-muted font-weight-bold">DOITWIFI</small>
                                <h4 class="mb-0">Rp <?= number_format($val['doitwifi'], 0, ',', '.') ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card card-report p-3 bg-primary text-white border-0 shadow">
                        <div class="d-flex align-items-center">
                            <div class="icon-circle bg-white-50 mr-3"><i class="fas fa-coins text-white"></i></div>
                            <div>
                                <small class="text-white-50 font-weight-bold">TOTAL INCOME</small>
                                <h4 class="mb-0 font-weight-bold">Rp <?= number_format($val['total'], 0, ',', '.') ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php
$content = ob_get_clean();
include dirname(__DIR__,2) . '/layouts/layout.php';