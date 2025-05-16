<?php
include '../includes/db_connect.php';
include '../includes/header.php';

// Handle delete request via GET
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $delete_query = "DELETE FROM transactions WHERE id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Redirect to avoid resubmission and show success message
        header("Location: index.php?deleted=1");
        exit();
    } else {
        $error_message = "Error deleting transaction: " . $conn->error;
    }
}

// Show success message after delete
if (isset($_GET['deleted']) && $_GET['deleted'] == 1) {
    $success_message = "Transaction deleted successfully.";
}

// Get financial summary
$summary_query = "SELECT 
                    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
                    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense
                  FROM transactions";
$summary_result = $conn->query($summary_query);
$summary = $summary_result->fetch_assoc();

// Pagination setup
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Get total records count
$count_query = "SELECT COUNT(*) as total FROM transactions";
$count_result = $conn->query($count_query);
$count_data = $count_result->fetch_assoc();
$total_records = $count_data['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get transactions with pagination
$query = "SELECT * FROM transactions ORDER BY transaction_date DESC LIMIT ?, ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $offset, $records_per_page);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1>Financial Management</h1>
    </div>
    <div class="col-md-6 text-end">
        <a href="add-transaction.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Transaction
        </a>
        <a href="reports.php" class="btn btn-secondary ms-2">
            <i class="bi bi-file-earmark-text"></i> Financial Reports
        </a>
    </div>
</div>

<?php if (isset($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $success_message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $error_message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Financial Summary Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted">Total Income</h6>
                    <h2 class="mb-0 text-success">₱<?= number_format($summary['total_income'] ?? 0, 2) ?></h2>
                </div>
                <div class="bg-success text-white p-3 rounded">
                    <i class="bi bi-graph-up-arrow fs-1"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted">Total Expenses</h6>
                    <h2 class="mb-0 text-danger">₱<?= number_format($summary['total_expense'] ?? 0, 2) ?></h2>
                </div>
                <div class="bg-danger text-white p-3 rounded">
                    <i class="bi bi-graph-down-arrow fs-1"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted">Current Balance</h6>
                    <h2 class="mb-0 <?= (($summary['total_income'] ?? 0) - ($summary['total_expense'] ?? 0)) >= 0 ? 'text-primary' : 'text-danger' ?>">
                        ₱<?= number_format(($summary['total_income'] ?? 0) - ($summary['total_expense'] ?? 0), 2) ?>
                    </h2>
                </div>
                <div class="bg-primary text-white p-3 rounded">
                    <i class="bi bi-cash-stack fs-1"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0">Transaction History</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($transaction = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $transaction['id'] ?></td>
                                <td><?= date('M d, Y', strtotime($transaction['transaction_date'])) ?></td>
                                <td><?= htmlspecialchars($transaction['description']) ?></td>
                                <td><?= htmlspecialchars($transaction['category']) ?></td>
                                <td>
                                    <?php if ($transaction['type'] === 'income'): ?>
                                        <span class="badge bg-success">Income</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Expense</span>
                                    <?php endif; ?>
                                </td>
                                <td>₱<?= number_format($transaction['amount'], 2) ?></td>
                                <td>
                                    <a href="edit-transaction.php?id=<?= $transaction['id'] ?>" class="btn btn-sm btn-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $transaction['id'] ?>" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>

                                    <!-- Delete Confirmation Modal -->
                                    <div class="modal fade" id="deleteModal<?= $transaction['id'] ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?= $transaction['id'] ?>" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="deleteModalLabel<?= $transaction['id'] ?>">Confirm Delete</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Are you sure you want to delete this transaction?
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <a href="index.php?delete=<?= $transaction['id'] ?>" class="btn btn-danger">Delete</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">No transactions found</td>
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
