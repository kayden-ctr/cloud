<?php
include '../includes/db_connect.php';
include '../includes/header.php';

// Handle delete request
if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    $delete_query = "DELETE FROM inventory_items WHERE id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $id);
    
    if($stmt->execute()) {
        $success_message = "Item deleted successfully";
    } else {
        $error_message = "Error deleting item: " . $conn->error;
    }
}

// Get all inventory items
$query = "SELECT * FROM inventory_items ORDER BY name ASC";
$result = $conn->query($query);
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1><i class="bi bi-box-seam me-2"></i>Inventory Management</h1>
        <p class="text-muted">Manage your organization's inventory items</p>
    </div>
    <div class="col-md-6 text-end">
        <a href="<?php echo url('inventory/add-item.php'); ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add New Item
        </a>
        <a href="<?php echo url('inventory/low-stock.php'); ?>" class="btn btn-warning ms-2">
            <i class="bi bi-exclamation-triangle"></i> Low Stock Items
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

<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Inventory Items</h5>
        <div class="input-group" style="max-width: 300px;">
            <input type="text" class="form-control" id="searchInventory" placeholder="Search items...">
            <button class="btn btn-outline-secondary" type="button">
                <i class="bi bi-search"></i>
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="inventoryTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Item Name</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total Value</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($item = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $item['id']; ?></td>
                                <td>
                                    <div class="fw-semibold"><?php echo $item['name']; ?></div>
                                    <div class="small text-muted"><?php echo substr($item['description'], 0, 50) . (strlen($item['description']) > 50 ? '...' : ''); ?></div>
                                </td>
                                <td><span class="badge bg-light text-dark"><?php echo $item['category']; ?></span></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>₱<?php echo number_format($item['unit_price'], 2); ?></td>
                                <td>₱<?php echo number_format($item['quantity'] * $item['unit_price'], 2); ?></td>
                                <td>
                                    <?php if($item['quantity'] <= 0): ?>
                                        <span class="badge bg-danger">Out of Stock</span>
                                    <?php elseif($item['quantity'] <= $item['reorder_level']): ?>
                                        <span class="badge bg-warning">Low Stock</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">In Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="<?php echo url('inventory/edit-item.php?id=' . $item['id']); ?>" class="btn btn-sm btn-primary" title="Edit Item">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="#" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $item['id']; ?>" title="Delete Item">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                    
                                    <!-- Delete Confirmation Modal -->
                                    <div class="modal fade" id="deleteModal<?php echo $item['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Are you sure you want to delete <strong><?php echo $item['name']; ?></strong>?</p>
                                                    <p class="text-muted small">This action cannot be undone.</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <a href="<?php echo url('inventory/index.php?delete=' . $item['id']); ?>" class="btn btn-danger">Delete</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <p class="mt-3 text-muted">No inventory items found</p>
                                <a href="<?php echo url('inventory/add-item.php'); ?>" class="btn btn-primary">Add Your First Item</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Simple search functionality
document.getElementById('searchInventory')?.addEventListener('keyup', function() {
    const value = this.value.toLowerCase();
    const rows = document.querySelectorAll('#inventoryTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(value) ? '' : 'none';
    });
});
</script>

<?php include '../includes/footer.php'; ?>
