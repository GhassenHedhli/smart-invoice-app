<?php
session_start();
include 'config.php';

if(!isset($_SESSION['client'])) {
    header("Location: client_login.php");
    exit;
}

$clientId = $_SESSION['client'];
$stmt = $conn->prepare("SELECT * FROM clients WHERE id = :id");
$stmt->execute([':id' => $clientId]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$client) {
    session_destroy();
    header("Location: client_login.php");
    exit;
}

// Get client's invoices
$invoices = $conn->prepare("
    SELECT i.* 
    FROM invoices i 
    WHERE i.client_id = :client_id 
    ORDER BY i.date DESC
");
$invoices->execute([':client_id' => $clientId]);
$invoices = $invoices->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - InvoicePro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .sidebar { background: #f8f9fa; min-height: 100vh; }
        .invoice-card { transition: transform 0.2s; }
        .invoice-card:hover { transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 sidebar d-md-block bg-light">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4 class="text-primary">InvoicePro</h4>
                        <small class="text-muted">Client Portal</small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="#">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="client_logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </li>
                    </ul>
                    
                    <div class="mt-4 p-3 bg-white rounded">
                        <h6>Account Info</h6>
                        <small class="text-muted">Client: <?php echo htmlspecialchars($client['name']); ?></small><br>
                        <small class="text-muted">Email: <?php echo htmlspecialchars($client['email']); ?></small>
                    </div>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Your Invoices</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <span class="text-muted me-3">Welcome, <?php echo htmlspecialchars($client['name']); ?></span>
                    </div>
                </div>

                <?php if(empty($invoices)): ?>
                <div class="alert alert-info">
                    <h5>No Invoices Found</h5>
                    <p>You don't have any invoices yet. Invoices will appear here once they are created by the administrator.</p>
                </div>
                <?php else: ?>
                <div class="row">
                    <?php foreach($invoices as $invoice): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card invoice-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="card-title">Invoice #<?php echo $invoice['id']; ?></h5>
                                    <span class="badge bg-<?php echo $invoice['status'] == 'paid' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($invoice['status']); ?>
                                    </span>
                                </div>
                                
                                <p class="card-text">
                                    <small class="text-muted">Date: <?php echo date('M j, Y', strtotime($invoice['date'])); ?></small><br>
                                    <strong>Total: $<?php echo number_format($invoice['total'], 2); ?></strong>
                                </p>
                                
                                <?php if($invoice['sent_via_email']): ?>
                                <small class="text-success"><i class="bi bi-check-circle"></i> Sent via email</small>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer bg-transparent">
                                <a href="generate_pdf.php?id=<?php echo $invoice['id']; ?>&client_access=1" 
                                   class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="bi bi-download"></i> Download PDF
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</body>
</html>