<nav class="navbar sticky-top navbar-expand-lg navbar-dark bg-dark w-100" style="box-shadow:0 1px 5px rgba(0,0,0,0.5)">
  <a class="navbar-brand" href="">
    <div id="brandNameDashboard">
      <span class="d-block">
       <svg xmlns="http://www.w3.org/2000/svg" width="38" height="38" fill="currentColor" class="bi bi-incognito" viewBox="0 0 640 512">
        <path d="M634.9 154.9C457.7-9 182.2-8.9 5.1 154.9c-6.7 6.2-6.8 16.6-.4 23l34.2 34c6.1 6.1 16 6.2 22.4 .4 145.9-133.7 371.3-133.7 517.3 0 6.4 5.9 16.3 5.7 22.4-.4l34.2-34c6.4-6.4 6.3-16.8-.4-23zM320 352c-35.4 0-64 28.7-64 64s28.7 64 64 64 64-28.7 64-64-28.7-64-64-64zm202.7-83.6c-115.3-101.9-290.2-101.8-405.3 0-6.9 6.1-7.1 16.7-.6 23.2l34.4 34c6 5.9 15.7 6.3 22.1 .8 84-72.6 209.7-72.4 293.5 0 6.4 5.5 16.1 5.1 22.1-.8l34.4-34c6.6-6.5 6.3-17.1-.6-23.2z"/>
       </svg>
        <span id="JuanFi"><?php echo $dn ?></span><span id="Man"><?php echo $ns ?></span>
      </span>
      <span class="d-block" id="timeDiv" style="font-size:12px;font-family:arial;font-style:normal"></span>
    </div>
  </a>

  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarContent">
    <span class="navbar-toggler-icon"></span>
  </button>

  <div class="collapse navbar-collapse" id="navbarContent">
    <!-- Kiri -->
    <ul class="navbar-nav mr-auto">

      <!-- Voucher -->
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="voucherMenu" role="button" data-toggle="dropdown">🎟️ Voucher</a>
        <div class="dropdown-menu">
          <a class="dropdown-item" href="../page/dashboard.php">➕ Generate</a>
          <a class="dropdown-item" href="../admin/voucher.php">🧩 Vouchers <span class="badge badge-warning"><?php echo $userCount ?></span></a>
          <a class="dropdown-item" href="../page/monitoring.php">💰 Income</a>
        </div>
      </li>
    <?php if ($_SESSION['role'] === 'admin') { ?>
      <!-- Hotspot -->
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="hotspotMenu" role="button" data-toggle="dropdown">📡 Hotspot</a>
        <div class="dropdown-menu">
          <a class="dropdown-item" href="../admin/activeuser.php">👥 Active Users<span class="badge badge-warning"><?php echo $userActive ?></span></a>
		  <a class="dropdown-item" href="../admin/voucher.php">🧩 Vouchers <span class="badge badge-warning"><?php echo $userCount ?></span></a>
          
        </div>
      </li>

      <!-- Client -->
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="clientMenu" role="button" data-toggle="dropdown">👤 Client</a>
        <div class="dropdown-menu">
          <a class="dropdown-item" href="../admin/ppoe.php">🧪 PPPoE<span class="badge badge-warning"><?php echo $ppoeActive ?></span></a>
          <a class="dropdown-item" href="../admin/static.php">📍 Static/AP Hotspot<span class="badge badge-warning"><?php echo $jumlah_up ?></span></a>
        </div>
      </li>

      <!-- Seller -->
      
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="sellerMenu" role="button" data-toggle="dropdown">🧑‍💼 Seller</a>
        <div class="dropdown-menu">
          <a class="dropdown-item" href="../admin/seller.php">📋 Daftar Seller</a>
		  <button type="button" class="dropdown-item"data-toggle="modal" data-target="#addSellModal">
           ➕👤 Add Seller</button>
		  <button type="button" class="dropdown-item" data-toggle="modal" data-target="#TopUpModal">
           💸 Topup Balance</button>
          <a class="dropdown-item" href="../admin/topup_history.php">📜 Topup History</a>
          <a class="dropdown-item" href="../admin/voucherlog.php">📘 Generate History</a>
        </div>
      </li>
    

      <!-- Settings -->
      
      <li class="nav-item">
        <a class="nav-link" href="admin.php">⚙️ Settings</a>
      </li>
      <?php } ?>

      <!-- Notifikasi Seller -->
      <?php
      if ($_SESSION['role'] === 'seller') {
        $notifPath = "../json/topup_log.json";
        if (!file_exists($notifPath)) file_put_contents($notifPath, json_encode([]));
        $notifications = json_decode(file_get_contents($notifPath), true) ?: [];
        $unread = 0;
        $notifList = '';
        foreach (array_reverse($notifications) as $notif) {
          if ($notif['sellername'] == $_SESSION['sell'] && !$notif['read']) {
            $unread++;
            $notifList .= "<a class='dropdown-item small' href='#'>{$notif['msg']}<br><small class='text-muted'>{$notif['datetime']}</small></a>";
          }
        }
      ?>
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle text-warning" href="#" id="notifDropdown" data-toggle="dropdown">
          🔔 <span class="badge badge-danger"><?php echo $unread ?></span>
        </a>
        <div class="dropdown-menu" style="max-height:300px;overflow:auto;">
          <?php echo $notifList ?: "<span class='dropdown-item text-muted'>Tidak ada notifikasi</span>"; ?>
        </div>
      </li>
      <?php } ?>
    </ul>

    <!-- Kanan -->
    <div class="ml-auto d-flex align-items-center text-light">
 
      <span class="mr-3" id="welcomeSeller"></span>
      <button onclick="logOut()" class="btn btn-sm btn-warning">Logout</button>
    </div>
  </div>
</nav>

<script>
  const username = "<?php echo $_SESSION['username'] ?? ''; ?>";
  const role = "<?php echo $_SESSION['role'] ?? ''; ?>";
  if (username && role) {
    const label = role === 'admin' ? 'Welcome Admin' : 'Welcome Seller';
    document.getElementById("welcomeSeller").textContent = `${label} : ${username}`;
  }

  function logOut() {
    if (confirm("Are you sure you want to logout?")) {
      window.location.href = "../page/action.php?logout=1";
    }
  }

  // Time realtime
  setInterval(() => {
    const now = new Date();
    document.getElementById('timeDiv').textContent = now.toLocaleString();
  }, 1000);

  // Tandai notifikasi sebagai terbaca
  document.addEventListener('DOMContentLoaded', () => {
    const dropdown = document.getElementById('notifDropdown');
    if (dropdown) {
      dropdown.addEventListener('click', () => {
        fetch('../post/mark_read.php')
          .then(res => res.text())
          .then(() => {
            const badge = document.querySelector('#notifDropdown .badge-danger');
            if (badge) badge.textContent = '0';
          });
      });
    }
  });
</script>
