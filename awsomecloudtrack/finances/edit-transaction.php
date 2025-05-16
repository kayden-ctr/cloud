<?php
include '../includes/db_connect.php';
include '../includes/header.php';

// Check if ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: " . url("finances/index.php"));
    exit();
}

$id = $_GET['id'];

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
        // Update transaction
        $query = "UPDATE transactions SET 
                  description = ?, 
                  amount = ?, 
                  type = ?, 
                  category = ?, 
                  transaction_date = ?, 
                  reference_id = ?, 
                  reference_type = ? 
                  WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sdssssii", $description, $amount, $type, $category, $transaction_date, $reference_type, $reference_id, $id);
        
        if($stmt->execute()) {
            $success_message = "Transaction updated successfully";
        } else {
            $error_message = "Error updating transaction: " . $conn->error;
        }
    }
}

// Get transaction data
$query = "SELECT * FROM transactions WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0) {
    header("Location: " . url("finances/index.php"));
    exit();
}

$transaction = $result->fetch_assoc();
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1><i class="bi bi-pencil-square me-2"></i>Edit Transaction</h1>
        <p class="text-muted">Modify transaction #<?php echo $id; ?></p>
    </div>
    <div class="col-md-6 text-end">
        <a href="<?php echo url('finances/index.php'); ?>" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left"></i> Back to Finances
        </a>
    </div>
</div>

<?php if(isset($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

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
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $id; ?>" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="description" name="description" value="<?php echo $transaction['description']; ?>" required>
                            <div class="invalid-feedback">Please enter a description.</div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="amount" class="form-label">Amount (₱) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0.01" value="<?php echo $transaction['amount']; ?>" required>
                                <div class="invalid-feedback">Please enter a valid amount.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="type" class="form-label">Transaction Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="income" <?php echo $transaction['type'] == 'income' ? 'selected' : ''; ?>>Income</option>
                                <option value="expense" <?php echo $transaction['type'] == 'expense' ? 'selected' : ''; ?>>Expense</option>
                            </select>
                            <div class="invalid-feedback">Please select a transaction type.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="category" name="category" list="categoryList" value="<?php echo $transaction['category']; ?>" required>
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
                            <input type="datetime-local" class="form-control" id="transaction_date" name="transaction_date" value="<?php echo date('Y-m-d\TH:i', strtotime($transaction['transaction_date'])); ?>" required>
                            <div class="invalid-feedback">Please select a date and time.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="reference_id" class="form-label">Reference ID (Optional)</label>
                            <input type="text" class="form-control" id="reference_id" name="reference_id" value="<?php echo $transaction['reference_id']; ?>">
                            <small class="text-muted">ID of related sale, procurement, etc.</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reference_type" class="form-label">Reference Type (Optional)</label>
                        <select class="form-select" id="reference_type" name="reference_type">
                            <option value="" <?php echo empty($transaction['reference_type']) ? 'selected' : ''; ?>>None</option>
                            <option value="sale" <?php echo $transaction['reference_type'] == 'sale' ? 'selected' : ''; ?>>Sale</option>
                            <option value="procurement" <?php echo $transaction['reference_type'] == 'procurement' ? 'selected' : ''; ?>>Procurement</option>
                            <option value="membership" <?php echo $transaction['reference_type'] == 'membership' ? 'selected' : ''; ?>>Membership</option>
                            <option value="event" <?php echo $transaction['reference_type'] == 'event' ? 'selected' : ''; ?>>Event</option>
                        </select>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <a href="<?php echo url('finances/index.php'); ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Update Transaction
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Transaction Details</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Transaction ID:</span>
                        <span class="fw-semibold">#<?php echo $transaction['id']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Created:</span>
                        <span><?php echo date('M d, Y h:i A', strtotime($transaction['created_at'])); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Type:</span>
                        <span>
                            <?php if($transaction['type'] == 'income'): ?>
                                <span class="badge bg-success">Income</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Expense</span>
                            <?php endif; ?>
                        </span>
                    </li>
                </ul>
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
    
    // Fetch all forms we want to apply validation styles to
    var forms = document.querySelectorAll('.needs-validation');
    
    // Loop over them and prevent submission
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
