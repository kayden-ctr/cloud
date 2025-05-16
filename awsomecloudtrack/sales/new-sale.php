<?php
// At the very top of new-sale.php

session_start();
require_once '../includes/db_connect.php';

// Handle POST and form processing here
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate, insert, etc.
    // On success:
    header("Location: index.php?message=Sale added successfully");
    exit();
}

// After processing and possible redirect, include header and output HTML
include '../includes/header.php';

// Fetch inventory items with quantity > 0
$items_query = "SELECT * FROM inventory_items WHERE quantity > 0 ORDER BY name ASC";
$items_result = $conn->query($items_query);

// Fetch active members
$members_query = "SELECT * FROM members WHERE status = 'active' ORDER BY name ASC";
$members_result = $conn->query($members_query);

$error_message = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn->begin_transaction();

    try {
        // Sanitize inputs
        $member_id = !empty($_POST['member_id']) ? intval($_POST['member_id']) : null;
        $payment_method = $_POST['payment_method'] ?? '';
        $notes = trim($_POST['notes'] ?? '');

        $item_ids = array_map('intval', $_POST['item_id'] ?? []);
        $quantities = array_map('intval', $_POST['quantity'] ?? []);
        $unit_prices = array_map('floatval', $_POST['unit_price'] ?? []);
        $total_prices = array_map('floatval', $_POST['total_price'] ?? []);

        if (count($item_ids) === 0) {
            throw new Exception("At least one sale item must be selected.");
        }

        $item_count = count($item_ids);
        $total_amount = 0;

        // Validate each item total and calculate grand total
        for ($i = 0; $i < $item_count; $i++) {
            $expected_total = $quantities[$i] * $unit_prices[$i];
            if (abs($expected_total - $total_prices[$i]) > 0.01) {
                throw new Exception("Price mismatch detected for item ID: " . $item_ids[$i]);
            }
            $total_amount += $expected_total;
        }

        // Insert into sales table
        $sale_query = "INSERT INTO sales (member_id, sale_date, item_count, total_amount, payment_method, notes, created_by) 
                       VALUES (?, NOW(), ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sale_query);
        $created_by = $_SESSION['user_id'] ?? 0;
        $stmt->bind_param("iidssi", $member_id, $item_count, $total_amount, $payment_method, $notes, $created_by);
        $stmt->execute();

        $sale_id = $conn->insert_id;

        // Insert each sale item and update inventory
        for ($i = 0; $i < $item_count; $i++) {
            // Insert sale item
            $item_query = "INSERT INTO sale_items (sale_id, item_id, quantity, unit_price, total_price) 
                           VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($item_query);
            $stmt->bind_param("iiidd", $sale_id, $item_ids[$i], $quantities[$i], $unit_prices[$i], $total_prices[$i]);
            $stmt->execute();

            // Update inventory, ensure stock is enough
            $update_query = "UPDATE inventory_items SET quantity = quantity - ? 
                             WHERE id = ? AND quantity >= ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("iii", $quantities[$i], $item_ids[$i], $quantities[$i]);
            $stmt->execute();

            if ($stmt->affected_rows === 0) {
                throw new Exception("Insufficient stock for item ID: " . $item_ids[$i]);
            }
        }

        // Log transaction
        $transaction_query = "INSERT INTO transactions (description, amount, type, category, transaction_date, reference_id, reference_type) 
                              VALUES (?, ?, 'income', 'Sales', NOW(), ?, 'sale')";
        $description = "Sale #" . $sale_id;
        $stmt = $conn->prepare($transaction_query);
        $stmt->bind_param("sdi", $description, $total_amount, $sale_id);
        $stmt->execute();

        $conn->commit();

        // Redirect after success (no output before this)
        header("Location: " . url("sales/view-sale.php?id=" . $sale_id . "&success=1"));
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error creating sale: " . $e->getMessage();
    }
}
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1><i class="bi bi-cart-plus me-2"></i>New Sale</h1>
        <p class="text-muted">Create a new sales transaction</p>
    </div>
    <div class="col-md-6 text-end">
        <a href="<?php echo url('sales/index.php'); ?>" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left"></i> Back to Sales
        </a>
    </div>
</div>

<?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="needs-validation" novalidate>
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Sale Items</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="saleItemsTable">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th width="120">Quantity</th>
                                    <th width="150">Unit Price</th>
                                    <th width="150">Total</th>
                                    <th width="50"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr id="itemRow0">
                                    <td>
                                        <select class="form-select item-select" name="item_id[]" required onchange="updateItemPrice(this)">
                                            <option value="">Select an item</option>
                                            <?php if ($items_result && $items_result->num_rows > 0): ?>
                                                <?php
                                                // Reset pointer so options show fresh here
                                                $items_result->data_seek(0);
                                                while ($item = $items_result->fetch_assoc()): ?>
                                                    <option value="<?php echo $item['id']; ?>" data-price="<?php echo $item['unit_price']; ?>" data-max="<?php echo $item['quantity']; ?>">
                                                        <?php echo htmlspecialchars($item['name']); ?> (<?php echo $item['quantity']; ?> available)
                                                    </option>
                                                <?php endwhile; ?>
                                            <?php endif; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control item-quantity" name="quantity[]" min="1" value="1" required onchange="calculateItemTotal(this)">
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control item-price" name="unit_price[]" step="0.01" readonly>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control item-total" name="total_price[]" step="0.01" readonly>
                                        </div>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)" disabled>
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5">
                                        <button type="button" class="btn btn-sm btn-success" onclick="addItem()">
                                            <i class="bi bi-plus-circle"></i> Add Item
                                        </button>
                                    </td>
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
                    <h5 class="mb-0">Sale Details</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="member_id" class="form-label">Member (Optional)</label>
                        <select class="form-select" id="member_id" name="member_id">
                            <option value="">Guest / Walk-in</option>
                            <?php if ($members_result && $members_result->num_rows > 0): ?>
                                <?php while ($member = $members_result->fetch_assoc()): ?>
                                    <option value="<?php echo $member['id']; ?>">
                                        <?php echo htmlspecialchars($member['name']); ?>
                                    </>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="payment_method" class="form-label">Payment Method</label>
                        <select class="form-select" id="payment_method" name="payment_method" required>
                            <option value="">Select payment method</option>
                            <option value="Cash">Cash</option>
                            <option value="GCash">GCash</option>
                            <option value="Credit Card">Credit Card</option>
                            <option value="Other">Other</option>
                        </select>
                        <div class="invalid-feedback">
                            Please select a payment method.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Additional notes..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Grand Total</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="text" class="form-control fw-bold" id="grandTotal" readonly value="0.00">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Complete Sale</button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
let itemIndex = 1;

function updateItemPrice(selectElem) {
    const row = selectElem.closest('tr');
    const priceInput = row.querySelector('.item-price');
    const quantityInput = row.querySelector('.item-quantity');
    const totalInput = row.querySelector('.item-total');

    const selectedOption = selectElem.options[selectElem.selectedIndex];
    const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
    const maxQty = parseInt(selectedOption.getAttribute('data-max')) || 0;

    priceInput.value = price.toFixed(2);

    // Adjust max quantity allowed
    quantityInput.max = maxQty > 0 ? maxQty : 1;

    // Reset quantity to 1 or max if zero
    quantityInput.value = maxQty > 0 ? 1 : 0;

    // Update total price
    totalInput.value = (price * quantityInput.value).toFixed(2);

    updateGrandTotal();
}

function calculateItemTotal(quantityInput) {
    const row = quantityInput.closest('tr');
    const priceInput = row.querySelector('.item-price');
    const totalInput = row.querySelector('.item-total');

    let quantity = parseInt(quantityInput.value) || 0;
    const maxQty = parseInt(quantityInput.max) || 0;

    if (quantity > maxQty) {
        quantity = maxQty;
        quantityInput.value = maxQty;
        alert("Quantity exceeds available stock.");
    } else if (quantity < 1) {
        quantity = 1;
        quantityInput.value = 1;
    }

    const price = parseFloat(priceInput.value) || 0;
    totalInput.value = (price * quantity).toFixed(2);

    updateGrandTotal();
}

function updateGrandTotal() {
    const totalInputs = document.querySelectorAll('.item-total');
    let grandTotal = 0;
    totalInputs.forEach(input => {
        grandTotal += parseFloat(input.value) || 0;
    });
    document.getElementById('grandTotal').value = grandTotal.toFixed(2);
}

function addItem() {
    const tbody = document.querySelector('#saleItemsTable tbody');
    const newRow = document.createElement('tr');
    newRow.id = 'itemRow' + itemIndex;

    newRow.innerHTML = `
        <td>
            <select class="form-select item-select" name="item_id[]" required onchange="updateItemPrice(this)">
                <option value="">Select an item</option>
                <?php
                $items_result->data_seek(0);
                while ($item = $items_result->fetch_assoc()):
                ?>
                    <option value="<?php echo $item['id']; ?>" data-price="<?php echo $item['unit_price']; ?>" data-max="<?php echo $item['quantity']; ?>">
                        <?php echo htmlspecialchars($item['name']); ?> (<?php echo $item['quantity']; ?> available)
                    </option>
                <?php endwhile; ?>
            </select>
        </td>
        <td>
            <input type="number" class="form-control item-quantity" name="quantity[]" min="1" value="1" required onchange="calculateItemTotal(this)">
        </td>
        <td>
            <div class="input-group">
                <span class="input-group-text">₱</span>
                <input type="number" class="form-control item-price" name="unit_price[]" step="0.01" readonly>
            </div>
        </td>
        <td>
            <div class="input-group">
                <span class="input-group-text">₱</span>
                <input type="number" class="form-control item-total" name="total_price[]" step="0.01" readonly>
            </div>
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;

    tbody.appendChild(newRow);
    itemIndex++;
}

function removeItem(button) {
    const tbody = document.querySelector('#saleItemsTable tbody');
    if (tbody.rows.length > 1) {
        const row = button.closest('tr');
        row.remove();
        updateGrandTotal();
    } else {
        alert("You must have at least one sale item.");
    }
}

// Bootstrap form validation
(() => {
    'use strict'
    const forms = document.querySelectorAll('.needs-validation')
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})();

window.onload = () => {
    // Initialize price and totals for first row
    const firstSelect = document.querySelector('.item-select');
    if (firstSelect) updateItemPrice(firstSelect);
}
</script>

<?php include '../includes/footer.php'; ?>
