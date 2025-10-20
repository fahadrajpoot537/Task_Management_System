<?php
require __DIR__ . '/vendor/autoload.php';

use Jmrashed\Zkteco\Lib\ZKTeco;

$ip = getenv('ZKTECO_IP') ?: '192.168.1.252';
$port = getenv('ZKTECO_PORT') ?: 4370;

echo "Connecting to ZKTeco device at {$ip}:{$port}...\n";

$zk = new ZKTeco($ip, $port);

if ($zk->connect()) {
    echo "✅ Connected to ZKTeco device\n";

    // Device info
    echo "Version: " . json_encode($zk->version()) . "\n";
    echo "Platform: " . json_encode($zk->platform()) . "\n";
    echo "OS: " . json_encode($zk->osVersion()) . "\n";
    echo "Serial: " . json_encode($zk->serialNumber()) . "\n";

    // Get users
    $zk->disableDevice();
    $users = $zk->getUser();
    $zk->enableDevice();
    $userCount = is_array($users) ? count($users) : 0;
    echo "Users detected: {$userCount}\n";

    // Fetch attendance with retries
    $attendance = [];
    for ($i=1;$i<=3;$i++) {
        $zk->disableDevice();
        $attendance = $zk->getAttendance();
        $zk->enableDevice();
        if (!empty($attendance)) { break; }
        usleep(200000);
    }

    if (!empty($attendance)) {
        echo "\nAttendance Records:\n";
        printf("%12s\t%s\t%d\t%d\t%d\t%d\n", "UID", "Timestamp", "State", "Verify", "Workcode", "Reserved");
        foreach ($attendance as $row) {
            $uid = $row['uid'] ?? '';
            $time = $row['timestamp'] ?? '';
            $state = $row['state'] ?? 0;
            $verify = $row['verify'] ?? 0;
            $workcode = $row['workcode'] ?? 0;
            $reserved = $row['reserved'] ?? 0;
            printf("%12s\t%s\t%d\t%d\t%d\t%d\n", $uid, $time, $state, $verify, $workcode, $reserved);
        }
    } else {
        echo "No attendance found on the device.\n";
    }

    $zk->disconnect();
} else {
    echo "❌ Connection failed. Check device IP/port, firewall, comm key, and model support.\n";
}