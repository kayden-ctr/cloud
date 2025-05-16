<?php
include '../includes/db_connect.php';
include '../includes/header.php';

// Get all inventory items
$items_query = "SELECT * FROM inventory_items ORDER BY name ASC";
$items_result = $conn->query($items_query);

// Get low stock items
$low_stock_query = "SELECT * FROM inventory_items WHERE quantity <= reorder_level ORDER BY quantity ASC";
$low_stock_result = $conn->query($low_stock_query);

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get form data
        $supplier = $_POST['supplier'];
        $notes = $_POST['notes'];
        $item_ids = $_POST['item_id'];
        $quantities = $_POST['quantity'];
        $unit_prices = $_POST['unit_price'];
        $total_prices = $_POST['total_price'];
        
        // Calculate totals
        $item_count = count($item_ids);
        $total_amount = 0;
        foreach ($total_prices as $price) {
            $total_amount += $price;
        }
        
        // Insert order record
        $order_query = "INSERT INTO procurement_orders (supplier, order_date, item_count, total_amount, status, notes, created_by) 
                        VALUES (?, NOW(), ?, ?, 'pending', ?, ?)";
        $stmt = $conn->prepare($order_query);
        $created_by = $_SESSION['user_id'];
        $stmt->bind_param("sidsi", $supplier, $item_count, $total_amount, $notes, $created_by);
        $stmt->execute();
        
        // Get the order ID
        $order_id = $conn->insert_id;
        
        // Insert order items
        for ($i = 0; $i < count($item_ids); $i++) {
            $item_query = "INSERT INTO procurement_items (order_id, item_id, quantity, unit_price, total_price) 
                          VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($item_query);
            $stmt->bind_param("iidd", $order_id, $item_ids[$i], $quantities[$i], $unit_prices[$i], $total_prices[$i]);
            $stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        // Redirect to view order page
        header("Location: " . url("procurement/view-order.php?id=" . $order_id . "&success=1"));
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $error_message = "Error creating order: " . $e->getMessage();
    }
}
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1><i class="bi bi-truck me-2"></i>New Procurement Order</h1>
        <p class="text-muted">Create a new order to restock inventory</p>
    </div>
    <div class="col-md-6 text-end">
        <a href="<?php echo url('procurement/index.php'); ?>" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left"></i> Back to Procurement
        </a>
    </div>
</div>

<?php if(isset($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="orderForm" class="needs-validation" novalidate>
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Order Items</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="orderItemsTable">
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
                                                <?php while($item = $items_result->fetch_assoc()): ?>
                                                    <option value="<?php echo $item['id']; ?>" data-price="<?php echo $item['unit_price']; ?>">
                                                        <?php echo $item['name']; ?> 
                                                        <?php if($item['quantity'] <= $item['reorder_level']): ?>
                                                            (Low Stock: <?php echo $item['quantity']; ?>)
                                                        <?php endif; ?>
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
                                            <input type="number" class="form-control item-price" name="unit_price[]" step="0.01" required onchange="calculateItemTotal(this)">
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
            
            <?php if ($low_stock_result && $low_stock_result->num_rows > 0): ?>
                <div class="card mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Low Stock Items</h5>
                        <button type="button" class="btn btn-sm btn-warning" onclick="addLowStockItems()">
                            <i class="bi bi-plus-circle"></i> Add All Low Stock Items
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Current Stock</th>
                                        <th>Reorder Level</th>
                                        <th>Suggested Order</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($item = $low_stock_result->fetch_assoc()): ?>
                                        <?php $suggested_order = max(1, $item['reorder_level'] - $item['quantity']); ?>
                                        <tr>
                                            <td><?php echo $item['name']; ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td><?php echo $item['reorder_level']; ?></td>
                                            <td><?php echo $suggested_order; ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary" 
                                                        onclick="addSpecificItem(<?php echo $item['id']; ?>, '<?php echo $item['name']; ?>', <?php echo $item['unit_price']; ?>, <?php echo $suggested_order; ?>)">
                                                    <i class="bi bi-plus-circle"></i> Add
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Order Details</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="supplier" class="form-label">Supplier <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="supplier" name="supplier" required>
                        <div class="invalid-feedback">Please enter a supplier name.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                    
                    <div class="alert alert-primary mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Total Items:</span>
                            <span id="totalItemCount">0</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Total Amount:</span>
                            <span id="totalAmount">₱0.00</span>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary" id="submitOrder">
                            <i class="bi bi-check-circle"></i> Create Order
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Initialize variables
let itemCount = 1;
let itemRowTemplate = document.getElementById('itemRow0').cloneNode(true);

// Function to add a new item row
function addItem() {
    const tbody = document.querySelector('#orderItemsTable tbody');
    const newRow = itemRowTemplate.cloneNode(true);
    newRow.id = 'itemRow' + itemCount;
    
    // Reset values
    newRow.querySelector('.item-select').value = '';
    newRow.querySelector('.item-quantity').value = 1;
    newRow.querySelector('.item-price').value = '';
    newRow.querySelector('.item-total').value = '';
    
    // Enable remove button
    newRow.querySelector('.btn-danger').disabled = false;
    
    // Add event listeners
    newRow.querySelector('.item-select').addEventListener('change', function() {
        updateItemPrice(this);
    });
    
    newRow.querySelector('.item-quantity').addEventListener('change', function() {
        calculateItemTotal(this);
    });
    
    newRow.querySelector('.item-price').addEventListener('change', function() {
        calculateItemTotal(this);
    });
    
    tbody.appendChild(newRow);
    itemCount++;
    
    // Enable the first row's remove button if we have more than one row
    if (tbody.children.length > 1) {
        document.querySelector('#itemRow0 .btn-danger').disabled = false;
    }
    
    updateTotals();
}

// Function to remove an item row
function removeItem(button) {
    const row = button.closest('tr');
    row.remove();
    
    // If only one row remains, disable its remove button
    const tbody = document.querySelector('#orderItemsTable tbody');
    if (tbody.children.length === 1) {
        tbody.querySelector('.btn-danger').disabled = true;
    }
    
    updateTotals();
}

// Function to update item price when an item is selected
function updateItemPrice(select) {
    const row = select.closest('tr');
    const option = select.options[select.selectedIndex];
    const priceInput = row.querySelector('.item-price');
    
    if (option.value) {
        const price = parseFloat(option.dataset.price);
        priceInput.value = price.toFixed(2);
        calculateItemTotal(priceInput);
    } else {
        priceInput.value = '';
        row.querySelector('.item-total').value = '';
    }
    
    updateTotals();
}

// Function to calculate item total
function calculateItemTotal(input) {
    const row = input.closest('tr');
    const quantity = parseInt(row.querySelector('.item-quantity').value) || 0;
    const price = parseFloat(row.querySelector('.item-price').value) || 0;
    const totalInput = row.querySelector('.item-total');
    
    const total = quantity * price;
    totalInput.value = total.toFixed(2);
    
    updateTotals();
}

// Function to update overall totals
function updateTotals() {
    const totalItems = document.querySelectorAll('#orderItemsTable tbody tr').length;
    let totalAmount = 0;
    
    document.querySelectorAll('.item-total').forEach(input => {
        totalAmount += parseFloat(input.value) || 0;
    });
    
    document.getElementById('totalItemCount').textContent = totalItems;
    document.getElementById('totalAmount').textContent = '₱' + totalAmount.toFixed(2);
}

// Function to add a specific item
function addSpecificItem(itemId, itemName, itemPrice, quantity) {
    // Check if item is already in the order
    let itemExists = false;
    document.querySelectorAll('.item-select').forEach(select => {
        if (select.value == itemId) {
            itemExists = true;
            const row = select.closest('tr');
            const quantityInput = row.querySelector('.item-quantity');
            quantityInput.value = parseInt(quantityInput.value) + quantity;
            calculateItemTotal(quantityInput);
        }
    });
    
    // If item doesn't exist, add a new row
    if (!itemExists) {
        const tbody = document.querySelector('#orderItemsTable tbody');
        const newRow = itemRowTemplate.cloneNode(true);
        newRow.id = 'itemRow' + itemCount;
        
        const select = newRow.querySelector('.item-select');
        for (let i = 0; i < select.options.length; i++) {
            if (select.options[i].value == itemId) {
                select.selectedIndex = i;
                break;
            }
        }
        
        newRow.querySelector('.item-quantity').value = quantity;
        newRow.querySelector('.item-price').value = itemPrice.toFixed(2);
        
        // Calculate total
        const total = quantity * itemPrice;
        newRow.querySelector('.item-total').value = total.toFixed(2);
        
        // Enable remove button
        newRow.querySelector('.btn-danger').disabled = false;
        
        // Add event listeners
        newRow.querySelector('.item-select').addEventListener('change', function() {
            updateItemPrice(this);
        });
        
        newRow.querySelector('.item-quantity').addEventListener('change', function() {
            calculateItemTotal(this);
        });
        
        newRow.querySelector('.item-price').addEventListener('change', function() {
            calculateItemTotal(this);
        });
        
        tbody.appendChild(newRow);
        itemCount++;
        
        // Enable the first row's remove button if we have more than one row
        if (tbody.children.length > 1) {
            document.querySelector('#itemRow0 .btn-danger').disabled = false;
        }
    }
    
    updateTotals();
}

// Function to add all low stock items
function addLowStockItems() {
    <?php 
    // Reset the result pointer
    if ($low_stock_result) {
        $low_stock_result->data_seek(0);
        while($item = $low_stock_result->fetch_assoc()): 
            $suggested_order = max(1, $item['reorder_level'] - $item['quantity']);
    ?>
        addSpecificItem(<?php echo $item['id']; ?>, '<?php echo $item['name']; ?>', <?php echo $item['unit_price']; ?>, <?php echo $suggested_order; ?>);
    <?php 
        endwhile;
    }
    ?>
}

// Form validation
document.getElementById('orderForm').addEventListener('submit', function(event) {
    // Check if at least one item is selected
    let valid = false;
    document.querySelectorAll('.item-select').forEach(select => {
        if (select.value) {
            valid = true;
        }
    });
    
    if (!valid) {
        event.preventDefault();
        alert('Please select at least one item for the order.');
    }
});

// Initialize the first row
updateTotals();
</script>

<?php include '../includes/footer.php'; ?>
