<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Recovery - <?php echo get_settings('system_name'); ?></title>
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/backend/css/bootstrap.min.css">
    <style>
        body {
            background: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .recovery-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
        }
        .card-body {
            padding: 20px;
        }
        .stat-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .stat-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-top: 5px;
        }
        .stat-value.amount {
            color: #28a745;
        }
        .stat-value.missing {
            color: #dc3545;
        }
        .btn-recover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-recover:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .btn-recover:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        .btn-refresh {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
        }
        #output {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
            max-height: 600px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
            display: none;
        }
        #output.active {
            display: block;
        }
        .progress-bar-container {
            background: #e9ecef;
            border-radius: 4px;
            height: 30px;
            margin: 20px 0;
            overflow: hidden;
            display: none;
        }
        .progress-bar-container.active {
            display: block;
        }
        .progress-bar {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            height: 100%;
            width: 0%;
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert-warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            color: #856404;
        }
        .alert-success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            color: #155724;
        }
        .alert-info {
            background: #d1ecf1;
            border-left: 4px solid #17a2b8;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <div class="recovery-container">
        <div class="card">
            <div class="card-header">
                <h2 style="margin: 0;">
                    <i class="fa fa-database"></i> Daily Fee Transaction Recovery
                </h2>
                <p style="margin: 10px 0 0 0; opacity: 0.9;">
                    Recover deleted transactions from May 4, 2026
                </p>
            </div>
            <div class="card-body">
                <?php if (isset($stats)): ?>
                    <?php if ($stats['missing_count'] > 0): ?>
                        <div class="alert alert-warning">
                            <strong>⚠️ Recovery Needed</strong><br>
                            <?php echo $stats['missing_count']; ?> transactions are missing and need to be recovered.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success">
                            <strong>✓ All Recovered</strong><br>
                            No missing transactions found. All records are intact.
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="stat-box">
                                <div class="stat-label">Total Journal Entries</div>
                                <div class="stat-value"><?php echo $stats['total_entries']; ?></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-box">
                                <div class="stat-label">Existing Transactions</div>
                                <div class="stat-value"><?php echo $stats['existing_count']; ?></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-box">
                                <div class="stat-label">Missing Transactions</div>
                                <div class="stat-value missing"><?php echo $stats['missing_count']; ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="row" style="margin-top: 15px;">
                        <div class="col-md-6">
                            <div class="stat-box">
                                <div class="stat-label">Total Amount (Journal)</div>
                                <div class="stat-value amount">₵<?php echo number_format($stats['total_amount'], 2); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="stat-box">
                                <div class="stat-label">Missing Amount</div>
                                <div class="stat-value missing">₵<?php echo number_format($stats['missing_amount'], 2); ?></div>
                            </div>
                        </div>
                    </div>

                    <?php if ($stats['missing_count'] > 0): ?>
                        <div style="margin-top: 30px; text-align: center;">
                            <button id="startRecovery" class="btn-recover">
                                <i class="fa fa-play"></i> Start Recovery Process
                            </button>
                            <button id="refreshStats" class="btn-refresh" style="display: none;">
                                <i class="fa fa-refresh"></i> Refresh Statistics
                            </button>
                        </div>

                        <div class="progress-bar-container" id="progressContainer">
                            <div class="progress-bar" id="progressBar">0%</div>
                        </div>

                        <div id="output"></div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        Loading statistics...
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        console.log('Transaction Recovery page loaded');
        
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Document ready');
            
            const startBtn = document.getElementById('startRecovery');
            const refreshBtn = document.getElementById('refreshStats');
            const output = document.getElementById('output');
            const progressContainer = document.getElementById('progressContainer');
            const progressBar = document.getElementById('progressBar');
            
            console.log('Button found:', startBtn);
            
            if (startBtn) {
                startBtn.addEventListener('click', function() {
                    console.log('Button clicked!');
                    
                    // Disable button
                    startBtn.disabled = true;
                    startBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';
                    
                    // Show output and progress
                    output.classList.add('active');
                    output.textContent = 'Initializing recovery process...\n\n';
                    progressContainer.classList.add('active');
                    
                    console.log('Starting AJAX request...');
                    
                    // Start recovery via AJAX with streaming
                    const xhr = new XMLHttpRequest();
                    let lastLength = 0;
                    
                    xhr.open('GET', '<?php echo base_url(); ?>transaction_recovery/process', true);
                    
                    xhr.onprogress = function() {
                        const newData = xhr.responseText.substring(lastLength);
                        lastLength = xhr.responseText.length;
                        
                        if (newData) {
                            output.textContent += newData;
                            output.scrollTop = output.scrollHeight;
                            
                            // Update progress bar (estimate based on output length)
                            const progress = Math.min(95, (lastLength / 10000) * 100);
                            progressBar.style.width = progress + '%';
                            progressBar.textContent = Math.round(progress) + '%';
                        }
                    };
                    
                    xhr.onload = function() {
                        console.log('AJAX completed with status:', xhr.status);
                        
                        if (xhr.status === 200) {
                            progressBar.style.width = '100%';
                            progressBar.textContent = '100%';
                            output.textContent += '\n\n✅ Recovery process completed!\n';
                            output.textContent += '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n';
                            output.textContent += 'Click "Refresh Statistics" to see updated numbers.\n';
                            output.scrollTop = output.scrollHeight;
                            
                            // Show refresh button
                            refreshBtn.style.display = 'inline-block';
                            
                            // Keep progress bar visible
                            setTimeout(function() {
                                progressContainer.classList.remove('active');
                            }, 2000);
                            
                            // Re-enable button but change text
                            startBtn.disabled = false;
                            startBtn.innerHTML = '<i class="fa fa-repeat"></i> Run Recovery Again';
                        } else {
                            output.textContent += '\n\n❌ Error: ' + xhr.statusText + '\n';
                            startBtn.disabled = false;
                            startBtn.innerHTML = '<i class="fa fa-play"></i> Start Recovery Process';
                        }
                    };
                    
                    xhr.onerror = function() {
                        console.log('AJAX error occurred');
                        output.textContent += '\n\n❌ Network error occurred\n';
                        startBtn.disabled = false;
                        startBtn.innerHTML = '<i class="fa fa-play"></i> Start Recovery Process';
                    };
                    
                    xhr.send();
                    console.log('AJAX request sent');
                });
            }
            
            if (refreshBtn) {
                refreshBtn.addEventListener('click', function() {
                    console.log('Refresh button clicked');
                    window.location.href = '<?php echo base_url(); ?>transaction_recovery';
                });
            }
        });
    </script>
</body>
</html>
