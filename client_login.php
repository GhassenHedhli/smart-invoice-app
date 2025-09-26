<?php
session_start();
include 'config.php';

if(isset($_SESSION['client'])) {
    header("Location: client_dashboard.php");
    exit;
}

if(isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT * FROM clients WHERE login_email = :email AND login_enabled = 1");
    $stmt->execute([':email' => $email]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($client && password_verify($password, $client['login_password'])) {
        $_SESSION['client'] = $client['id'];
        header("Location: client_dashboard.php");
        exit;
    } else {
        $error = "Invalid login credentials or client portal access is disabled";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Login - InvoicePro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .login-card { border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card login-card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <h2 class="fw-bold text-primary">Client Portal</h2>
                            <p class="text-muted">Access your invoices and billing information</p>
                        </div>
                        
                        <?php if(isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="email" class="form-control" placeholder="your@email.com" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" name="password" class="form-control" placeholder="Your password" required>
                                </div>
                            </div>
                            
                            <button type="submit" name="login" class="btn btn-primary w-100 py-2 mb-3">
                                <i class="bi bi-box-arrow-in-right"></i> Login to Client Portal
                            </button>
                            
                            <div class="text-center">
                                <small class="text-muted">
                                    Need help? Contact our support team
                                </small>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>