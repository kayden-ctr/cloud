<?php
include '../includes/db_connect.php';
include '../includes/header.php';

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);

    // Start transaction
    $conn->begin_transaction();

    try {
        // Get sale items
        $sale_query = "SELECT * FROM sale_items WHERE sale_id = ?";
        $stmt1 = $conn->prepare($sale_query);
        $stmt1->bind_param("i", $id);
        $stmt1->execute();
        $sale_items_result = $stmt1->get_result();

        // Return items to inventory
        while ($item = $sale_items_result->fetch_assoc()) {
            $update_inventory = "UPDATE inventory_items SET quantity = quantity + ? WHERE id = ?";
            $stmt2 = $conn->prepare($update_inventory);
            $stmt2->bind_param("ii", $item['quantity'], $item['item_id']);
            $stmt2->execute();
            $stmt2->close();
        }
        $stmt1->close();

        // Delete sale items
        $delete_items = "DELETE FROM sale_items WHERE sale_id = ?";
        $stmt3 = $conn->prepare($delete_items);
        $stmt3->bind_param("i", $id);
        $stmt3->execute();
        $stmt3->close();

        // Delete sale
        $delete_sale = "DELETE FROM sales WHERE id = ?";
        $stmt4 = $conn->prepare($delete_sale);
        $stmt4->bind_param("i", $id);
        $stmt4->execute();
        $stmt4->close();

        // Delete related transaction if exists
        $delete_transaction = "DELETE FROM transactions WHERE reference_id = ? AND reference_type = 'sale'";
        $stmt5 = $conn->prepare($delete_transaction);
        $stmt5->bind_param("i", $id);
        $stmt5->execute();
        $stmt5->close();

        // Commit
        $conn->commit();
        $success_message = "Sale deleted successfully";
        
        // Redirect to avoid resubmission and refresh page
        header("Location: index.php?deleted=1");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error deleting sale: " . $e->getMessage();
    }
}

// Show success message after redirect
if (isset($_GET['deleted']) && $_GET['deleted'] == 1) {
    $success_message = "Sale deleted successfully.";
}

// Pagination setup
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Get total count
$count_query = "SELECT COUNT(*) as total FROM sales";
$count_result = $conn->query($count_query);
$count_data = $count_result->fetch_assoc();
$total_records = $count_data['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get sales with member name, pagination
$query = "SELECT s.*, m.name as member_name 
          FROM sales s 
          LEFT JOIN members m ON s.member_id = m.id 
          ORDER BY s.sale_date DESC 
          LIMIT ?, ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $offset, $records_per_page);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1>Sales Management</h1>
    </div>
    <div class="col-md-6 text-end">
        <a href="new-sale.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Sale
        </a>
        <a href="reports.php" class="btn btn-secondary ms-2">
            <i class="bi bi-file-earmark-text"></i> Sales Reports
        </a>
    </div>
</div>

<?php if (isset($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($success_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($error_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0">Sales History</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Member</th>
                        <th>Items</th>
                        <th>Total Amount</th>
                        <th>Payment Method</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($sale = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $sale['id'] ?></td>
                                <td><?= date('M d, Y', strtotime($sale['sale_date'])) ?></td>
                                <td><?= htmlspecialchars($sale['member_name'] ?? 'Guest') ?></td>
                                <td><?= htmlspecialchars($sale['item_count']) ?></td>
                                <td>₱<?= number_format($sale['total_amount'], 2) ?></td>
                                <td><?= htmlspecialchars($sale['payment_method']) ?></td>
                                <td>
                                    <a href="view-sale.php?id=<?= $sale['id'] ?>" class="btn btn-sm btn-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $sale['id'] ?>" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>

                                    <!-- Delete Confirmation Modal -->
                                    <div class="modal fade" id="deleteModal<?= $sale['id'] ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?= $sale['id'] ?>" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="deleteModalLabel<?= $sale['id'] ?>">Confirm Delete</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Are you sure you want to delete this sale?</p>
                                                    <p class="text-danger"><strong>Warning:</strong> This will return items to inventory and delete related financial transactions.</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <a href="index.php?delete=<?= $sale['id'] ?>" class="btn btn-danger">Delete</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No sales found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= ($page <= 1) ? '#' : '?page=' . ($page - 1) ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= ($page >= $total_pages) ? '#' : '?page=' . ($page + 1) ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<?php include '../includes/footer.php'; ?>
