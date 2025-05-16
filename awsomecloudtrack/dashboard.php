<?php
include 'includes/db_connect.php';
include 'includes/header.php';

// Get inventory count
$inventory_query = "SELECT COUNT(*) as total_items, SUM(quantity) as total_quantity FROM inventory_items";
$inventory_result = $conn->query($inventory_query);
$inventory_data = $inventory_result->fetch_assoc();

// Get low stock items
$low_stock_query = "SELECT COUNT(*) as low_stock FROM inventory_items WHERE quantity <= reorder_level";
$low_stock_result = $conn->query($low_stock_query);
$low_stock_data = $low_stock_result->fetch_assoc();

// Get total members
$members_query = "SELECT COUNT(*) as total_members FROM members";
$members_result = $conn->query($members_query);
$members_data = $members_result->fetch_assoc();

// Get financial summary
$finance_query = "SELECT 
                    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
                    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense
                  FROM transactions";
$finance_result = $conn->query($finance_query);
$finance_data = $finance_result->fetch_assoc();

// Get recent transactions
$recent_transactions_query = "SELECT * FROM transactions ORDER BY transaction_date DESC LIMIT 5";
$recent_transactions_result = $conn->query($recent_transactions_query);

// Get recent sales
$recent_sales_query = "SELECT s.*, m.name as member_name FROM sales s 
                      LEFT JOIN members m ON s.member_id = m.id 
                      ORDER BY s.sale_date DESC LIMIT 5";
$recent_sales_result = $conn->query($recent_sales_query);
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1 class="mb-0"><i class="bi bi-speedometer2 me-2"></i>Dashboard</h1>
        <p class="text-muted">Welcome back, <?php echo $_SESSION['username']; ?></p>
    </div>
    <div class="col-md-6 text-end">
        <button type="button" class="btn btn-outline-primary" id="refreshDashboard">
            <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
    </div>
</div>

<div class="row">
    <!-- Inventory Summary Card -->
    <div class="col-md-3 mb-4">
        <div class="card dashboard-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Inventory Items</h6>
                        <h2 class="mb-0"><?php echo $inventory_data['total_items'] ?? 0; ?></h2>
                    </div>
                    <div class="icon-box bg-primary text-white">
                        <i class="bi bi-box-seam"></i>
                    </div>
                </div>
                <p class="mt-2 mb-0">Total Quantity: <?php echo $inventory_data['total_quantity'] ?? 0; ?></p>
            </div>
            <div class="card-footer bg-white border-0">
                <a href="<?php echo url('inventory/index.php'); ?>" class="text-decoration-none text-primary">View Inventory <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
    </div>
    
    <!-- Low Stock Alert Card -->
    <div class="col-md-3 mb-4">
        <div class="card dashboard-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Low Stock Items</h6>
                        <h2 class="mb-0"><?php echo $low_stock_data['low_stock'] ?? 0; ?></h2>
                    </div>
                    <div class="icon-box bg-warning text-white">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                </div>
                <p class="mt-2 mb-0">Items below reorder level</p>
            </div>
            <div class="card-footer bg-white border-0">
                <a href="<?php echo url('inventory/low-stock.php'); ?>" class="text-decoration-none text-warning">View Low Stock <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
    </div>
    
    <!-- Members Card -->
    <div class="col-md-3 mb-4">
        <div class="card dashboard-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Total Members</h6>
                        <h2 class="mb-0"><?php echo $members_data['total_members'] ?? 0; ?></h2>
                    </div>
                    <div class="icon-box bg-success text-white">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
                <p class="mt-2 mb-0">Active organization members</p>
            </div>
            <div class="card-footer bg-white border-0">
                <a href="<?php echo url('members/index.php'); ?>" class="text-decoration-none text-success">View Members <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
    </div>
    
    <!-- Financial Summary Card -->
    <div class="col-md-3 mb-4">
        <div class="card dashboard-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Balance</h6>
                        <h2 class="mb-0">₱<?php echo number_format(($finance_data['total_income'] ?? 0) - ($finance_data['total_expense'] ?? 0), 2); ?></h2>
                    </div>
                    <div class="icon-box bg-info text-white">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                </div>
                <p class="mt-2 mb-0">
                    <span class="text-success">₱<?php echo number_format($finance_data['total_income'] ?? 0, 2); ?></span> | 
                    <span class="text-danger">₱<?php echo number_format($finance_data['total_expense'] ?? 0, 2); ?></span>
                </p>
            </div>
            <div class="card-footer bg-white border-0">
                <a href="<?php echo url('finances/index.php'); ?>" class="text-decoration-none text-info">View Finances <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Transactions -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Transactions</h5>
                <a href="<?php echo url('finances/index.php'); ?>" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Type</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_transactions_result && $recent_transactions_result->num_rows > 0): ?>
                                <?php while($transaction = $recent_transactions_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($transaction['transaction_date'])); ?></td>
                                        <td><?php echo $transaction['description']; ?></td>
                                        <td>
                                            <?php if($transaction['type'] == 'income'): ?>
                                                <span class="badge bg-success">Income</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Expense</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="<?php echo $transaction['type'] == 'income' ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo $transaction['type'] == 'income' ? '+' : '-'; ?>₱<?php echo number_format($transaction['amount'], 2); ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4">No transactions found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Sales -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Sales</h5>
                <a href="<?php echo url('sales/index.php'); ?>" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Member</th>
                                <th>Items</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_sales_result && $recent_sales_result->num_rows > 0): ?>
                                <?php while($sale = $recent_sales_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($sale['sale_date'])); ?></td>
                                        <td><?php echo $sale['member_name'] ?? 'Guest'; ?></td>
                                        <td><?php echo $sale['item_count']; ?></td>
                                        <td class="text-success">₱<?php echo number_format($sale['total_amount'], 2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4">No sales found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3 col-sm-6">
                        <a href="<?php echo url('sales/new-sale.php'); ?>" class="btn btn-primary d-block py-3">
                            <i class="bi bi-cart-plus me-2"></i> New Sale
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="<?php echo url('inventory/add-item.php'); ?>" class="btn btn-success d-block py-3">
                            <i class="bi bi-plus-circle me-2"></i> Add Item
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="<?php echo url('members/add-member.php'); ?>" class="btn btn-info d-block py-3 text-white">
                            <i class="bi bi-person-plus me-2"></i> Add Member
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="<?php echo url('procurement/new-order.php'); ?>" class="btn btn-warning d-block py-3 text-white">
                            <i class="bi bi-truck me-2"></i> New Order
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Refresh dashboard
document.getElementById('refreshDashboard')?.addEventListener('click', function() {
    location.reload();
});
</script>

<?php include 'includes/footer.php'; ?>
