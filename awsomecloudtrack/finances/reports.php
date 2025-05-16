<?php
include '../includes/db_connect.php';
include '../includes/header.php';

// Set default filter values
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // First day of current month
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t'); // Last day of current month
$type = isset($_GET['type']) ? $_GET['type'] : 'all';
$category = isset($_GET['category']) ? $_GET['category'] : '';

// Build query based on filters
$query = "SELECT * FROM transactions WHERE transaction_date BETWEEN ? AND ?";
$params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
$types = "ss";

if ($type != 'all') {
    $query .= " AND type = ?";
    $params[] = $type;
    $types .= "s";
}

if (!empty($category)) {
    $query .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}

$query .= " ORDER BY transaction_date DESC";

// Prepare and execute query
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Get categories for filter
$categories_query = "SELECT DISTINCT category FROM transactions ORDER BY category";
$categories_result = $conn->query($categories_query);

// Calculate summary statistics
$total_income = 0;
$total_expense = 0;
$transactions_by_category = [];
$transactions_by_date = [];

// Clone result to use for calculations
$calc_result = $result->fetch_all(MYSQLI_ASSOC);
foreach ($calc_result as $transaction) {
    // Calculate totals
    if ($transaction['type'] == 'income') {
        $total_income += $transaction['amount'];
    } else {
        $total_expense += $transaction['amount'];
    }
    
    // Group by category
    $category = $transaction['category'];
    if (!isset($transactions_by_category[$category])) {
        $transactions_by_category[$category] = [
            'income' => 0,
            'expense' => 0
        ];
    }
    $transactions_by_category[$category][$transaction['type']] += $transaction['amount'];
    
    // Group by date (month)
    $date = date('Y-m', strtotime($transaction['transaction_date']));
    if (!isset($transactions_by_date[$date])) {
        $transactions_by_date[$date] = [
            'income' => 0,
            'expense' => 0
        ];
    }
    $transactions_by_date[$date][$transaction['type']] += $transaction['amount'];
}

// Sort date array by key (chronologically)
ksort($transactions_by_date);
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1><i class="bi bi-file-earmark-text me-2"></i>Financial Reports</h1>
        <p class="text-muted">Analyze financial transactions and trends</p>
    </div>
    <div class="col-md-6 text-end">
        <button type="button" class="btn btn-outline-secondary btn-print">
            <i class="bi bi-printer"></i> Print Report
        </button>
        <a href="<?php echo url('finances/index.php'); ?>" class="btn btn-outline-primary ms-2">
            <i class="bi bi-arrow-left"></i> Back to Finances
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
                        <label for="type" class="form-label">Transaction Type</label>
                        <select class="form-select" id="type" name="type">
                            <option value="all" <?php echo $type == 'all' ? 'selected' : ''; ?>>All Types</option>
                            <option value="income" <?php echo $type == 'income' ? 'selected' : ''; ?>>Income Only</option>
                            <option value="expense" <?php echo $type == 'expense' ? 'selected' : ''; ?>>Expense Only</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-select" id="category" name="category">
                            <option value="">All Categories</option>
                            <?php if ($categories_result && $categories_result->num_rows > 0): ?>
                                <?php while($cat = $categories_result->fetch_assoc()): ?>
                                    <option value="<?php echo $cat['category']; ?>" <?php echo $category == $cat['category'] ? 'selected' : ''; ?>>
                                        <?php echo $cat['category']; ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-filter"></i> Apply Filters
                        </button>
                        <a href="<?php echo url('finances/reports.php'); ?>" class="btn btn-outline-secondary ms-2">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <!-- Financial Summary Cards -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Total Income</h6>
                        <h2 class="mb-0 text-success">₱<?php echo number_format($total_income, 2); ?></h2>
                    </div>
                    <div class="bg-success text-white p-3 rounded">
                        <i class="bi bi-graph-up-arrow fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Total Expenses</h6>
                        <h2 class="mb-0 text-danger">₱<?php echo number_format($total_expense, 2); ?></h2>
                    </div>
                    <div class="bg-danger text-white p-3 rounded">
                        <i class="bi bi-graph-down-arrow fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Net Balance</h6>
                        <h2 class="mb-0 <?php echo ($total_income - $total_expense) >= 0 ? 'text-primary' : 'text-danger'; ?>">
                            ₱<?php echo number_format($total_income - $total_expense, 2); ?>
                        </h2>
                    </div>
                    <div class="bg-primary text-white p-3 rounded">
                        <i class="bi bi-cash-stack fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Transactions by Category</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th class="text-end">Income</th>
                                <th class="text-end">Expense</th>
                                <th class="text-end">Net</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($transactions_by_category)): ?>
                                <?php foreach($transactions_by_category as $cat => $amounts): ?>
                                    <tr>
                                        <td><?php echo $cat; ?></td>
                                        <td class="text-end text-success">₱<?php echo number_format($amounts['income'], 2); ?></td>
                                        <td class="text-end text-danger">₱<?php echo number_format($amounts['expense'], 2); ?></td>
                                        <td class="text-end <?php echo ($amounts['income'] - $amounts['expense']) >= 0 ? 'text-primary' : 'text-danger'; ?>">
                                            ₱<?php echo number_format($amounts['income'] - $amounts['expense'], 2); ?>
                                        </td>
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
                <h5 class="mb-0">Monthly Summary</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th class="text-end">Income</th>
                                <th class="text-end">Expense</th>
                                <th class="text-end">Net</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($transactions_by_date)): ?>
                                <?php foreach($transactions_by_date as $date => $amounts): ?>
                                    <tr>
                                        <td><?php echo date('F Y', strtotime($date . '-01')); ?></td>
                                        <td class="text-end text-success">₱<?php echo number_format($amounts['income'], 2); ?></td>
                                        <td class="text-end text-danger">₱<?php echo number_format($amounts['expense'], 2); ?></td>
                                        <td class="text-end <?php echo ($amounts['income'] - $amounts['expense']) >= 0 ? 'text-primary' : 'text-danger'; ?>">
                                            ₱<?php echo number_format($amounts['income'] - $amounts['expense'], 2); ?>
                                        </td>
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
</div>

<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Transaction Details</h5>
        <div>
            <button class="btn btn-sm btn-outline-primary" id="exportCSV">
                <i class="bi bi-file-earmark-spreadsheet"></i> Export to CSV
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover" id="transactionsTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Type</th>
                        <th class="text-end">Amount</th>
                        <th>Reference</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($transaction = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($transaction['transaction_date'])); ?></td>
                                <td><?php echo $transaction['description']; ?></td>
                                <td><?php echo $transaction['category']; ?></td>
                                <td>
                                    <?php if($transaction['type'] == 'income'): ?>
                                        <span class="badge bg-success">Income</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Expense</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end <?php echo $transaction['type'] == 'income' ? 'text-success' : 'text-danger'; ?>">
                                    ₱<?php echo number_format($transaction['amount'], 2); ?>
                                </td>
                                <td>
                                    <?php if($transaction['reference_id'] && $transaction['reference_type']): ?>
                                        <?php echo ucfirst($transaction['reference_type']); ?> #<?php echo $transaction['reference_id']; ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">No transactions found for the selected filters</td>
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
    const table = document.getElementById('transactionsTable');
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    for (let i = 0; i < rows.length; i++) {
        const row = [], cols = rows[i].querySelectorAll('td, th');
        
        for (let j = 0; j < cols.length; j++) {
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
    downloadLink.download = 'financial_report_<?php echo date('Y-m-d'); ?>.csv';
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    
    // Add to document and trigger download
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
});
</script>

<?php include '../includes/footer.php'; ?>
