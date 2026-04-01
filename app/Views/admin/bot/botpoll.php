<?php
/**
 * ===================================================== 
 * FILE: botpoll.php
 * MODE: FULL FEATURE & STARLINK INTEGRATED
 * ===================================================== 
 */

date_default_timezone_set('Asia/Jakarta');
set_time_limit(0); 
ini_set('memory_limit', '512M'); 
gc_enable(); 

// Naik 3 tingkat: bot -> admin -> Views -> app -> Libraries
require_once __DIR__ . '/../../../Libraries/routeros_api.class.php';

// Sesuaikan juga path JSON jika disimpan di storage (sesuai struktur folder Anda)
$jsonPath = __DIR__ . '/../../../../storage/';; 
$starlinkUrl = "http://172.17.0.2:8081"; // URL Bridge gRPC Starlink

// --- HELPER DATA ---
function loadData($file) {
    global $jsonPath;
    $fullPath = $jsonPath . $file;
    if (!file_exists($fullPath)) return [];
    $content = @file_get_contents($fullPath);
    return json_decode($content, true) ?: [];
}

function saveData($file, $data) {
    global $jsonPath;
    file_put_contents($jsonPath . $file, json_encode($data, JSON_PRETTY_PRINT));
}

function getSeller($chat_id) {
    $sellers = loadData('sellerdata.json');
    foreach ($sellers as $key => $s) {
        if (isset($s['chat_id']) && $s['chat_id'] == $chat_id) {
            $s['idx_db'] = $key; 
            return $s;
        }
    }
    return null;
}

// --- STARLINK ENGINE (SISIIPAN) ---
function getStarlinkStatus() {
    global $starlinkUrl;
    $ch = curl_init($starlinkUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 3,
        CURLOPT_CONNECTTIMEOUT => 2
    ]);
    $res = curl_exec($ch);
    $err = curl_errno($ch);
    curl_close($ch);
    
    if ($err) return "❌ <b>Starlink Bridge Offline</b>\nPastikan gRPC server aktif di port 8081.";
    
    $raw = json_decode($res, true);
    if (!$raw || !isset($raw['dishGetStatus'])) return "❌ <b>Data Starlink Tidak Ditemukan</b>";
    
    $d = $raw['dishGetStatus'];
    $down = round(($d['downlinkThroughputBps'] ?? 0) / 1000000, 1);
    $up = round(($d['uplinkThroughputBps'] ?? 0) / 1000000, 1);
    $ping = round($d['popPingLatencyMs'] ?? 0);
    $snr = ($d['isSnrAboveNoiseFloor'] ?? false) ? "🟢 STABLE" : "🔴 WEAK";
    $sec = $d['deviceState']['uptimeS'] ?? 0;
    $uptime = floor($sec/3600)."j ".floor(($sec%3600)/60)."m";

    return "🛰 <b>STARLINK TELEMETRY</b>\n" .
           "━━━━━━━━━━━━━━━\n" .
           "📥 Download: <b>$down Mbps</b>\n" .
           "📤 Upload: <b>$up Mbps</b>\n" .
           "⏱ Latency: <b>$ping ms</b>\n" .
           "📡 Signal: <b>$snr</b>\n" .
           "⏰ Uptime: <code>$uptime</code>\n" .
           "━━━━━━━━━━━━━━━\n" .
           "🕒 Update: " . date('H:i:s');
}

// --- TELEGRAM CORE ---
function botRequest($method, $params, $token) {
    $url = "https://api.telegram.org/bot$token/$method";
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 45
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}

// --- UI DASHBOARD ---
function getMainMenu($chat_id) {
    $user = getSeller($chat_id);
    if (!$user) {
        return [
            "👋 <b>Selamat Datang!</b>\n\nID Anda: <code>$chat_id</code>\nStatus: <b>Belum Terdaftar</b>\n\nSilakan klik tombol di bawah untuk menautkan akun Web Anda ke Telegram.",
            [[['text' => '📝 Daftar / Link Akun', 'callback_data' => 'reg_user']]]
        ];
    }
    
    $role = strtoupper($user['profile'] ?? 'SELLER');
    $text = "─── <b>DASHBOARD " . $role . "</b> ───\n\n";
    $text .= "👤 User: <b>" . $user['sellername'] . "</b>\n";
    $text .= "💳 Saldo: <b>Rp " . number_format($user['sellerbalance'] ?? 0, 0, ',', '.') . "</b>\n";
    $text .= "────────────────────";
    
    $kb = [
        [['text' => '🎟 Beli Voucher', 'callback_data' => 'menu_vch'], ['text' => '💰 Isi Saldo', 'callback_data' => 'menu_tp']]
    ];

    if ($role == 'ADMIN') {
        $kb[] = [['text' => '🌐 PPPoE', 'callback_data' => 'pop_pppoe'], ['text' => '📍 Static', 'callback_data' => 'pop_static'], ['text' => '🚫 Isolir', 'callback_data' => 'pop_isolir']];
        $kb[] = [['text' => '📡 ACS Devices', 'callback_data' => 'acs_all'], ['text' => '🛰 STARLINK', 'callback_data' => 'pop_starlink']];
        $kb[] = [['text' => '🔥 HSP', 'callback_data' => 'pop_hsp'], ['text' => '📊 RES', 'callback_data' => 'pop_res']];
        $kb[] = [['text' => '🕌 Kontrol Masjid', 'callback_data' => 'menu_masjid']];
    }
    
    return [$text, $kb];
}

// --- INIT CONFIG ---
$mikDataRaw = loadData('mikrotikdata.json');
$mikConfig  = $mikDataRaw[0] ?? []; 
$teleConfig = $mikDataRaw[1] ?? []; 
$token      = $teleConfig['teletoken'] ?? '';

if (!$token) die("🔴 Error: Token tidak ditemukan!\n");

echo "🚀DoitWifi Bot Aktif (Polling Mode)...\n";

$offset = 0;
$lastMinute = "";
$API = new RouterosAPI();

while (true) {
    $updates = botRequest("getUpdates", ["offset" => $offset, "timeout" => 30], $token);
    
    if (isset($updates['result'])) {
        foreach ($updates['result'] as $upd) {
            $offset = $upd['update_id'] + 1;

            // --- HANDLING MESSAGE ---
            if (isset($upd['message'])) {
                $chat_id = (string)$upd['message']['chat']['id'];
                $text = $upd['message']['text'] ?? '';
                $user_tele = $upd['message']['from']['username'] ?? $upd['message']['from']['first_name'];
                $user = getSeller($chat_id);

                if ($text == '/start') {
                    list($textMsg, $kb) = getMainMenu($chat_id);
                    botRequest("sendMessage", [
                        'chat_id' => $chat_id, 
                        'text' => $textMsg, 
                        'parse_mode' => 'html', 
                        'reply_markup' => json_encode(['inline_keyboard' => $kb])
                    ], $token);
                } 
                elseif (!$user && !empty($text)) {
                    $sellers = loadData('sellerdata.json');
                    $found = false;

                    foreach ($sellers as $k => $s) {
                        if (strtolower($s['sellername']) == strtolower($text)) {
                            if (empty($s['chat_id'])) {
                                $sellers[$k]['chat_id'] = $chat_id;
                                $sellers[$k]['sellertele'] = "@" . ($upd['message']['from']['username'] ?? $user_tele);
                                saveData('sellerdata.json', $sellers);
                                
                                botRequest("sendMessage", [
                                    'chat_id' => $chat_id, 
                                    'text' => "✅ <b>Link Akun Berhasil!</b>\nSelamat datang <b>$text</b>.", 
                                    'parse_mode' => 'html'
                                ], $token);

                                list($textMsg, $kb) = getMainMenu($chat_id);
                                botRequest("sendMessage", [
                                    'chat_id' => $chat_id, 
                                    'text' => $textMsg, 
                                    'parse_mode' => 'html', 
                                    'reply_markup' => json_encode(['inline_keyboard' => $kb])
                                ], $token);

                                $found = true;
                                break;
                            } else {
                                botRequest("sendMessage", ['chat_id' => $chat_id, 'text' => "⚠️ Akun <b>$text</b> sudah tertaut dengan Telegram lain.", 'parse_mode' => 'html'], $token);
                                $found = true; break;
                            }
                        }
                    }
                    if (!$found) {
                        botRequest("sendMessage", ['chat_id' => $chat_id, 'text' => "❌ Akun <b>$text</b> tidak ditemukan di Web.", 'parse_mode' => 'html'], $token);
                    }
                }
            }

            // --- HANDLING CALLBACK QUERY ---
            if (isset($upd['callback_query'])) {
                $cb = $upd['callback_query'];
                $data = $cb['data'];
                $chat_id = (string)$cb['message']['chat']['id'];
                $msg_id = $cb['message']['message_id'];
                $cb_id = $cb['id'];
                $user = getSeller($chat_id);

                // 1. REGISTRASI
                if ($data == 'reg_user') {
                    botRequest("editMessageText", [
                        'chat_id' => $chat_id, 
                        'message_id' => $msg_id, 
                        'text' => "🔍 <b>KWHotspot</b>\n\nSilakan ketikkan <b>USERNAME</b> Aplikasi:", 
                        'parse_mode' => 'html'
                    ], $token);
                    botRequest("answerCallbackQuery", ['callback_query_id' => $cb_id], $token);
                }

                // 2. STARLINK CALLBACK (SISIPAN)
                if ($data == 'pop_starlink' && ($user['profile'] ?? '') == 'admin') {
                    $res = getStarlinkStatus();
                    $kb = [[['text' => '🔄 Refresh', 'callback_data' => 'pop_starlink'], ['text' => '🔙 Kembali', 'callback_data' => 'back_main']]];
                    botRequest("editMessageText", [
                        'chat_id' => $chat_id, 'message_id' => $msg_id, 'text' => $res, 
                        'parse_mode' => 'html', 'reply_markup' => json_encode(['inline_keyboard' => $kb])
                    ], $token);
                }

                // 3. LOGIKA TOPUP
                if ((strpos($data, 'acc_tp_') === 0 || strpos($data, 'acc_web_') === 0) && ($user['profile'] ?? '') == 'admin') {
                    $p = explode('_', $data);
                    $isWeb = ($p[1] == 'web');
                    $targetId = $p[2];
                    $amount = (int)$p[3];
                    $sellers = loadData('sellerdata.json');
                    $found = false;

                    foreach ($sellers as $k => $s) {
                        if (($isWeb && $s['sellername'] == $targetId) || (!$isWeb && $s['chat_id'] == $targetId)) {
                            $oldBal = $s['sellerbalance'];
                            $sellers[$k]['sellerbalance'] += $amount;
                            $targetChatId = $s['chat_id'];
                            $realName = $s['sellername'];
                            $found = true; break;
                        }
                    }
                    if ($found) {
                        saveData('sellerdata.json', $sellers);
                        $saldoAkhir = $oldBal + $amount;
                        $logs = loadData('topup_log.json');
                        array_unshift($logs, ["datetime" => date("Y-m-d H:i:s"), "sellername" => $realName, "topup" => $amount, "after" => $saldoAkhir, "msg" => "Topup Berhasil", "read" => false]);
                        saveData('topup_log.json', $logs);
                        if ($targetChatId) botRequest("sendMessage", ['chat_id' => $targetChatId, 'text' => "💰 <b>TOPUP BERHASIL!</b>\nSaldo ditambahkan Rp ".number_format($amount), 'parse_mode' => 'html'], $token);
                        botRequest("editMessageText", ['chat_id' => $chat_id, 'message_id' => $msg_id, 'text' => "✅ Topup $realName Berhasil!"], $token);
                    }
                }

                // 4. MONITORING MIKROTIK
                if (strpos($data, 'pop_') === 0 && ($user['profile'] ?? '') == 'admin' && $data !== 'pop_starlink') {
                    if ($API->connect($mikConfig['mtip'], $mikConfig['mtuser'], $mikConfig['mtpass'])) {
                        $res = "Nihil.";
                        if ($data == 'pop_pppoe') {
                            $all = $API->comm("/ppp/secret/print"); 
                            $act = array_column($API->comm("/ppp/active/print"), 'name');
                            $off = 0; $listOff = "";
                            foreach ($all as $s) { 
                                if (!in_array($s['name'], $act)) { 
                                    $off++; 
                                    if ($off <= 10) $listOff .= "• " . $s['name'] . "\n"; 
                                } 
                            }
                            $res = "🌐 <b>PPPoE Status</b>\n✅ ON: ".count($act)." | ❌ OFF: $off\n\nOFFLINE:\n$listOff";
                        } elseif ($data == 'pop_static') {
                            $nw = $API->comm("/tool/netwatch/print", ["?status" => "down"]);
                            $res = empty($nw) ? "✅ Semua Static UP" : "❌ <b>Down:</b>\n";
                            foreach ($nw as $n) { $res .= "• ".($n['comment'] ?? $n['host'])."\n"; }
                        } elseif ($data == 'pop_isolir') {
                            $sc = $API->comm("/ppp/secret/print"); $res = "🚫 <b>ISOLIR LIST:</b>\n"; $f = false;
                            foreach ($sc as $s) {
                                $c = $s['comment'] ?? '';
                                if (strpos($c, '!') !== false) {
                                    $st = (stripos($c, 'isolir') !== false) ? "❌ isolir" : "🔴 Bloked";
                                    $res .= "• ".$s['name']." ($st)\n"; $f = true;
                                }
                            }
                            $res = $f ? $res : "✅ Tidak ada user terisolir.";
                        } elseif ($data == 'pop_res') {
                            $r = $API->comm("/system/resource/print")[0];
                            $res = "📊 <b>SYSTEM RESOURCE</b>\nCPU: ".$r['cpu-load']."% | Up: ".$r['uptime']."\nModel: ".$r['board-name'];
                        } elseif ($data == 'pop_hsp') {
                            $act = count($API->comm("/ip/hotspot/active/print"));
                            $res = "🔥 <b>Hotspot Aktif:</b> $act User";
                        }
                        $API->disconnect();
                        botRequest("answerCallbackQuery", ['callback_query_id' => $cb_id, 'text' => strip_tags($res), 'show_alert' => true], $token);
                    }
                }

                // 4. ACS MONITORING
                if ($data == 'acs_all' && ($user['profile'] ?? '') == 'admin') {
                    $proj = "_productClass,VirtualParameters.get_active_ip,VirtualParameters.rx_power,InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID,_lastInform";
                    $ch = curl_init($teleConfig['acs_url'] . "?projection=" . urlencode($proj));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $resACS = json_decode(curl_exec($ch), true); curl_close($ch);
                    if ($resACS) {
                        $m = "📡 <b>DATA PERANGKAT ACS</b>\n\n"; $now = time();
                        foreach ($resACS as $d) {
                            $ssid = $d['InternetGatewayDevice']['LANDevice'][1]['WLANConfiguration'][1]['SSID']['_value'] ?? '-';
                            $lastIn = isset($d['_lastInform']) ? strtotime($d['_lastInform']) : 0;
                            $status = ($now - $lastIn < 300) ? "🟢 Online" : "🔴 Offline";
                            $m .= "<b>SN:</b> <code>".$d['_id']."</code>\n<b>IP:</b> ".($d['VirtualParameters']['get_active_ip']['_value'] ?? '-')."\n<b>SSID:</b> $ssid | <b>RX:</b> ".($d['VirtualParameters']['rx_power']['_value'] ?? '-')."\n<b>Status:</b> $status\n---\n";
                        }
                        $m = (strlen($m) > 4000) ? substr($m, 0, 3900) . "..." : $m;
                        botRequest("editMessageText", ['chat_id' => $chat_id, 'message_id' => $msg_id, 'text' => $m, 'parse_mode' => 'html', 'reply_markup' => json_encode(['inline_keyboard' => [[['text' => '🔙 Kembali', 'callback_data' => 'back_main']]]])], $token);
                    }
                }

                // 6. VOUCHER MENU
                if ($data == 'menu_vch') {
                    $rates = loadData('rate.json'); $kb = []; $row = [];
                    foreach ($rates as $r) {
                        $row[] = ['text' => "🎟 ".$r['name'], 'callback_data' => "vchbuat_" . $r['id']];
                        if (count($row) == 2) { $kb[] = $row; $row = []; }
                    }
                    if (!empty($row)) $kb[] = $row;
                    $kb[] = [['text' => '🔙 Kembali', 'callback_data' => 'back_main']];
                    botRequest("editMessageText", ['chat_id' => $chat_id, 'message_id' => $msg_id, 'text' => "📋 <b>PILIH PAKET:</b>", 'parse_mode' => 'html', 'reply_markup' => json_encode(['inline_keyboard' => $kb])], $token);
                }

                if (strpos($data, 'vchbuat_') === 0) {
                    $id = str_replace('vchbuat_', '', $data);
                    $rates = loadData('rate.json'); $sel = null;
                    foreach ($rates as $r) { if ($r['id'] == $id) { $sel = $r; break; } }
                    
                    if ($sel && ($user['sellerbalance'] >= $sel['amount'])) {
                        if ($API->connect($mikConfig['mtip'], $mikConfig['mtuser'], $mikConfig['mtpass'])) {
                            $vc = strtoupper(substr(sha1(mt_rand()), 17, (int)$sel['length']));
                            $API->comm('/ip/hotspot/user/add', [
                                "name" => $vc, "password" => $vc, "profile" => $sel['profile'],
                                "limit-bytes-total" => (string)($sel['limitbytes'] * 1024 * 1024 * 1024),
                                "comment" => "vc-bot|{$user['sellername']}|{$sel['amount']}|" . date('Y-m-d H:i')
                            ]);
                            $API->disconnect();
                            $allS = loadData('sellerdata.json');
                            $allS[$user['idx_db']]['sellerbalance'] -= $sel['amount'];
                            saveData('sellerdata.json', $allS);
                            $waText = urlencode("*KWHotspot*\nKode: `$vc`\nPaket: {$sel['name']}");
                                                        botRequest("sendMessage", [
                            'chat_id' => $chat_id, 
                            'text' => "───- <b>VOUCHER KWHotspot</b> ───\n\n"
							.         "=========================\n"  .
                                      "🎫 Kode : <code>$vc</code>\n" .
                                      "📦 Paket: <b>{$sel['name']}</b>\n" .
                                      "📊 Data : <b>{$sel['limitbytes']} GB</b>\n" .
                                      "💰 Harga: <b>Rp " . number_format($sel['amount'], 0, ',', '.') . "</b>\n\n" .
                                      "=========================\n" .
                                      "<i>Internet Untuk Kebaikan.</i>",
                            'parse_mode' => 'html', 
                            'reply_markup' => json_encode([
                            'inline_keyboard' => [
                            [['text' => '📤 Share WhatsApp', 'url' => "https://wa.me/?text=".urlencode($waText)]]]])], $token);
                        }
                    } else { botRequest("answerCallbackQuery", ['callback_query_id' => $cb_id, 'text' => "❌ Saldo Tidak Cukup!", 'show_alert' => true], $token); }
                }

                if ($data == 'menu_tp') {
                    $btn = [[['text' => '💰 50k', 'callback_data' => 'req_50000'], ['text' => '💰 100k', 'callback_data' => 'req_100000']], [['text' => '🔙 Kembali', 'callback_data' => 'back_main']]];
                    botRequest("editMessageText", ['chat_id' => $chat_id, 'message_id' => $msg_id, 'text' => "💳 <b>Pilih Nominal:</b>", 'parse_mode' => 'html', 'reply_markup' => json_encode(['inline_keyboard' => $btn])], $token);
                }

                if (strpos($data, 'req_') === 0) {
                    $nom = str_replace('req_', '', $data);
                    botRequest("sendMessage", ['chat_id' => $teleConfig['chatid'], 'text' => "💰 <b>REQ TOPUP</b>\nUser: @$chat_id\nNominal: Rp $nom", 'reply_markup' => json_encode(['inline_keyboard' => [[['text' => '✅ Terima', 'callback_data' => "acc_tp_{$chat_id}_{$nom}"]] ]])], $token);
                    botRequest("answerCallbackQuery", ['callback_query_id' => $cb_id, 'text' => "✅ Terkirim!", 'show_alert' => true], $token);
                }

                if ($data == 'back_main') {
                    list($text, $kb) = getMainMenu($chat_id);
                    botRequest("editMessageText", ['chat_id' => $chat_id, 'message_id' => $msg_id, 'text' => $text, 'parse_mode' => 'html', 'reply_markup' => json_encode(['inline_keyboard' => $kb])], $token);
                }
                
                // MASJID MENU
                if ($data == 'menu_masjid' && ($user['profile'] ?? '') == 'admin') {
                    $kbd = [[['text' => '🟢 SET LOW', 'callback_data' => 'MODE_L'], ['text' => '🔴 SET HIGH', 'callback_data' => 'MODE_H']], [['text' => '🟡 KEMBALI AUTO', 'callback_data' => 'MODE_A']], [['text' => '🔙 Kembali', 'callback_data' => 'back_main']]];
                    botRequest("editMessageText", ['chat_id' => $chat_id, 'message_id' => $msg_id, 'text' => "<b>--- KONTROL QUEUE MASJID ---</b>\nMode:", 'parse_mode' => 'html', 'reply_markup' => json_encode(['inline_keyboard' => $kbd])], $token);
                }

// 1. BLOK PILIH MODE (LOW/HIGH/AUTO)
if (strpos($data, "MODE_") === 0) {
    $mode = substr($data, 5, 1);
    if ($API->connect($mikConfig['mtip'], $mikConfig['mtuser'], $mikConfig['mtpass'])) {
        $all = $API->comm("/queue/simple/print", [".proplist" => ".id,name,comment"]);
        $buttons = [];
        foreach ($all as $q) {
            $comm = strtolower($q['comment'] ?? '');
            if (strpos($comm, 'masjid') !== false) {
                if (strpos($comm, 'low manual') !== false) { $statusLabel = "⚙️🟢"; }
                elseif (strpos($comm, 'high manual') !== false) { $statusLabel = "⚙️🔴"; }
                elseif (strpos($comm, 'high auto') !== false) { $statusLabel = "🔴"; }
                else { $statusLabel = "🟢"; }
                
                // Gunakan $mode dari substr di atas
                $buttons[] = [['text' => $statusLabel . " " . $q['name'], 'callback_data' => "EXE_" . $mode . "_" . $q['.id']]];
            }
        }
        $buttons[] = [['text' => '🔙 Batal', 'callback_data' => 'menu_masjid']];
        botRequest("editMessageText", ['chat_id' => $chat_id, 'message_id' => $msg_id, 'text' => "<b>Pilih Target Queue:</b>\nMode yang akan diterapkan: <b>$mode</b>", 'parse_mode' => 'html', 'reply_markup' => json_encode(['inline_keyboard' => $buttons])], $token);
        $API->disconnect();
    }
}

// 2. BLOK EKSEKUSI (EXE_)
if (isset($upd['callback_query']) && strpos($upd['callback_query']['data'], "EXE_") === 0) {
    $p = explode("_", $upd['callback_query']['data']);
    // $p[1] = Mode (L/H/A), $p[2] = ID Queue
    
    if ($API->connect($mikConfig['mtip'], $mikConfig['mtuser'], $mikConfig['mtpass'])) {
        if ($p[1] == "L") {
            $API->comm("/queue/simple/set", [".id" => $p[2], "max-limit" => "1M/1M", "comment" => "masjid low manual"]);
            $msg = "✅ LOW MANUAL Aktif.";
        } elseif ($p[1] == "H") {
            $API->comm("/queue/simple/set", [".id" => $p[2], "max-limit" => "5M/10M", "comment" => "masjid high manual"]);
            $msg = "✅ HIGH MANUAL Aktif.";
        } else {
            $API->comm("/queue/simple/set", [".id" => $p[2], "comment" => "masjid"]);
            $msg = "🔄 Kembali ke JADWAL AUTO.";
        }

        // Tampilkan Pop-up (Alert)
        botRequest("answerCallbackQuery", ['callback_query_id' => $cb_id, 'text' => $msg, 'show_alert' => false], $token);

        // --- REFRESH MENU OTOMATIS SETELAH EKSEKUSI ---
        $all = $API->comm("/queue/simple/print", [".proplist" => ".id,name,comment"]);
        $buttons = [];
        foreach ($all as $q) {
            $comm = strtolower($q['comment'] ?? '');
            if (strpos($comm, 'masjid') !== false) {
                if (strpos($comm, 'low manual') !== false) { $statusLabel = "⚙️🟢 [MAN]"; }
                elseif (strpos($comm, 'high manual') !== false) { $statusLabel = "⚙️🔴 [MAN]"; }
                elseif (strpos($comm, 'high auto') !== false) { $statusLabel = "🔴 [AUTO]"; }
                else { $statusLabel = "🟢 [AUTO]"; }
                $buttons[] = [['text' => $statusLabel . " " . $q['name'], 'callback_data' => "EXE_" . $p[1] . "_" . $q['.id']]];
            }
        }
        $buttons[] = [['text' => '🔙 Kembali ke Menu', 'callback_data' => 'menu_masjid']];
        
        botRequest("editMessageReplyMarkup", [
            'chat_id' => $chat_id, 
            'message_id' => $msg_id, 
            'reply_markup' => json_encode(['inline_keyboard' => $buttons])
        ], $token);

        $API->disconnect();
    }
}		
                botRequest("answerCallbackQuery", ['callback_query_id' => $cb_id], $token);
            }
        }
    }

    // --- JADWAL OTOMATIS MASJID ---
    $hari = strtolower(date('D')); $jam = date('H:i');
    if ($jam !== $lastMinute) {
        if ($API->connect($mikConfig['mtip'], $mikConfig['mtuser'], $mikConfig['mtpass'])){
            $allQueues = $API->comm("/queue/simple/print", [".proplist" => ".id,comment"]);
            foreach ($allQueues as $q) {
                $comment = strtolower($q['comment'] ?? '');
                if ($comment === "masjid" || strpos($comment, 'auto') !== false) {
                    $targetMode = "LOW"; 
                    if (in_array($hari, ["tue", "fri"])) { if ($jam >= "00:00" && $jam <= "22:00") $targetMode = "HIGH"; }
                    elseif (in_array($hari, ["mon", "wed", "thu"])) { if ($jam >= "00:00" && $jam <= "17:59") $targetMode = "HIGH"; }
                    elseif (in_array($hari, ["sat", "sun"])) { if ($jam >= "00:00" && $jam <= "07:59") $targetMode = "HIGH"; }
                    $limit = ($targetMode == "HIGH") ? "5M/10M" : "1M/1M";
                    $API->comm("/queue/simple/set", [".id" => $q['.id'], "max-limit" => $limit, "comment" => "masjid auto"]);
                } 
            }
            $API->disconnect(); $lastMinute = $jam;             
        }
    }
    gc_collect_cycles();
    usleep(400000); 
}