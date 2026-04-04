
MODERN ISP PANEL

Modules:
- Dashboard
- ACS (TR069 / GenieACS)
- MikroTik PPPoE
- Hotspot Voucher
- Seller System

URL:
/dashboard
/acs/devices
/mikrotik/pppoe
/voucher
/seller
<h2><b>Beri ijin dulu</b></h2>
<code>sudo chown -R www-data:www-data DoiTWiFi/storage </code>
<code>sudo chmod -R 755 DoiTWiFi/storage </code>

longpoling telegram
php /var/www/html/DOiTWiFi/app/Views/admin/bot/botpoll.php

Backen Starlink
node /var/www/html/DOiTWiFi/app/Views/node/starlink.js

/bin/sh /usr/local/bin/sync-github.sh
