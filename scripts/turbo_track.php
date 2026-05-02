<?php
/**
 * CLI Turbo Track V2 - Multi-Process OSINT Processor
 * Usage: php scripts/turbo_track.php [num_workers]
 */

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

require_once __DIR__ . '/../config/database.php';

$numWorkers = isset($argv[1]) ? (int)$argv[1] : 5;
$scriptPath = __DIR__ . "/osint_engine.py";
$db = getDB();

echo "========================================\n";
echo "ALUMNI OSINT TURBO CLI V2 (PARALLEL)\n";
echo "========================================\n";
echo "Mode: Multi-Process ($numWorkers Workers)\n";

// Count remaining
$stmt = $db->query("SELECT COUNT(*) FROM alumni WHERE status_pelacakan = 'belum'");
$total = $stmt->fetchColumn();

if ($total == 0) {
    echo "✅ Semua alumni sudah dilacak. Selesai!\n";
    exit;
}

echo "Target Tersisa: " . number_format($total, 0, ',', '.') . "\n";
echo "Estimasi Selesai: " . round(($total * 5) / ($numWorkers * 3600), 1) . " jam\n";
echo "----------------------------------------\n\n";

$workers = [];
$processed = 0;

function spawnWorker($id, $nama, $nim, $fakultas, $tahun, $scriptPath) {
    $command = "python " . escapeshellarg($scriptPath) . " " . 
               escapeshellarg($nama) . " " . 
               escapeshellarg($nim ?? '') . " " . 
               escapeshellarg($fakultas ?? '') . " " . 
               escapeshellarg($tahun ?? '');
    
    $spec = [
        0 => ["pipe", "r"], // stdin
        1 => ["pipe", "w"], // stdout
        2 => ["pipe", "w"]  // stderr
    ];
    
    $process = proc_open($command, $spec, $pipes);
    return ['process' => $process, 'pipes' => $pipes, 'id' => $id, 'nama' => $nama];
}

while ($processed < $total) {
    // Fill workers
    while (count($workers) < $numWorkers) {
        $stmt = $db->query("SELECT * FROM alumni WHERE status_pelacakan = 'belum' ORDER BY id ASC LIMIT 1");
        $alumni = $stmt->fetch();
        
        if (!$alumni) break 2;
        
        // Mark as 'proses' immediately so other workers don't pick it up (though we are single threaded manager)
        $db->prepare("UPDATE alumni SET status_pelacakan = 'proses' WHERE id = ?")->execute([$alumni['id']]);
        
        $workers[] = spawnWorker(
            $alumni['id'], 
            $alumni['nama_lulusan'], 
            $alumni['nim'], 
            $alumni['fakultas'], 
            $alumni['tahun_lulus'], 
            $scriptPath
        );
        echo " [+] [" . date('H:i:s') . "] Starting: " . $alumni['nama_lulusan'] . "\n";
    }

    // Check workers status
    foreach ($workers as $key => $w) {
        $status = proc_get_status($w['process']);
        
        if (!$status['running']) {
            $output = stream_get_contents($w['pipes'][1]);
            fclose($w['pipes'][0]);
            fclose($w['pipes'][1]);
            fclose($w['pipes'][2]);
            proc_close($w['process']);
            
            $result = json_decode($output, true);
            $processed++;

            if ($result && $result['status'] === 'success' && !empty($result['candidates'])) {
                $best = $result['candidates'][0];
                $updateStmt = getDB()->prepare("UPDATE alumni SET 
                    email = ?, no_hp = ?,
                    linkedin_url = ?, instagram_url = ?, facebook_url = ?, tiktok_url = ?,
                    tempat_bekerja = ?, alamat_bekerja = ?, posisi = ?, status_pekerjaan = ?,
                    website_kerja = ?,
                    pddikti_kampus = ?, pddikti_prodi = ?, pddikti_status = ?, pddikti_nim = ?, pddikti_url = ?,
                    status_pelacakan = 'selesai', tanggal_pelacakan = NOW(), dilacak_oleh = 1
                    WHERE id = ?");

                $updateStmt->execute([
                    $best['email'], $best['no_hp'],
                    $best['linkedin_url'], $best['instagram_url'], $best['facebook_url'], $best['tiktok_url'],
                    $best['tempat_bekerja'], $best['alamat_bekerja'], $best['posisi'], 
                    ($best['status_pekerjaan'] !== '' ? $best['status_pekerjaan'] : null),
                    $best['website_kerja'],
                    $best['pddikti_kampus'], $best['pddikti_prodi'], $best['pddikti_status'], $best['pddikti_nim'], $best['pddikti_url'],
                    $w['id']
                ]);
                echo " [v] [" . date('H:i:s') . "] Done: " . $w['nama'] . " (" . $best['source'] . ")\n";
            } else {
                getDB()->prepare("UPDATE alumni SET status_pelacakan = 'gagal' WHERE id = ?")->execute([$w['id']]);
                echo " [x] [" . date('H:i:s') . "] Failed: " . $w['nama'] . "\n";
            }
            
            unset($workers[$key]);
        }
    }
    
    usleep(100000); // 0.1s delay to prevent CPU spiking
}

echo "\n PROSES SELESAI!\n";
echo "Total data diproses pada sesi ini: $processed\n";
