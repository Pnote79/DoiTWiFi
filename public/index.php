<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ==============================
// LOAD HELPER DULU (WAJIB)
// ==============================
require_once __DIR__.'/../app/Helpers/url.php';

// ==============================
// BARU ROUTER
// ==============================
require_once __DIR__ . '/../routes/web.php';