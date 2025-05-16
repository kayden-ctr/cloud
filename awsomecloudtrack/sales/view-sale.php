<?php
include '../includes/db_connect.php';
include '../includes/header.php';

// Check if ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: " . url("sales/index.php"));
    exit();
}

$id = $_GET['id'];

// Get sale data
$query = "SELECT s.*, m.name as member_name, m.email as member_email, m.phone as member_phone, u.username as created_by_name
          FROM sales s 
          LEFT JOIN members m ON s.member_id = m.id
          LEFT JOIN users u ON s.created_by = u.id
          WHERE s.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0) {
    header("Location: " . url("sales/index.php"));
    exit();
}

$sale = $result->fetch_assoc();

// Get sale items
$items_query = "SELECT si.*, i.name as item_name, i.category as item_category
               FROM sale_items si
               JOIN inventory_items i ON si.item_id = i.id
               WHERE si.sale_id = ?";
$stmt = $conn->prepare($items_query);
$stmt->bind_param("i", $id);
$stmt->execute();
$items_result = $stmt->get_result();
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1><i class="bi bi-receipt me-2"></i>Sale Details</h1>
        <p class="text-muted">Sale #<?php echo $sale['id']; ?> - <?php echo date('M d, Y', strtotime($sale['sale_date'])); ?></p>
    </div>
    <div class="col-md-6 text-end">
        <button type="button" class="btn btn-outline-secondary btn-print">
            <i class="bi bi-printer"></i> Print
        </button>
        <a href="<?php echo url('sales/index.php'); ?>" class="btn btn-outline-primary ms-2">
            <i class="bi bi-arrow-left"></i> Back to Sales
        </a>
    </div>
</div>

<?php if(isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> Sale created successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Sale Items</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Category</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($items_result && $items_result->num_rows > 0): ?>
                                <?php while($item = $items_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $item['item_name']; ?></td>
                                        <td><?php echo $item['item_category']; ?></td>
                                        <td class="text-center"><?php echo $item['quantity']; ?></td>
                                        <td class="text-end">₱<?php echo number_format($item['unit_price'], 2); ?></td>
                                        <td class="text-end">₱<?php echo number_format($item['total_price'], 2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No items found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="table-group-divider">
                            <tr>
                                <th colspan="4" class="text-end">Total:</th>
                                <th class="text-end">₱<?php echo number_format($sale['total_amount'], 2); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Sale Information</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Sale ID:</span>
                        <span class="fw-semibold">#<?php echo $sale['id']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Date:</span>
                        <span><?php echo date('M d, Y h:i A', strtotime($sale['sale_date'])); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Payment Method:</span>
                        <span><?php echo $sale['payment_method']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Items:</span>
                        <span><?php echo $sale['item_count']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Total Amount:</span>
                        <span class="fw-bold text-success">₱<?php echo number_format($sale['total_amount'], 2); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Created By:</span>
                        <span><?php echo $sale['created_by_name']; ?></span>
                    </li>
                </ul>
            </div>
        </div>
        
        <?php if($sale['member_id']): ?>
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Member Information</h5>
                </div>
                <div class="card-body">
                    <h6 class="fw-bold"><?php echo $sale['member_name']; ?></h6>
                    <p class="mb-0">
                        <?php if($sale['member_email']): ?>
                            <i class="bi bi-envelope me-2"></i> <?php echo $sale['member_email']; ?><br>
                        <?php endif; ?>
                        <?php if($sale['member_phone']): ?>
                            <i class="bi bi-telephone me-2"></i> <?php echo $sale['member_phone']; ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if($sale['notes']): ?>
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Notes</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0"><?php echo nl2br($sale['notes']); ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Print functionality
document.querySelector('.btn-print')?.addEventListener('click', function() {
    window.print();
});
</script>

<?php include '../includes/footer.php'; ?>
