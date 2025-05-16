<?php
include '../includes/db_connect.php';
include '../includes/header.php';

// Set default filter values
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // First day of current month
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t'); // Last day of current month
$member_id = isset($_GET['member_id']) ? $_GET['member_id'] : '';
$payment_method = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';

// Build query based on filters
$query = "SELECT s.*, m.name as member_name 
          FROM sales s 
          LEFT JOIN members m ON s.member_id = m.id 
          WHERE s.sale_date BETWEEN ? AND ?";
$params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
$types = "ss";

if (!empty($member_id)) {
    $query .= " AND s.member_id = ?";
    $params[] = $member_id;
    $types .= "i";
}

if (!empty($payment_method)) {
    $query .= " AND s.payment_method = ?";
    $params[] = $payment_method;
    $types .= "s";
}

$query .= " ORDER BY s.sale_date DESC";

// Prepare and execute query
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Get members for filter
$members_query = "SELECT id, name FROM members ORDER BY name";
$members_result = $conn->query($members_query);

// Get payment methods for filter
$payment_methods_query = "SELECT DISTINCT payment_method FROM sales ORDER BY payment_method";
$payment_methods_result = $conn->query($payment_methods_query);

// Calculate summary statistics
$total_sales = 0;
$total_items = 0;
$sales_by_payment = [];
$sales_by_date = [];
$top_items = [];

// Clone result to use for calculations
$calc_result = $result->fetch_all(MYSQLI_ASSOC);
foreach ($calc_result as $sale) {
    // Calculate totals
    $total_sales += $sale['total_amount'];
    $total_items += $sale['item_count'];
    
    // Group by payment method
    $method = $sale['payment_method'];
    if (!isset($sales_by_payment[$method])) {
        $sales_by_payment[$method] = [
            'count' => 0,
            'amount' => 0
        ];
    }
    $sales_by_payment[$method]['count']++;
    $sales_by_payment[$method]['amount'] += $sale['total_amount'];
    
    // Group by date (day)
    $date = date('Y-m-d', strtotime($sale['sale_date']));
    if (!isset($sales_by_date[$date])) {
        $sales_by_date[$date] = [
            'count' => 0,
            'amount' => 0
        ];
    }
    $sales_by_date[$date]['count']++;
    $sales_by_date[$date]['amount'] += $sale['total_amount'];
}

// Get top selling items
$top_items_query = "SELECT i.name, i.category, SUM(si.quantity) as total_quantity, SUM(si.total_price) as total_amount
                   FROM sale_items si
                   JOIN inventory_items i ON si.item_id = i.id
                   JOIN sales s ON si.sale_id = s.id
                   WHERE s.sale_date BETWEEN ? AND ?
                   GROUP BY si.item_id
                   ORDER BY total_quantity DESC
                   LIMIT 10";
$stmt = $conn->prepare($top_items_query);
$stmt->bind_param("ss", $params[0], $params[1]);
$stmt->execute();
$top_items_result = $stmt->get_result();

// Sort date array by key (chronologically)
ksort($sales_by_date);
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1><i class="bi bi-file-earmark-text me-2"></i>Sales Reports</h1>
        <p class="text-muted">Analyze sales data and trends</p>
    </div>
    <div class="col-md-6 text-end">
        <button type="button" class="btn btn-outline-secondary btn-print">
            <i class="bi bi-printer"></i> Print Report
        </button>
        <a href="<?php echo url('sales/index.php'); ?>" class="btn btn-outline-primary ms-2">
            <i class="bi bi-arrow-left"></i> Back to Sales
        </a>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Filter Options</h5>
            </div>
            <div class="card-body">
                <form method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="row g-3">
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="member_id" class="form-label">Member</label>
                        <select class="form-select" id="member_id" name="member_id">
                            <option value="">All Members</option>
                            <?php if ($members_result && $members_result->num_rows > 0): ?>
                                <?php while($member = $members_result->fetch_assoc()): ?>
                                    <option value="<?php echo $member['id']; ?>" <?php echo $member_id == $member['id'] ? 'selected' : ''; ?>>
                                        <?php echo $member['name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="payment_method" class="form-label">Payment Method</label>
                        <select class="form-select" id="payment_method" name="payment_method">
                            <option value="">All Methods</option>
                            <?php if ($payment_methods_result && $payment_methods_result->num_rows > 0): ?>
                                <?php while($method = $payment_methods_result->fetch_assoc()): ?>
                                    <option value="<?php echo $method['payment_method']; ?>" <?php echo $payment_method == $method['payment_method'] ? 'selected' : ''; ?>>
                                        <?php echo $method['payment_method']; ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-filter"></i> Apply Filters
                        </button>
                        <a href="<?php echo url('sales/reports.php'); ?>" class="btn btn-outline-secondary ms-2">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <!-- Sales Summary Cards -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Total Sales</h6>
                        <h2 class="mb-0 text-primary">₱<?php echo number_format($total_sales, 2); ?></h2>
                    </div>
                    <div class="bg-primary text-white p-3 rounded">
                        <i class="bi bi-cash-stack fs-1"></i>
                    </div>
                </div>
                <p class="mt-2 mb-0">From <?php echo count($calc_result); ?> transactions</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Items Sold</h6>
                        <h2 class="mb-0 text-success"><?php echo $total_items; ?></h2>
                    </div>
                    <div class="bg-success text-white p-3 rounded">
                        <i class="bi bi-box-seam fs-1"></i>
                    </div>
                </div>
                <p class="mt-2 mb-0">Average <?php echo count($calc_result) > 0 ? round($total_items / count($calc_result), 1) : 0; ?> items per sale</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Average Sale</h6>
                        <h2 class="mb-0 text-info">₱<?php echo count($calc_result) > 0 ? number_format($total_sales / count($calc_result), 2) : '0.00'; ?></h2>
                    </div>
                    <div class="bg-info text-white p-3 rounded">
                        <i class="bi bi-graph-up fs-1"></i>
                    </div>
                </div>
                <p class="mt-2 mb-0">Per transaction</p>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Sales by Payment Method</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Payment Method</th>
                                <th class="text-center">Count</th>
                                <th class="text-end">Amount</th>
                                <th class="text-end">Average</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($sales_by_payment)): ?>
                                <?php foreach($sales_by_payment as $method => $data): ?>
                                    <tr>
                                        <td><?php echo $method; ?></td>
                                        <td class="text-center"><?php echo $data['count']; ?></td>
                                        <td class="text-end">₱<?php echo number_format($data['amount'], 2); ?></td>
                                        <td class="text-end">₱<?php echo number_format($data['amount'] / $data['count'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No data available</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Top Selling Items</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Category</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($top_items_result && $top_items_result->num_rows > 0): ?>
                                <?php while($item = $top_items_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $item['name']; ?></td>
                                        <td><?php echo $item['category']; ?></td>
                                        <td class="text-center"><?php echo $item['total_quantity']; ?></td>
                                        <td class="text-end">₱<?php echo number_format($item['total_amount'], 2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No data available</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Sales Details</h5>
        <div>
            <button class="btn btn-sm btn-outline-primary" id="exportCSV">
                <i class="bi bi-file-earmark-spreadsheet"></i> Export to CSV
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover" id="salesTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>ID</th>
                        <th>Member</th>
                        <th>Items</th>
                        <th>Payment Method</th>
                        <th class="text-end">Amount</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($sale = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($sale['sale_date'])); ?></td>
                                <td>#<?php echo $sale['id']; ?></td>
                                <td><?php echo $sale['member_name'] ?? 'Guest'; ?></td>
                                <td><?php echo $sale['item_count']; ?></td>
                                <td><?php echo $sale['payment_method']; ?></td>
                                <td class="text-end">₱<?php echo number_format($sale['total_amount'], 2); ?></td>
                                <td>
                                    <a href="<?php echo url('sales/view-sale.php?id=' . $sale['id']); ?>" class="btn btn-sm btn-info">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">No sales found for the selected filters</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Print functionality
document.querySelector('.btn-print')?.addEventListener('click', function() {
    window.print();
});

// Export to CSV
document.getElementById('exportCSV')?.addEventListener('click', function() {
    const table = document.getElementById('salesTable');
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    for (let i = 0; i < rows.length; i++) {
        const row = [], cols = rows[i].querySelectorAll('td, th');
        
        for (let j = 0; j < cols.length - 1; j++) { // Skip the Actions column
            // Get the text content and clean it
            let data = cols[j].textContent.replace(/(\r\n|\n|\r)/gm, '').trim();
            // Escape double quotes
            data = data.replace(/"/g, '""');
            // Add quotes around the data
            row.push('"' + data + '"');
        }
        csv.push(row.join(','));
    }
    
    // Create CSV file
    const csvFile = new Blob([csv.join('\n')], {type: 'text/csv'});
    
    // Create download link
    const downloadLink = document.createElement('a');
    downloadLink.download = 'sales_report_<?php echo date('Y-m-d'); ?>.csv';
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    
    // Add to document and trigger download
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
});
</script>

<?php include '../includes/footer.php'; ?>
