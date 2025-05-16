<?php
include '../includes/db_connect.php';

// Get categories for dropdown
$categories_query = "SELECT DISTINCT category FROM transactions ORDER BY category";
$categories_result = $conn->query($categories_query);

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $description = $_POST['description'];
    $amount = $_POST['amount'];
    $type = $_POST['type'];
    $category = $_POST['category'];
    $transaction_date = $_POST['transaction_date'];
    $reference_id = !empty($_POST['reference_id']) ? $_POST['reference_id'] : null;
    $reference_type = !empty($_POST['reference_type']) ? $_POST['reference_type'] : null;

    // Basic validation
    if (empty($description) || empty($amount) || !is_numeric($amount)) {
        $error_message = "Please fill all required fields with valid data";
    } else {
        // Insert new transaction
        $query = "INSERT INTO transactions (description, amount, type, category, transaction_date, reference_id, reference_type) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sdsssis", $description, $amount, $type, $category, $transaction_date, $reference_id, $reference_type);

        if ($stmt->execute()) {
            // Redirect before output
           header("Location: ../finances/index.php?success=1");
            exit();
        } else {
            $error_message = "Error adding transaction: " . $conn->error;
        }
    }
}

// Get current date and time for default transaction date
$current_datetime = date('Y-m-d\TH:i');

// Include header *after* header() calls
include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1><i class="bi bi-cash-coin me-2"></i>Add Transaction</h1>
        <p class="text-muted">Record a new financial transaction</p>
    </div>
    <div class="col-md-6 text-end">
        <a href="<?php echo url('finances/index.php'); ?>" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left"></i> Back to Finances
        </a>
    </div>
</div>

<?php if(isset($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Transaction Information</h5>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="description" name="description" required>
                            <div class="invalid-feedback">Please enter a description.</div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="amount" class="form-label">Amount (₱) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0.01" required>
                                <div class="invalid-feedback">Please enter a valid amount.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="type" class="form-label">Transaction Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="income">Income</option>
                                <option value="expense">Expense</option>
                            </select>
                            <div class="invalid-feedback">Please select a transaction type.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="category" name="category" list="categoryList" required>
                            <datalist id="categoryList">
                                <?php if ($categories_result && $categories_result->num_rows > 0): ?>
                                    <?php while($category = $categories_result->fetch_assoc()): ?>
                                        <option value="<?php echo $category['category']; ?>">
                                    <?php endwhile; ?>
                                <?php endif; ?>
                                <option value="Sales">
                                <option value="Membership">
                                <option value="Donation">
                                <option value="Sponsorship">
                                <option value="Procurement">
                                <option value="Events">
                                <option value="Operations">
                                <option value="Utilities">
                                <option value="Other">
                            </datalist>
                            <div class="invalid-feedback">Please enter a category.</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="transaction_date" class="form-label">Transaction Date <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="transaction_date" name="transaction_date" value="<?php echo $current_datetime; ?>" required>
                            <div class="invalid-feedback">Please select a date and time.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="reference_id" class="form-label">Reference ID (Optional)</label>
                            <input type="text" class="form-control" id="reference_id" name="reference_id">
                            <small class="text-muted">ID of related sale, procurement, etc.</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reference_type" class="form-label">Reference Type (Optional)</label>
                        <select class="form-select" id="reference_type" name="reference_type">
                            <option value="">None</option>
                            <option value="sale">Sale</option>
                            <option value="procurement">Procurement</option>
                            <option value="membership">Membership</option>
                            <option value="event">Event</option>
                        </select>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <button type="reset" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i> Add Transaction
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Transaction Types</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge bg-success me-2">Income</span>
                        <span>Money coming into the organization</span>
                    </div>
                    <ul class="list-unstyled ms-4 small text-muted">
                        <li>Sales of merchandise</li>
                        <li>Membership fees</li>
                        <li>Donations</li>
                        <li>Sponsorships</li>
                    </ul>
                </div>
                
                <div>
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge bg-danger me-2">Expense</span>
                        <span>Money going out of the organization</span>
                    </div>
                    <ul class="list-unstyled ms-4 small text-muted">
                        <li>Procurement of inventory</li>
                        <li>Event expenses</li>
                        <li>Operational costs</li>
                        <li>Utilities and services</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Tips</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item px-0">
                        <i class="bi bi-info-circle text-primary me-2"></i>
                        Use clear descriptions for easier tracking
                    </li>
                    <li class="list-group-item px-0">
                        <i class="bi bi-info-circle text-primary me-2"></i>
                        Categorize transactions consistently
                    </li>
                    <li class="list-group-item px-0">
                        <i class="bi bi-info-circle text-primary me-2"></i>
                        Link related transactions using reference IDs
                    </li>
                    <li class="list-group-item px-0">
                        <i class="bi bi-info-circle text-primary me-2"></i>
                        Record transactions promptly for accurate reporting
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Form validation
(function() {
    'use strict';

    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

<?php include '../includes/footer.php'; ?>
