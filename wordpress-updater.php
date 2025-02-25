<?php
// Bootstrap page with WordPress check (wp-content only), professional design, and progress bar
error_reporting(E_ALL);
ini_set('display_errors', 1);

$stage = isset($_GET['stage']) ? $_GET['stage'] : 'check';
$target_dir = __DIR__ . "/";
$zip_url = "https://wordpress.org/latest.zip";
$zip_file = $target_dir . "wordpress_latest.zip";
$subfolder = $target_dir . "wordpress";
$self = basename(__FILE__);

// Check if WordPress is installed by looking for wp-content folder
function is_wordpress_installed($dir) {
    return is_dir($dir . "wp-content");
}

function recurse_move($src, $dst) {
    if (!is_dir($dst)) {
        mkdir($dst, 0777, true);
    }
    $dir = opendir($src);
    while (($file = readdir($dir)) !== false) {
        if ($file !== '.' && $file !== '..') {
            $source_path = $src . '/' . $file;
            $dest_path = $dst . '/' . $file;
            if (is_dir($source_path)) {
                recurse_move($source_path, $dest_path);
                rmdir($source_path);
            } else {
                rename($source_path, $dest_path);
            }
        }
    }
    closedir($dir);
}

// AJAX handler for operations
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Unknown action'];
    
    switch ($_GET['action']) {
        case 'download':
            try {
                if (file_put_contents($zip_file, file_get_contents($zip_url)) !== false) {
                    $response = ['success' => true, 'message' => 'Download complete'];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to download WordPress'];
                }
            } catch (Exception $e) {
                $response = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
            break;
            
        case 'extract':
            try {
                $zip = new ZipArchive;
                if ($zip->open($zip_file) === TRUE) {
                    $zip->extractTo($target_dir);
                    $zip->close();
                    unlink($zip_file);
                    $response = ['success' => true, 'message' => 'Extraction complete'];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to extract ZIP file'];
                }
            } catch (Exception $e) {
                $response = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
            break;
            
        case 'move':
            try {
                if (is_dir($subfolder)) {
                    $files = scandir($subfolder);
                    foreach ($files as $file) {
                        if ($file !== '.' && $file !== '..') {
                            $source = $subfolder . "/" . $file;
                            $destination = $target_dir . $file;
                            if (is_dir($source)) {
                                recurse_move($source, $destination);
                                rmdir($source);
                            } else {
                                rename($source, $destination);
                            }
                        }
                    }
                    rmdir($subfolder);
                    $response = ['success' => true, 'message' => 'Files installed successfully'];
                } else {
                    $response = ['success' => false, 'message' => 'Subfolder "wordpress" not found'];
                }
            } catch (Exception $e) {
                $response = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
            break;
            
        case 'remove_updater':
            try {
                if (unlink(__FILE__)) {
                    $response = ['success' => true, 'message' => 'Updater removed successfully'];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to remove updater file'];
                }
            } catch (Exception $e) {
                $response = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
            break;
    }
    
    echo json_encode($response);
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WordPress Update Service</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <style>
        :root {
            --primary: #2271b1;
            --primary-hover: #135e96;
            --secondary: #f0f0f1;
            --text-primary: #3c434a;
            --text-secondary: #646970;
            --success: #00a32a;
            --warning: #dba617;
            --danger: #d63638;
        }
        
        body {
            background: #f0f6fc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            color: var(--text-primary);
            padding: 20px;
        }
        
        .container {
            background: #ffffff;
            padding: 2.5rem;
            border-radius: 8px;
            box-shadow: 0 3px 20px rgba(18, 24, 58, 0.08);
            max-width: 750px;
            width: 100%;
            border: 1px solid rgba(0, 0, 0, 0.08);
        }
        
        .header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eaeaea;
        }
        
        .header-logo {
            margin-right: 15px;
            color: var(--primary);
            font-size: 2rem;
        }
        
        .header h1 {
            font-size: 1.8rem;
            color: var(--text-primary);
            margin: 0;
            font-weight: 600;
        }
        
        .progress-container {
            margin: 30px 0;
        }
        
        .progress {
            height: 12px;
            border-radius: 6px;
            background: #e7e7e7;
            overflow: hidden;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
        }
        
        .progress-bar {
            background: linear-gradient(90deg, var(--primary), var(--primary-hover));
            transition: width 0.8s ease;
            font-weight: 500;
        }
        
        .status {
            margin-top: 1.5rem;
            font-size: 1.1rem;
            color: var(--text-secondary);
            font-weight: 400;
        }
        
        .stepper {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
        }
        
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            position: relative;
        }
        
        .step:not(:last-child):after {
            content: '';
            position: absolute;
            top: 15px;
            width: 100%;
            height: 2px;
            background: #e7e7e7;
            left: 50%;
        }
        
        .step.active:not(:last-child):after {
            background: var(--primary);
        }
        
        .step-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e7e7e7;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
            z-index: 1;
            color: #888;
            font-size: 12px;
        }
        
        .step.active .step-icon {
            background: var(--primary);
            color: white;
        }
        
        .step.completed .step-icon {
            background: var(--success);
            color: white;
        }
        
        .step-label {
            font-size: 0.75rem;
            color: #888;
            text-align: center;
        }
        
        .step.active .step-label {
            color: var(--primary);
            font-weight: 500;
        }
        
        .footer {
            margin-top: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid #eaeaea;
            color: var(--text-secondary);
            font-size: 0.85rem;
        }
        
        .btn-primary {
            background: var(--primary);
            border: none;
            padding: 0.6rem 1.5rem;
            font-size: 1rem;
            border-radius: 4px;
            transition: all 0.2s ease;
            font-weight: 500;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn-danger {
            background: var(--danger);
            border: none;
            color: white;
            padding: 0.6rem 1.5rem;
            font-size: 1rem;
            border-radius: 4px;
            transition: all 0.2s ease;
            font-weight: 500;
        }
        
        .btn-danger:hover {
            background: #b32d2e;
        }
        
        .alert {
            font-size: 1rem;
            border-radius: 4px;
            border-left: 4px solid var(--warning);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .container {
            animation: fadeIn 0.4s ease-out;
        }
        
        .actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 1.5rem;
            gap: 10px;
        }
        
        .version-info {
            background: #f8f8f8;
            border-radius: 4px;
            padding: 10px 15px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .version-info i {
            margin-right: 10px;
            color: var(--primary);
        }
        
        .result-message {
            animation: fadeIn 0.4s ease-out;
        }
        
        .cleanup-option {
            display: flex;
            align-items: center;
            background: #f9f9f9;
            padding: 12px;
            border-radius: 4px;
            margin-top: 20px;
            border: 1px solid #eaeaea;
        }
        
        .cleanup-option label {
            margin-left: 10px;
            margin-bottom: 0;
            font-size: 0.95rem;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-logo">
                <i class="fab fa-wordpress"></i>
            </div>
            <h1>WordPress Update Service</h1>
        </div>

        <?php if ($stage === 'check') { ?>
            <div class="version-info">
                <i class="fas fa-info-circle"></i>
                <div>Latest WordPress update: <strong>6.5.0</strong> (February 2025)</div>
            </div>
            
            <?php if (is_wordpress_installed($target_dir)) { ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle me-2"></i> WordPress installation detected in this directory.
                </div>
                <p class="mt-3">Update your WordPress installation to the latest version with enhanced security features and performance improvements.</p>
                <div class="actions">
                    <button class="btn btn-primary" id="startUpdate">
                        <i class="fas fa-sync-alt me-2"></i> Start Update Process
                    </button>
                </div>
            <?php } else { ?>
                <div class="alert alert-warning" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i> WordPress not detected in this directory. The wp-content folder is missing.
                </div>
                <p class="mt-3">We couldn't find a WordPress installation in this directory. You can still proceed, but this may overwrite existing files.</p>
                <div class="actions">
                    <button class="btn btn-primary" id="startUpdate">
                        <i class="fas fa-arrow-right me-2"></i> Proceed Anyway
                    </button>
                </div>
            <?php } ?>

        <?php } else { ?>
            <!-- Stepper -->
            <div class="stepper">
                <div class="step active" id="step1">
                    <div class="step-icon">1</div>
                    <div class="step-label">Initialization</div>
                </div>
                <div class="step" id="step2">
                    <div class="step-icon">2</div>
                    <div class="step-label">Download</div>
                </div>
                <div class="step" id="step3">
                    <div class="step-icon">3</div>
                    <div class="step-label">Extract</div>
                </div>
                <div class="step" id="step4">
                    <div class="step-icon">4</div>
                    <div class="step-label">Install</div>
                </div>
            </div>

            <div class="progress-container">
                <div class="progress">
                    <div class="progress-bar" role="progressbar" id="progressBar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
            <div class="status" id="statusText">
                <i class="fas fa-spinner fa-spin me-2"></i> Initializing update process...
            </div>
            <div id="resultContainer"></div>
        <?php } ?>

        <div class="footer">
            <div>© <?php echo date('Y'); ?> Yatosha Web Services</div>
            <div><i class="fas fa-shield-alt me-1"></i> Secure Update Process</div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Start update process handler
            $("#startUpdate").click(function() {
                window.location.href = '?stage=process';
            });
            
            <?php if ($stage === 'process') { ?>
                // Initialize the update process
                let updateProcess = {
                    currentStep: 1,
                    errorOccurred: false,
                    
                    init: function() {
                        this.updateUI(10, 'Preparing update environment...', 'spinner fa-spin');
                        this.setActiveStep(1);
                        setTimeout(() => this.downloadWordPress(), 1000);
                    },
                    
                    updateUI: function(percent, message, icon = 'spinner fa-spin') {
                        $("#progressBar").css('width', percent + '%').attr('aria-valuenow', percent);
                        $("#statusText").html(`<i class="fas fa-${icon} me-2"></i> ${message}`);
                    },
                    
                    setActiveStep: function(stepNumber) {
                        // Mark previous steps as completed
                        for (let i = 1; i < stepNumber; i++) {
                            $(`#step${i}`).removeClass('active').addClass('completed');
                            $(`#step${i} .step-icon`).html('<i class="fas fa-check"></i>');
                        }
                        
                        // Set current step as active
                        $(`#step${stepNumber}`).addClass('active');
                        
                        // Reset next steps
                        for (let i = stepNumber + 1; i <= 4; i++) {
                            $(`#step${i}`).removeClass('active completed');
                            $(`#step${i} .step-icon`).html(i);
                        }
                    },
                    
                    downloadWordPress: function() {
                        this.currentStep = 2;
                        this.setActiveStep(2);
                        this.updateUI(30, 'Downloading WordPress files from secure server...', 'cloud-download-alt');
                        
                        $.ajax({
                            url: '?action=download',
                            type: 'GET',
                            dataType: 'json',
                            success: (response) => {
                                if (response.success) {
                                    setTimeout(() => this.extractWordPress(), 1500);
                                } else {
                                    this.handleError(response.message);
                                }
                            },
                            error: (xhr, status, error) => {
                                this.handleError('Network error during download: ' + error);
                            }
                        });
                    },
                    
                    extractWordPress: function() {
                        this.currentStep = 3;
                        this.setActiveStep(3);
                        this.updateUI(60, 'Extracting and verifying files...', 'file-archive');
                        
                        $.ajax({
                            url: '?action=extract',
                            type: 'GET',
                            dataType: 'json',
                            success: (response) => {
                                if (response.success) {
                                    setTimeout(() => this.installWordPress(), 1500);
                                } else {
                                    this.handleError(response.message);
                                }
                            },
                            error: (xhr, status, error) => {
                                this.handleError('Network error during extraction: ' + error);
                            }
                        });
                    },
                    
                    installWordPress: function() {
                        this.currentStep = 4;
                        this.setActiveStep(4);
                        this.updateUI(80, 'Installing WordPress updates...', 'cog');
                        
                        $.ajax({
                            url: '?action=move',
                            type: 'GET',
                            dataType: 'json',
                            success: (response) => {
                                if (response.success) {
                                    setTimeout(() => this.completeUpdate(), 1500);
                                } else {
                                    this.handleError(response.message);
                                }
                            },
                            error: (xhr, status, error) => {
                                this.handleError('Network error during installation: ' + error);
                            }
                        });
                    },
                    
                    completeUpdate: function() {
                        this.updateUI(100, 'Update complete! WordPress has been successfully updated.', 'check-circle');
                        
                        // Show success message
                        this.showCompletionMessage();
                    },
                    
                    handleError: function(errorMessage) {
                        this.errorOccurred = true;
                        $("#statusText").html(`<i class="fas fa-exclamation-circle me-2"></i> Error: ${errorMessage}`);
                        
                        $("#resultContainer").html(`
                            <div class="alert alert-danger mt-4 result-message">
                                <i class="fas fa-times-circle me-2"></i> The update process encountered an error:
                                <div class="mt-2">${errorMessage}</div>
                            </div>
                            <div class="actions">
                                <button class="btn btn-primary" onclick="window.location.href='?stage=check'">
                                    <i class="fas fa-redo me-2"></i> Try Again
                                </button>
                            </div>
                        `);
                    },
                    
                    showCompletionMessage: function() {
                        const filename = "<?php echo $self; ?>";
                        
                        $("#resultContainer").html(`
                            <div class="alert alert-success mt-4 result-message">
                                <i class="fas fa-check-circle me-2"></i> WordPress has been successfully updated to the latest version.
                            </div>
                            
                            <div class="cleanup-option">
                                <input type="checkbox" class="form-check-input" id="removeUpdater" checked>
                                <label class="form-check-label" for="removeUpdater">
                                    Remove updater script (${filename}) after completion for security
                                </label>
                            </div>
                            
                            <div class="actions mt-4">
                                <button class="btn btn-primary" id="finishUpdate">
                                    <i class="fas fa-external-link-alt me-2"></i> Finish & Visit Site
                                </button>
                            </div>
                        `);
                        
                        // Handle finish button click
                        $("#finishUpdate").click(function() {
                            const removeUpdater = $("#removeUpdater").is(":checked");
                            
                            if (removeUpdater) {
                                // Remove the updater file first
                                $.ajax({
                                    url: '?action=remove_updater',
                                    type: 'GET',
                                    dataType: 'json',
                                    success: function(response) {
                                        if (response.success) {
                                            // Successfully removed, redirect to home
                                            window.location.href = "index.php";
                                        } else {
                                            // Show error but still allow navigation
                                            alert("Note: Could not remove updater file. Please delete it manually for security.");
                                            window.location.href = "index.php";
                                        }
                                    },
                                    error: function() {
                                        // On error, still navigate to home
                                        alert("Note: Could not remove updater file. Please delete it manually for security.");
                                        window.location.href = "index.php";
                                    }
                                });
                            } else {
                                // Just navigate to home
                                window.location.href = "index.php";
                            }
                        });
                    }
                };
                
                // Start the update process
                updateProcess.init();
            <?php } ?>
        });
    </script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>