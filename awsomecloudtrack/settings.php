<?php
include 'includes/db_connect.php';
include 'includes/header.php';

// Check if user is admin
$is_admin = ($_SESSION['role'] === 'admin');

// Process form submission
$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && $is_admin) {
    $success_message = "Settings updated successfully";
}
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1><i class="bi bi-gear me-2"></i>System Settings</h1>
        <p class="text-muted">Configure system preferences and options</p>
    </div>
</div>

<?php if($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if($is_admin): ?>
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">General Settings</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="mb-3">
                            <label for="organization_name" class="form-label">Organization Name</label>
                            <input type="text" class="form-control" id="organization_name" name="organization_name" value="AWSome Cloudtrack">
                        </div>
                        <div class="mb-3">
                            <label for="currency" class="form-label">Currency</label>
                            <select class="form-select" id="currency" name="currency">
                                <option value="PHP" selected>Philippine Peso (₱)</option>
                                <option value="USD">US Dollar ($)</option>
                                <option value="EUR">Euro (€)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="date_format" class="form-label">Date Format</label>
                            <select class="form-select" id="date_format" name="date_format">
                                <option value="m/d/Y" selected>MM/DD/YYYY</option>
                                <option value="d/m/Y">DD/MM/YYYY</option>
                                <option value="Y-m-d">YYYY-MM-DD</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="timezone" class="form-label">Timezone</label>
                            <select class="form-select" id="timezone" name="timezone">
                                <option value="Asia/Manila" selected>Asia/Manila</option>
                                <option value="UTC">UTC</option>
                                <option value="America/New_York">America/New_York</option>
                            </select>
                        </div>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" name="update_settings" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Inventory Settings</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="mb-3">
                            <label for="default_reorder_level" class="form-label">Default Reorder Level</label>
                            <input type="number" class="form-control" id="default_reorder_level" name="default_reorder_level" value="5" min="1">
                        </div>
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="enable_low_stock_alerts" name="enable_low_stock_alerts" checked>
                                <label class="form-check-label" for="enable_low_stock_alerts">Enable Low Stock Alerts</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="auto_update_inventory" name="auto_update_inventory" checked>
                                <label class="form-check-label" for="auto_update_inventory">Automatically Update Inventory on Sales</label>
                            </div>
                        </div>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" name="update_inventory_settings" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">System Maintenance</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Database Backup</h5>
                                    <p class="card-text">Create a backup of your database to prevent data loss.</p>
                                    <button type="button" class="btn btn-primary">
                                        <i class="bi bi-download me-1"></i> Backup Database
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Clear Cache</h5>
                                    <p class="card-text">Clear system cache to resolve performance issues.</p>
                                    <button type="button" class="btn btn-secondary">
                                        <i class="bi bi-trash me-1"></i> Clear Cache
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">System Information</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>PHP Version</span>
                            <span class="badge bg-primary"><?php echo phpversion(); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>MySQL Version</span>
                            <span class="badge bg-primary"><?php echo $conn->server_info; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>Server</span>
                            <span class="badge bg-primary"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>System Time</span>
                            <span><?php echo date('Y-m-d H:i:s'); ?></span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Help & Support</h5>
                </div>
                <div class="card-body">
                    <p>Need help with the system? Contact our support team or check the documentation.</p>
                    <div class="d-grid gap-2">
                        <a href="#" class="btn btn-outline-primary">
                            <i class="bi bi-book me-1"></i> Documentation
                        </a>
                        <a href="#" class="btn btn-outline-success">
                            <i class="bi bi-headset me-1"></i> Contact Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> You do not have permission to access system settings. Please contact an administrator.
    </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<?php include 'includes/footer.php'; ?>
