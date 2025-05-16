<?php
include '../includes/db_connect.php';
include '../includes/header.php';

// Check if ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate input
    $name = $_POST['name'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    $quantity = $_POST['quantity'];
    $unit_price = $_POST['unit_price'];
    $reorder_level = $_POST['reorder_level'];
    
    // Basic validation
    if (empty($name) || !is_numeric($quantity) || !is_numeric($unit_price)) {
        $error_message = "Please fill all required fields with valid data";
    } else {
        // Update item
        $query = "UPDATE inventory_items 
                  SET name = ?, category = ?, description = ?, quantity = ?, unit_price = ?, reorder_level = ? 
                  WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssddii", $name, $category, $description, $quantity, $unit_price, $reorder_level, $id);
        
        if($stmt->execute()) {
            $success_message = "Item updated successfully";
        } else {
            $error_message = "Error updating item: " . $conn->error;
        }
    }
}

// Get item data
$query = "SELECT * FROM inventory_items WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0) {
    header("Location: index.php");
    exit();
}

$item = $result->fetch_assoc();

// Get categories for dropdown
$categories_query = "SELECT DISTINCT category FROM inventory_items ORDER BY category";
$categories_result = $conn->query($categories_query);
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1>Edit Inventory Item</h1>
    </div>
    <div class="col-md-6 text-end">
        <a href="index.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Inventory
        </a>
    </div>
</div>

<?php if(isset($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $success_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if(isset($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $id; ?>">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">Item Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo $item['name']; ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="category" name="category" list="categoryList" value="<?php echo $item['category']; ?>" required>
                        <datalist id="categoryList">
                            <?php if ($categories_result && $categories_result->num_rows > 0): ?>
                                <?php while($category = $categories_result->fetch_assoc()): ?>
                                    <option value="<?php echo $category['category']; ?>">
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </datalist>
                    </div>
                    <small class="text-muted">Type a new category or select an existing one</small>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?php echo $item['description']; ?></textarea>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="quantity" name="quantity" min="0" step="1" value="<?php echo $item['quantity']; ?>" required>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="unit_price" class="form-label">Unit Price (₱) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="unit_price" name="unit_price" min="0" step="0.01" value="<?php echo $item['unit_price']; ?>" required>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="reorder_level" class="form-label">Reorder Level <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="reorder_level" name="reorder_level" min="0" step="1" value="<?php echo $item['reorder_level']; ?>" required>
                    <small class="text-muted">Minimum quantity before reordering</small>
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="index.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Item</button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
