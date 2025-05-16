<?php
include '../includes/db_connect.php';
include '../includes/header.php';

// Get low stock items
$query = "SELECT * FROM inventory_items WHERE quantity <= reorder_level ORDER BY quantity ASC";
$result = $conn->query($query);
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1><i class="bi bi-exclamation-triangle me-2"></i>Low Stock Items</h1>
        <p class="text-muted">Items that need to be reordered soon</p>
    </div>
    <div class="col-md-6 text-end">
        <a href="<?php echo url('procurement/new-order.php'); ?>" class="btn btn-primary">
            <i class="bi bi-truck"></i> Create Procurement Order
        </a>
        <a href="<?php echo url('inventory/index.php'); ?>" class="btn btn-outline-secondary ms-2">
            <i class="bi bi-arrow-left"></i> Back to Inventory
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">Low Stock Items</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="lowStockTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Item Name</th>
                        <th>Category</th>
                        <th>Current Quantity</th>
                        <th>Reorder Level</th>
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
                                <td><?php echo $item['reorder_level']; ?></td>
                                <td>
                                    <?php if($item['quantity'] <= 0): ?>
                                        <span class="badge bg-danger">Out of Stock</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Low Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="<?php echo url('inventory/edit-item.php?id=' . $item['id']); ?>" class="btn btn-sm btn-primary" title="Edit Item">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="#" class="btn btn-sm btn-success" title="Add to Procurement">
                                            <i class="bi bi-plus-circle"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <p class="mt-3 text-success">No low stock items found. Your inventory levels are good!</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<?php include '../includes/footer.php'; ?>
