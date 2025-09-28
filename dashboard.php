<?php
$pageTitle = "Dashboard";
include 'header.php';
include 'config.php';

$view = $_GET['view'] ?? 'monthly';
// Handle revenue range filter
$range = $_GET['range'] ?? '6m';
switch($range) {
    case '12m': $limit = 12; break;
    case 'all': $limit = 1000; break;
    default: $limit = 6;
}

// Get totals
$totalClients = $conn->query("SELECT COUNT(*) as count FROM clients")->fetch(PDO::FETCH_ASSOC)['count'];
$totalProducts = $conn->query("SELECT COUNT(*) as count FROM products")->fetch(PDO::FETCH_ASSOC)['count'];
$totalInvoices = $conn->query("SELECT COUNT(*) as count FROM invoices")->fetch(PDO::FETCH_ASSOC)['count'];
$totalRevenueResult = $conn->query("SELECT SUM(total) as sum FROM invoices")->fetch(PDO::FETCH_ASSOC);
$totalRevenue = $totalRevenueResult['sum'] ?? 0;

// Recent invoices
$recentInvoices = $conn->query("
    SELECT i.*, c.name as client_name 
    FROM invoices i 
    JOIN clients c ON i.client_id = c.id 
    ORDER BY i.date DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Monthly revenue data for chart (MySQL syntax)
if ($view === 'daily') {
    // group by day
    $monthlyRevenue = $conn->query("
        SELECT strftime('%Y-%m-%d', date) as period, SUM(total) as revenue
        FROM invoices 
        GROUP BY period 
        ORDER BY period DESC 
        LIMIT 30
    ")->fetchAll(PDO::FETCH_ASSOC);
} else {
    // group by month
    $limit = ($range === '12m') ? 12 : (($range === 'all') ? 1000 : 6);
    $monthlyRevenue = $conn->query("
        SELECT strftime('%Y-%m', date) as period, SUM(total) as revenue
        FROM invoices 
        GROUP BY period 
        ORDER BY period DESC 
        LIMIT $limit
    ")->fetchAll(PDO::FETCH_ASSOC);
}

?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 mb-0">Dashboard Overview</h1>
        <p class="text-muted">Welcome to your invoice management system</p>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-5">
    <div class="col-md-3">
        <div class="card stat-card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h4 class="card-title"><?php echo $totalRevenue > 0 ? '$' . number_format($totalRevenue, 2) : '$0.00'; ?></h4>
                        <p class="card-text mb-0">Total Revenue</p>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="bi bi-currency-dollar display-6 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stat-card bg-success text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h4 class="card-title"><?php echo $totalInvoices; ?></h4>
                        <p class="card-text mb-0">Total Invoices</p>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="bi bi-receipt display-6 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stat-card bg-info text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h4 class="card-title"><?php echo $totalClients; ?></h4>
                        <p class="card-text mb-0">Total Clients</p>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="bi bi-people display-6 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stat-card bg-warning text-dark">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h4 class="card-title"><?php echo $totalProducts; ?></h4>
                        <p class="card-text mb-0">Total Products</p>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="bi bi-box-seam display-6 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-5">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Revenue Overview</h5>
                <form method="get" class="d-flex gap-2 mb-0">
                    <select name="view" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="monthly" <?= ($view=='monthly')?'selected':'' ?>>Monthly</option>
                        <option value="daily" <?= ($view=='daily')?'selected':'' ?>>Daily</option>
                    </select>
                    <?php if ($view === 'monthly'): ?>
                    <select name="range" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="6m" <?= ($range=='6m')?'selected':'' ?>>Last 6 Months</option>
                        <option value="12m" <?= ($range=='12m')?'selected':'' ?>>Last 12 Months</option>
                        <option value="all" <?= ($range=='all')?'selected':'' ?>>All Time</option>
                    </select>
                    <?php endif; ?>
                </form>
            </div>

            <div class="card-body">
                <canvas id="revenueChart" height="250"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="create_invoice.php" class="btn btn-primary btn-lg">
                        <i class="bi bi-plus-circle"></i> Create New Invoice
                    </a>
                    <a href="clients.php" class="btn btn-outline-primary">
                        <i class="bi bi-person-plus"></i> Add Client
                    </a>
                    <a href="products.php" class="btn btn-outline-primary">
                        <i class="bi bi-plus-square"></i> Add Product
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Invoices -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Recent Invoices</h5>
                <a href="invoices.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Client</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recentInvoices as $invoice): ?>
                            <tr>
                                <td>#<?php echo $invoice['id']; ?></td>
                                <td><?php echo htmlspecialchars($invoice['client_name']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($invoice['date'])); ?></td>
                                <td class="fw-bold">$<?php echo number_format($invoice['total'], 2); ?></td>
                                <td>
                                    <a href="generate_pdf.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-download"></i> PDF
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Inject PHP data into JS -->
<script>
window.revenueData = {
    labels: [<?php echo implode(',', array_map(function($item) use ($view) { 
        return "'" . ($view=='daily' 
            ? date('M j', strtotime($item['period'])) 
            : date('M Y', strtotime($item['period'].'-01'))
        ) . "'"; 
    }, array_reverse($monthlyRevenue))); ?>],
    datasets: [{
        label: '<?= ucfirst($view) ?> Revenue',
        data: [<?php echo implode(',', array_map(function($item) { return $item['revenue']; }, array_reverse($monthlyRevenue))); ?>],
        borderColor: '#0d6efd',
        borderWidth: 3,
        fill: false,
        tension: 0, // zigzag
        pointRadius: 5,
        pointBackgroundColor: '#0d6efd',
        pointBorderColor: '#fff',
        pointBorderWidth: 2
    }]
};
</script>


<script src="assets/js/chart.js"></script>
<?php include 'footer.php'; ?>
