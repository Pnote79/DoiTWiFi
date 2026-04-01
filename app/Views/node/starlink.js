const http = require('http');
const { exec } = require('child_process');

// KONFIGURASI
const WEB_PORT = 8081; 
const DISH_IP = "192.168.100.1"; // IP Antena/Mikrotik kamu
const DISH_PORT = "9200";

const server = http.createServer((req, res) => {
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Content-Type', 'application/json');

    const command = `grpcurl -plaintext -d "{\\"get_status\\": {}}" ${DISH_IP}:${DISH_PORT} SpaceX.API.Device.Device/Handle`;

    exec(command, (error, stdout, stderr) => {
        if (error) {
            res.end(JSON.stringify({
                error: "Gagal Menghubungi Antena",
                target: `${DISH_IP}:${DISH_PORT}`,
                detail: stderr || error.message
            }, null, 4));
            return;
        }
        res.end(stdout); 
    });
});

// LISTEN di 0.0.0.0 agar bisa diakses via IP 172.17.0.2
server.listen(WEB_PORT, '0.0.0.0', () => {
    console.log("========================================");
    console.log(`JEMBATAN gRPC STARLINK AKTIF`);
    console.log(`Target Antena : ${DISH_IP}:${DISH_PORT}`);
    console.log(`Akses IP      : http://172.17.0.2:${WEB_PORT}`);
    console.log("========================================");
});