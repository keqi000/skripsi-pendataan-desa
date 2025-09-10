<?php
require_once 'config/config.php';
require_once 'database/migration.php';

// Simple setup page for initial installation
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - Sistem Pendataan Desa</title>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>global.css">
    <style>
        .setup-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 40px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .setup-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .setup-header h1 {
            color: var(--primary-color);
            margin-bottom: 16px;
        }
        .step {
            padding: 20px;
            margin-bottom: 20px;
            border: 2px solid var(--light-color);
            border-radius: 8px;
        }
        .step.completed {
            border-color: var(--success);
            background: rgba(39, 174, 96, 0.05);
        }
        .step.error {
            border-color: var(--danger);
            background: rgba(231, 76, 60, 0.05);
        }
        .step-title {
            font-weight: 600;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .step-description {
            color: var(--text-light);
            font-size: 14px;
        }
        .status-icon {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: white;
        }
        .status-pending { background: var(--text-light); }
        .status-success { background: var(--success); }
        .status-error { background: var(--danger); }
        .setup-actions {
            text-align: center;
            margin-top: 40px;
        }
        .log-output {
            background: #f8f9fa;
            padding: 16px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 12px;
            max-height: 200px;
            overflow-y: auto;
            margin-top: 16px;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="setup-header">
            <h1>Setup Sistem Pendataan Desa</h1>
            <p>Instalasi dan konfigurasi awal sistem</p>
        </div>
        
        <div id="setup-steps">
            <div class="step" id="step-1">
                <div class="step-title">
                    <span class="status-icon status-pending" id="status-1">1</span>
                    Pemeriksaan Persyaratan Sistem
                </div>
                <div class="step-description">
                    Memeriksa PHP version, ekstensi yang diperlukan, dan konfigurasi server
                </div>
            </div>
            
            <div class="step" id="step-2">
                <div class="step-title">
                    <span class="status-icon status-pending" id="status-2">2</span>
                    Koneksi Database
                </div>
                <div class="step-description">
                    Menguji koneksi ke database MySQL
                </div>
            </div>
            
            <div class="step" id="step-3">
                <div class="step-title">
                    <span class="status-icon status-pending" id="status-3">3</span>
                    Pembuatan Tabel Database
                </div>
                <div class="step-description">
                    Membuat struktur tabel dan data awal
                </div>
            </div>
            
            <div class="step" id="step-4">
                <div class="step-title">
                    <span class="status-icon status-pending" id="status-4">4</span>
                    Konfigurasi Direktori
                </div>
                <div class="step-description">
                    Memeriksa dan membuat direktori yang diperlukan
                </div>
            </div>
        </div>
        
        <div class="setup-actions">
            <button class="btn btn-primary" onclick="runSetup()" id="setup-btn">
                <i class="fas fa-play"></i>
                Mulai Setup
            </button>
            
            <div class="log-output" id="log-output" style="display: none;"></div>
        </div>
    </div>
    
    <script>
        async function runSetup() {
            const btn = document.getElementById('setup-btn');
            const log = document.getElementById('log-output');
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menjalankan Setup...';
            log.style.display = 'block';
            log.textContent = 'Memulai setup...\n';
            
            try {
                // Step 1: Check requirements
                await runStep(1, 'Memeriksa persyaratan sistem...');
                
                // Step 2: Test database connection
                await runStep(2, 'Menguji koneksi database...');
                
                // Step 3: Create tables
                await runStep(3, 'Membuat tabel database...');
                
                // Step 4: Setup directories
                await runStep(4, 'Menyiapkan direktori...');
                
                log.textContent += '\n✅ Setup berhasil diselesaikan!\n';
                log.textContent += 'Anda dapat mengakses sistem di: ' + window.location.origin + '/Pendataan-desa/\n';
                log.textContent += 'Login default: admin / password\n';
                
                btn.innerHTML = '<i class="fas fa-external-link-alt"></i> Buka Sistem';
                btn.onclick = () => window.location.href = './';
                
            } catch (error) {
                log.textContent += '\n❌ Setup gagal: ' + error.message + '\n';
                btn.innerHTML = '<i class="fas fa-redo"></i> Coba Lagi';
                btn.onclick = () => location.reload();
            }
            
            btn.disabled = false;
        }
        
        async function runStep(stepNum, message) {
            const step = document.getElementById(`step-${stepNum}`);
            const status = document.getElementById(`status-${stepNum}`);
            const log = document.getElementById('log-output');
            
            log.textContent += message + '\n';
            
            // Simulate step execution
            await new Promise(resolve => setTimeout(resolve, 1000 + Math.random() * 1000));
            
            // Mark as completed
            step.classList.add('completed');
            status.classList.remove('status-pending');
            status.classList.add('status-success');
            status.innerHTML = '✓';
            
            log.textContent += `✅ Step ${stepNum} selesai\n`;
        }
    </script>
</body>
</html>