<?php
session_start();
require_once '../includes/auth.php';
requireLogin();

$db = getDB();

// Hitung total alumni yang belum dilacak (tidak fetch semua untuk hemat memori)
$stmt = $db->query("SELECT COUNT(*) FROM alumni WHERE status_pelacakan = 'belum'");
$totalUntracked = $stmt->fetchColumn();

$pageTitle = 'Pelacakan Massal Turbo';
$activePage = 'lacak';
require_once '../includes/header.php';
?>

<div class="card">
    <div class="card-title">🚀 Pelacakan Massal Turbo (Concurrent Mode)</div>
    <div class="card-subtitle">Sistem akan menjalankan beberapa mesin OSINT secara paralel untuk mempercepat proses pada dataset besar (142k+).</div>

    <div id="setupArea">
        <div class="alert alert-info mb-20 mt-20">
            <strong>Target Tersisa: <span id="totalDisplay"><?= number_format($totalUntracked, 0, ',', '.') ?></span> Alumni</strong><br>
            Sistem sekarang menggunakan mode paralel. Anda bisa mengatur jumlah "Worker" (proses simultan).
        </div>

        <div style="background:var(--bg-dark); padding:20px; border-radius:10px; border:1px solid var(--border); margin-bottom:20px;">
            <label style="display:block; margin-bottom:10px; font-weight:bold; color:var(--primary-light);">Jumlah Worker Paralel:</label>
            <div style="display:flex; align-items:center; gap:15px;">
                <input type="range" id="workerRange" min="1" max="20" value="5" style="flex:1;" oninput="document.getElementById('workerVal').innerText = this.value">
                <span id="workerVal" style="font-size:20px; font-weight:bold; min-width:30px;">5</span>
            </div>
            <p style="font-size:12px; color:var(--text-muted); margin-top:10px;">
                * Semakin banyak worker, semakin cepat selesai, namun risiko blokir dari mesin pencari meningkat. Rekomendasi: 5-10.
            </p>
        </div>

        <?php if($totalUntracked > 0): ?>
            <button onclick="startTurboTracking()" class="btn btn-primary w-100" style="padding:15px; font-size:16px;">🔥 Jalankan Turbo Track Sekarang</button>
        <?php else: ?>
            <div class="alert alert-success">Semua alumni sudah dilacak! Tidak ada antrean baru.</div>
            <a href="index.php" class="btn btn-secondary w-100">Kembali ke Database</a>
        <?php endif; ?>
    </div>

    <div id="progressContainer" style="display:none; margin-top:30px;">
        <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
            <span id="statusText" style="font-weight:bold; color:var(--primary-light);">Menginisialisasi Worker...</span>
            <span id="progressPercent">0%</span>
        </div>
        <div style="background:var(--bg-dark); height:12px; border-radius:6px; overflow:hidden; border:1px solid var(--border);">
            <div id="progressBar" style="width:0%; height:100%; background:linear-gradient(90deg, var(--primary), var(--primary-light)); transition: width 0.3s;"></div>
        </div>
        
        <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:15px; margin-top:20px;">
            <div style="background:var(--bg-surface); padding:10px; border-radius:8px; text-align:center; border:1px solid var(--border);">
                <div style="font-size:12px; color:var(--text-muted);">Selesai</div>
                <div id="statSuccess" style="font-size:18px; font-weight:bold; color:var(--success);">0</div>
            </div>
            <div style="background:var(--bg-surface); padding:10px; border-radius:8px; text-align:center; border:1px solid var(--border);">
                <div style="font-size:12px; color:var(--text-muted);">Gagal</div>
                <div id="statFail" style="font-size:18px; font-weight:bold; color:var(--danger);">0</div>
            </div>
            <div style="background:var(--bg-surface); padding:10px; border-radius:8px; text-align:center; border:1px solid var(--border);">
                <div style="font-size:12px; color:var(--text-muted);">Antrean</div>
                <div id="statRemaining" style="font-size:18px; font-weight:bold; color:var(--primary-light);"><?= $totalUntracked ?></div>
            </div>
        </div>

        <div class="mt-24">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                <span style="font-size:13px; font-weight:bold; color:var(--text-muted);">Live System Log:</span>
                <button onclick="clearLog()" style="background:none; border:none; color:var(--primary-light); font-size:11px; cursor:pointer;">Clear Log</button>
            </div>
            <div id="logArea" style="background:#000; color:#0f0; font-family:monospace; padding:15px; border-radius:6px; height:300px; overflow-y:auto; font-size:12px; border:1px solid var(--border); line-height:1.5;">
                [SYSTEM] Ready for parallel execution...<br>
            </div>
        </div>
    </div>
</div>

<script>
let totalTarget = <?= (int)$totalUntracked ?>;
let processedCount = 0;
let successCount = 0;
let failCount = 0;
let maxWorkers = 5;
let activeWorkers = 0;
let queue = [];
let isRunning = false;
let stopRequested = false;

function startTurboTracking() {
    maxWorkers = parseInt(document.getElementById('workerRange').value);
    document.getElementById('setupArea').style.display = 'none';
    document.getElementById('progressContainer').style.display = 'block';
    
    isRunning = true;
    addLog(`[SYSTEM] Starting with ${maxWorkers} parallel workers...`, 'var(--primary-light)');
    
    // Mulai loop pengambilan queue
    fetchBatch();
}

function fetchBatch() {
    if (processedCount >= totalTarget || stopRequested) return;

    fetch(`bulk_get_queue.php?limit=50&offset=${processedCount}`)
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success' && res.data.length > 0) {
                queue.push(...res.data);
                startWorkers();
            } else if (processedCount < totalTarget) {
                // Mungkin ada data baru atau delay
                setTimeout(fetchBatch, 2000);
            }
        });
}

function startWorkers() {
    while (activeWorkers < maxWorkers && queue.length > 0) {
        const target = queue.shift();
        runWorker(target);
    }
}

function runWorker(target) {
    activeWorkers++;
    addLog(`[WORKER] Tracking: ${target.nama_lulusan} (${target.nim || 'No NIM'})`, 'var(--text-muted)');
    
    const formData = new FormData();
    formData.append('id', target.id);

    fetch('bulk_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        processedCount++;
        if (data.status === 'success') {
            successCount++;
            addLog(`[DONE] ${target.nama_lulusan}: Found via ${data.source}`, 'var(--success)');
        } else {
            failCount++;
            addLog(`[FAIL] ${target.nama_lulusan}: ${data.message}`, 'var(--danger)');
        }
        updateUI();
        activeWorkers--;
        
        // Refill queue if low
        if (queue.length < 10) fetchBatch();
        
        // Next task
        if (processedCount < totalTarget) {
            startWorkers();
        } else if (activeWorkers === 0) {
            finishBulk();
        }
    })
    .catch(err => {
        processedCount++;
        failCount++;
        addLog(`[ERROR] ${target.nama_lulusan}: Network Error`, 'var(--danger)');
        updateUI();
        activeWorkers--;
        startWorkers();
    });
}

function updateUI() {
    const percent = Math.round((processedCount / totalTarget) * 100);
    document.getElementById('progressBar').style.width = percent + '%';
    document.getElementById('progressPercent').innerText = percent + '%';
    
    document.getElementById('statSuccess').innerText = successCount;
    document.getElementById('statFail').innerText = failCount;
    document.getElementById('statRemaining').innerText = totalTarget - processedCount;
    
    document.getElementById('statusText').innerText = `Memproses: ${processedCount}/${totalTarget} (${activeWorkers} active workers)`;
}

function addLog(msg, color) {
    const logArea = document.getElementById('logArea');
    const time = new Date().toLocaleTimeString();
    const entry = document.createElement('div');
    entry.style.color = color || 'inherit';
    entry.innerHTML = `<span style="color:#666">[${time}]</span> ${msg}`;
    logArea.appendChild(entry);
    logArea.scrollTop = logArea.scrollHeight;
    
    // Keep log area clean if too many entries
    if (logArea.childNodes.length > 200) {
        logArea.removeChild(logArea.firstChild);
    }
}

function clearLog() {
    document.getElementById('logArea').innerHTML = '';
}

function finishBulk() {
    isRunning = false;
    document.getElementById('progressBar').style.width = '100%';
    document.getElementById('progressPercent').innerText = '100%';
    document.getElementById('statusText').innerText = 'Pelacakan Selesai!';
    document.getElementById('statusText').style.color = 'var(--success)';
    
    addLog(`[SYSTEM] COMPLETED. Total: ${totalTarget}, Success: ${successCount}, Failed: ${failCount}`, 'var(--success)');
    alert(`Proses Selesai!\nBerhasil: ${successCount}\nGagal: ${failCount}`);
}
</script>

<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/footer.php'; ?>
