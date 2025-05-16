<?php
include '../includes/db_connect.php';
include '../includes/header.php';

// Check if ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: " . url("procurement/index.php"));
    exit();
}

$id = $_GET['id'];

// Get order data
$query = "SELECT p.*, u.username as created_by_name
          FROM procurement_orders p 
          LEFT JOIN users u ON p.created_by = u.id
          WHERE p.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0) {
    header("Location: " . url("procurement/index.php"));
    exit();
}

$order = $result->fetch_assoc();

// Get order items
$items_query = "SELECT pi.*, i.name as item_name, i.category as item_category
               FROM procurement_items pi
               JOIN inventory_items i ON pi.item_id = i.id
               WHERE pi.order_id = ?";
$stmt = $conn->prepare($items_query);
$stmt->bind_param("i", $id);
$stmt->execute();
$items_result = $stmt->get_result();

// Handle receive request
$success_message = '';
$error_message = '';

if(isset($_POST['receive_order']) && $order['status'] == 'pending') {
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update order status
        $update_query = "UPDATE procurement_orders SET status = 'received', received_date = NOW() WHERE id = ? AND status = 'pending'";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        if($stmt->affected_rows > 0) {
            // Get order items
            $items_query = "SELECT * FROM procurement_items WHERE order_id = ?";
            $stmt = $conn->prepare($items_query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $items_result = $stmt->get_result();
            
            // Update inventory
            while($item = $items_result->fetch_assoc()) {
                $update_inventory = "UPDATE inventory_items SET quantity = quantity + ? WHERE id = ?";
                $stmt = $conn->prepare($update_inventory);
                $stmt->bind_param("ii", $item['quantity'], $item['item_id']);
                $stmt->execute();
            }
            
            // Add expense transaction
            $transaction_query = "INSERT INTO transactions (description, amount, type, category, transaction_date, reference_id, reference_type) 
                                VALUES (?, ?, 'expense', 'Procurement', NOW(), ?, 'procurement')";
            $description = "Procurement Order #" . $id;
            $stmt = $conn->prepare($transaction_query);
            $stmt->bind_param("sdi", $description, $order['total_amount'], $id);
            $stmt->execute();
            
            $conn->commit();
            $success_message = "Order received successfully and inventory updated";
            
            // Refresh order data
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $order = $result->fetch_assoc();
            
            // Refresh items data
            $stmt = $conn->prepare($items_query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $items_result = $stmt->get_result();
        } else {
            $conn->rollback();
            $error_message = "Order not found or already received";
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error receiving order: " . $e->getMessage();
    }
}
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1><i class="bi bi-truck me-2"></i>Procurement Order Details</h1>
        <p class="text-muted">Order #<?php echo $order['id']; ?> - <?php echo date('M d, Y', strtotime($order['order_date'])); ?></p>
    </div>
    <div class="col-md-6 text-end">
        <button type="button" class="btn btn-outline-secondary btn-print">
            <i class="bi bi-printer"></i> Print
        </button>
        <a href="<?php echo url('procurement/index.php'); ?>" class="btn btn-outline-primary ms-2">
            <i class="bi bi-arrow-left"></i> Back to Procurement
        </a>
    </div>
</div>

<?php if(isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> Order created successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

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

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Order Items</h5>
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
                                <th class="text-end">₱<?php echo number_format($order['total_amount'], 2); ?></th>
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
                <h5 class="mb-0">Order Information</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Order ID:</span>
                        <span class="fw-semibold">#<?php echo $order['id']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Supplier:</span>
                        <span><?php echo $order['supplier']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Order Date:</span>
                        <span><?php echo date('M d, Y', strtotime($order['order_date'])); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Status:</span>
                        <span>
                            <?php if($order['status'] == 'pending'): ?>
                                <span class="badge bg-warning">Pending</span>
                            <?php elseif($order['status'] == 'received'): ?>
                                <span class="badge bg-success">Received</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Cancelled</span>
                            <?php endif; ?>
                        </span>
                    </li>
                    <?php if($order['status'] == 'received'): ?>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Received Date:</span>
                            <span><?php echo date('M d, Y', strtotime($order['received_date'])); ?></span>
                        </li>
                    <?php endif; ?>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Items:</span>
                        <span><?php echo $order['item_count']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Total Amount:</span>
                        <span class="fw-bold text-primary">₱<?php echo number_format($order['total_amount'], 2); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Created By:</span>
                        <span><?php echo $order['created_by_name']; ?></span>
                    </li>
                </ul>
                
                <?php if($order['status'] == 'pending'): ?>
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $id; ?>" class="mt-3">
                        <div class="d-grid">
                            <button type="submit" name="receive_order" class="btn btn-success">
                                <i class="bi bi-check-circle"></i> Mark as Received
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if($order['notes']): ?>
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Notes</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0"><?php echo nl2br($order['notes']); ?></p>
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
