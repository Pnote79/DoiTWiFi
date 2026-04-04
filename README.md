
MODERN ISP PANEL

Modules:
- Dashboard
- ACS (TR069 / GenieACS)
- MikroTik PPPoE
- Hotspot Voucher
- Seller System

<h2><b>Beri ijin dulu</b></h2>
<code>sudo chown -R www-data:www-data DoiTWiFi/storage </code>
<code>sudo chmod -R 755 DoiTWiFi/storage </code>

<h2>longpoling telegram</h2> 
php /var/www/html/DOiTWiFi/app/Views/admin/bot/botpoll.php

<h2>Backen Starlink</h2>
node /var/www/html/DOiTWiFi/app/Views/node/starlink.js

/bin/sh /usr/local/bin/sync-github.sh
