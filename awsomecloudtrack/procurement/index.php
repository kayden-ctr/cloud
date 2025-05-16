<?php
include '../includes/db_connect.php';
include '../includes/header.php';

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];

    // Start transaction
    $conn->begin_transaction();

    try {
        // Lock the row to prevent race conditions
        $check_query = "SELECT status FROM procurement_orders WHERE id = ? FOR UPDATE";
        $stmt = $conn->prepare($check_query);
        if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();

        if (!$order) {
            throw new Exception("Order not found.");
        }

        if ($order['status'] !== 'pending') {
            throw new Exception("Only pending orders can be deleted.");
        }

        // Delete order items first
        $delete_items = "DELETE FROM procurement_items WHERE order_id = ?";
        $stmt = $conn->prepare($delete_items);
        if (!$stmt) throw new Exception("Prepare failed (delete items): " . $conn->error);
        $stmt->bind_param("i", $id);
        $stmt->execute();

        // Delete the order
        $delete_order = "DELETE FROM procurement_orders WHERE id = ?";
        $stmt = $conn->prepare($delete_order);
        if (!$stmt) throw new Exception("Prepare failed (delete order): " . $conn->error);
        $stmt->bind_param("i", $id);
        $stmt->execute();

        // Commit transaction
        $conn->commit();

        $success_message = "Order deleted successfully.";
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $error_message = "Error deleting order: " . $e->getMessage();
    }
}

// Handle receive request
if (isset($_GET['receive']) && is_numeric($_GET['receive'])) {
    $id = $_GET['receive'];

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update order status if pending
        $update_query = "UPDATE procurement_orders SET status = 'received', received_date = NOW() WHERE id = ? AND status = 'pending'";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("i", $id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            // Get order items
            $items_query = "SELECT * FROM procurement_items WHERE order_id = ?";
            $stmt = $conn->prepare($items_query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $items_result = $stmt->get_result();

            // Update inventory quantities
            while ($item = $items_result->fetch_assoc()) {
                $update_inventory = "UPDATE inventory_items SET quantity = quantity + ? WHERE id = ?";
                $stmt = $conn->prepare($update_inventory);
                $stmt->bind_param("ii", $item['quantity'], $item['item_id']);
                $stmt->execute();
            }

            // Get order total amount
            $order_query = "SELECT total_amount FROM procurement_orders WHERE id = ?";
            $stmt = $conn->prepare($order_query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $order_result = $stmt->get_result();
            $order = $order_result->fetch_assoc();

            // Insert expense transaction
            $transaction_query = "INSERT INTO transactions (description, amount, type, category, transaction_date, reference_id, reference_type) 
                                  VALUES (?, ?, 'expense', 'Procurement', NOW(), ?, 'procurement')";
            $description = "Procurement Order #" . $id;
            $stmt = $conn->prepare($transaction_query);
            $stmt->bind_param("sdi", $description, $order['total_amount'], $id);
            $stmt->execute();

            $conn->commit();
            $success_message = "Order received successfully and inventory updated.";
        } else {
            $conn->rollback();
            $error_message = "Order not found or already received.";
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error receiving order: " . $e->getMessage();
    }
}

// Get all procurement orders
$query = "SELECT * FROM procurement_orders ORDER BY order_date DESC";
$result = $conn->query($query);
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1>Procurement Management</h1>
    </div>
    <div class="col-md-6 text-end">
        <a href="new-order.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Order
        </a>
    </div>
</div>

<?php if (isset($success_message)) : ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($success_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($error_message)) : ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($error_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0">Procurement Orders</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="procurementTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Order Date</th>
                        <th>Supplier</th>
                        <th>Items</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Received Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0) : ?>
                        <?php while ($order = $result->fetch_assoc()) : ?>
                            <tr>
                                <td><?= htmlspecialchars($order['id']) ?></td>
                                <td><?= date('M d, Y', strtotime($order['order_date'])) ?></td>
                                <td><?= htmlspecialchars($order['supplier']) ?></td>
                                <td><?= htmlspecialchars($order['item_count']) ?></td>
                                <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                                <td>
                                    <?php if ($order['status'] == 'pending') : ?>
                                        <span class="badge bg-warning">Pending</span>
                                    <?php elseif ($order['status'] == 'received') : ?>
                                        <span class="badge bg-success">Received</span>
                                    <?php else : ?>
                                        <span class="badge bg-secondary">Cancelled</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= $order['received_date'] ? date('M d, Y', strtotime($order['received_date'])) : '-' ?>
                                </td>
                                <td>
                                    <a href="view-order.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-info">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    <?php if ($order['status'] == 'pending') : ?>
                                        <a href="#" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#receiveModal<?= $order['id'] ?>">
                                            <i class="bi bi-check-circle"></i>
                                        </a>
                                        <a href="#" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $order['id'] ?>">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    <?php endif; ?>

                                    <!-- Receive Confirmation Modal -->
                                    <div class="modal fade" id="receiveModal<?= $order['id'] ?>" tabindex="-1" aria-labelledby="receiveModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="receiveModalLabel">Confirm Receive Order</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Are you sure you want to mark this order as received?</p>
                                                    <p>This will:</p>
                                                    <ul>
                                                        <li>Update inventory quantities</li>
                                                        <li>Create an expense transaction</li>
                                                        <li>Change order status to "Received"</li>
                                                    </ul>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <a href="index.php?receive=<?= $order['id'] ?>" class="btn btn-success">Receive Order</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Delete Confirmation Modal -->
                                    <div class="modal fade" id="deleteModal<?= $order['id'] ?>" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Are you sure you want to delete this order?
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <a href="index.php?delete=<?= $order['id'] ?>" class="btn btn-danger">Delete</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="8" class="text-center">No procurement orders found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Initialize DataTable if available
        if ($.fn.DataTable) {
            $('#procurementTable').DataTable({
                responsive: true
            });
        }
    });
</script>

<?php include '../includes/footer.php'; ?>
