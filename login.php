<?php
session_start();
include 'config.php';

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Fetch user by username
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Secure session
        session_regenerate_id(true);
        $_SESSION['user'] = $user['id'];

        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - InvoicePro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
    </style>
</head>
<body class="d-flex align-items-center">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow-lg">
                <div class="card-header text-center bg-primary text-white py-3">
                    <h4><i class="bi bi-receipt"></i> InvoicePro</h4>
                    <p class="mb-0">Multi-User Login</p>
                </div>
                <div class="card-body p-4">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" name="username" class="form-control" required autofocus>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                        </div>
                        <button type="submit" name="login" class="btn btn-primary w-100 py-2">
                            <i class="bi bi-box-arrow-in-right"></i> Login to System
                        </button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <small class="text-muted">
                            <a href="client_login.php" class="text-decoration-none">Client Portal Login</a>
                        </small>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-3">
                <small class="text-white">
                    Default admin: admin / admin123
                </small>
            </div>
        </div>
    </div>
</div>
</body>
</html>