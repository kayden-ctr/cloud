<?php
include '../includes/db_connect.php';

// Process form submission BEFORE any output
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    $quantity = $_POST['quantity'];
    $unit_price = $_POST['unit_price'];
    $reorder_level = $_POST['reorder_level'];

    if (empty($name) || !is_numeric($quantity) || !is_numeric($unit_price)) {
        $error_message = "Please fill all required fields with valid data";
    } else {
        $query = "INSERT INTO inventory_items (name, category, description, quantity, unit_price, reorder_level) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssddi", $name, $category, $description, $quantity, $unit_price, $reorder_level);

        if ($stmt->execute()) {
            header("Location: ../inventory/index.php?success=1");
            exit();
        } else {
            $error_message = "Error adding item: " . $conn->error;
        }
    }
}

include '../includes/header.php';

// Get categories for dropdown
$categories_query = "SELECT DISTINCT category FROM inventory_items ORDER BY category";
$categories_result = $conn->query($categories_query);
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1><i class="bi bi-plus-circle me-2"></i>Add Inventory Item</h1>
        <p class="text-muted">Create a new item in your inventory</p>
    </div>
    <div class="col-md-6 text-end">
        <a href="../inventory/index.php" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left"></i> Back to Inventory
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
                <h5 class="mb-0">Item Information</h5>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Item Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="invalid-feedback">Please enter an item name.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="category" name="category" list="categoryList" required>
                                <datalist id="categoryList">
                                    <?php if ($categories_result && $categories_result->num_rows > 0): ?>
                                        <?php while($category = $categories_result->fetch_assoc()): ?>
                                            <option value="<?php echo htmlspecialchars($category['category']); ?>">
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </datalist>
                                <div class="invalid-feedback">Please select or enter a category.</div>
                            </div>
                            <small class="text-muted">Type a new category or select an existing one</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        <small class="text-muted">Provide a detailed description of the item</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="quantity" name="quantity" min="0" step="1" required>
                            <div class="invalid-feedback">Please enter a valid quantity.</div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="unit_price" class="form-label">Unit Price (₱) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" id="unit_price" name="unit_price" min="0" step="0.01" required>
                                <div class="invalid-feedback">Please enter a valid price.</div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="reorder_level" class="form-label">Reorder Level <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="reorder_level" name="reorder_level" min="0" step="1" required>
                            <small class="text-muted">Minimum quantity before reordering</small>
                            <div class="invalid-feedback">Please enter a reorder level.</div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <button type="reset" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i> Add Item
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Tips</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item px-0">
                        <i class="bi bi-info-circle text-primary me-2"></i>
                        Use descriptive names for easy identification
                    </li>
                    <li class="list-group-item px-0">
                        <i class="bi bi-info-circle text-primary me-2"></i>
                        Set appropriate reorder levels to avoid stockouts
                    </li>
                    <li class="list-group-item px-0">
                        <i class="bi bi-info-circle text-primary me-2"></i>
                        Consistent categorization helps with reporting
                    </li>
                    <li class="list-group-item px-0">
                        <i class="bi bi-info-circle text-primary me-2"></i>
                        Include detailed descriptions for better inventory management
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
