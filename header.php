<?php
session_start();
if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit;
}

include 'config.php';

// Get current user role and permissions
$stmt = $conn->prepare("
    SELECT u.*, r.name as role_name, r.permissions 
    FROM users u 
    LEFT JOIN roles r ON u.role_id = r.id 
    WHERE u.id = :user_id
");
$stmt->execute([':user_id' => $_SESSION['user']]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$currentUser){
    session_destroy();
    header("Location: login.php");
    exit;
}

// Check permission function
function hasPermission($permission) {
    global $currentUser;
    
    if($currentUser['role_name'] == 'Administrator') {
        return true;
    }
    
    $permissions = explode(',', $currentUser['permissions']);
    return in_array($permission, $permissions) || in_array('all', $permissions);
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InvoicePro - <?php echo $pageTitle ?? 'Dashboard'; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/themes.css">
</head>
<body>
    <!-- Theme Toggle -->
    <div class="theme-toggle position-fixed top-0 end-0 m-3">
        <button class="btn btn-outline-secondary btn-sm" id="themeToggle">
            <i class="bi bi-moon-fill"></i>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="bi bi-receipt"></i> InvoicePro
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    
                    <?php if(hasPermission('clients')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="clients.php">
                            <i class="bi bi-people"></i> Clients
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if(hasPermission('products')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">
                            <i class="bi bi-box-seam"></i> Products
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if(hasPermission('invoices')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="invoices.php">
                            <i class="bi bi-file-earmark-text"></i> Invoices
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="create_invoice.php">
                            <i class="bi bi-plus-circle"></i> Create Invoice
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if(hasPermission('users')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_users.php">
                            <i class="bi bi-person-gear"></i> Users
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($currentUser['username']); ?>
                            (<?php echo $currentUser['role_name']; ?>)
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="client_login.php" target="_blank">
                                <i class="bi bi-box-arrow-up-right"></i> Client Portal
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container-fluid py-4">