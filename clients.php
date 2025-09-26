<?php
// clients.php - FIXED VERSION
$pageTitle = "Clients";
include 'header.php';
include 'config.php';

// Delete client functionality
if(isset($_GET['delete'])){
    $client_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM clients WHERE id = :id");
    $stmt->execute([':id' => $client_id]);
    header("Location: clients.php?success=Client deleted successfully");
    exit;
}

// Edit client functionality
$editClient = null;
if(isset($_GET['edit'])){
    $client_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM clients WHERE id = :id");
    $stmt->execute([':id' => $client_id]);
    $editClient = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Update client
if(isset($_POST['update_client'])){
    $client_id = intval($_POST['client_id']);
    $loginEnabled = isset($_POST['login_enabled']) ? 1 : 0;
    
    $stmt = $conn->prepare("UPDATE clients SET name = :name, email = :email, phone = :phone, address = :address, login_enabled = :login_enabled, login_email = :login_email WHERE id = :id");
    $stmt->execute([
        ':name' => $_POST['name'],
        ':email' => $_POST['email'],
        ':phone' => $_POST['phone'],
        ':address' => $_POST['address'] ?? '',
        ':login_enabled' => $loginEnabled,
        ':login_email' => $_POST['login_email'] ?? $_POST['email'],
        ':id' => $client_id
    ]);
    
    header("Location: clients.php?success=Client updated successfully");
    exit;
}

// Add new client
if(isset($_POST['add_client'])){
    $loginEnabled = isset($_POST['login_enabled']) ? 1 : 0;
    $loginPassword = '';
    
    if($loginEnabled) {
        $loginPassword = bin2hex(random_bytes(8));
        $hashedPassword = password_hash($loginPassword, PASSWORD_DEFAULT);
    }
    
    $stmt = $conn->prepare("INSERT INTO clients (name, email, phone, address, login_enabled, login_email, login_password) VALUES (:name, :email, :phone, :address, :login_enabled, :login_email, :login_password)");
    $stmt->execute([
        ':name' => $_POST['name'],
        ':email' => $_POST['email'],
        ':phone' => $_POST['phone'],
        ':address' => $_POST['address'] ?? '',
        ':login_enabled' => $loginEnabled,
        ':login_email' => $_POST['login_email'] ?? $_POST['email'],
        ':login_password' => $loginEnabled ? $hashedPassword : ''
    ]);
    
    header("Location: clients.php?success=Client added successfully");
    exit;
}

// Enable/disable client login
if(isset($_GET['toggle_login'])) {
    $clientId = intval($_GET['toggle_login']);
    
    $stmt = $conn->prepare("SELECT login_enabled, login_password FROM clients WHERE id = :id");
    $stmt->execute([':id' => $clientId]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($client) {
        $newStatus = $client['login_enabled'] ? 0 : 1;
        $newPassword = $client['login_password'];
        
        if($newStatus == 1 && empty($client['login_password'])) {
            $newPassword = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
        }
        
        $stmt = $conn->prepare("UPDATE clients SET login_enabled = :status, login_password = :password WHERE id = :id");
        $stmt->execute([
            ':status' => $newStatus,
            ':password' => $newPassword,
            ':id' => $clientId
        ]);
    }
    
    header("Location: invoices.php?success=Portal client enabled");
    exit;
}

// Fetch clients
$clients = $conn->query("SELECT * FROM clients")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Clients Management</h4>
                <a href="dashboard.php" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            <div class="card-body">
                <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
                <?php endif; ?>

                <!-- Add/Edit Client Form -->
                <div class="row">
                    <div class="col-md-6">
                        <h5><?php echo $editClient ? 'Edit Client' : 'Add New Client'; ?></h5>
                        <form method="POST" class="mb-4">
                            <?php if($editClient): ?>
                                <input type="hidden" name="client_id" value="<?php echo $editClient['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label class="form-label">Client Name</label>
                                <input type="text" name="name" class="form-control" placeholder="Client Name" 
                                       value="<?php echo $editClient ? htmlspecialchars($editClient['name']) : ''; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" placeholder="Email"
                                       value="<?php echo $editClient ? htmlspecialchars($editClient['email']) : ''; ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" placeholder="Phone"
                                       value="<?php echo $editClient ? htmlspecialchars($editClient['phone']) : ''; ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" placeholder="Address" rows="2"><?php echo $editClient ? htmlspecialchars($editClient['address']) : ''; ?></textarea>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" name="login_enabled" class="form-check-input" id="loginEnabled" 
                                    <?php echo ($editClient && $editClient['login_enabled']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="loginEnabled">
                                    Enable Client Portal Access
                                </label>
                            </div>
                            <div class="mb-3" id="loginEmailField" style="display: <?php echo ($editClient && $editClient['login_enabled']) ? 'block' : 'none'; ?>;">
                                <label class="form-label">Portal Login Email</label>
                                <input type="email" name="login_email" class="form-control" placeholder="Login Email"
                                       value="<?php echo $editClient ? htmlspecialchars($editClient['login_email'] ?? '') : ''; ?>">
                                <small class="text-muted">If different from main email</small>
                            </div>
                            
                            <?php if($editClient): ?>
                                <button type="submit" name="update_client" class="btn btn-primary">
                                    <i class="bi bi-check"></i> Update Client
                                </button>
                                <a href="clients.php" class="btn btn-secondary">Cancel</a>
                            <?php else: ?>
                                <button type="submit" name="add_client" class="btn btn-primary">
                                    <i class="bi bi-person-plus"></i> Add Client
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Clients List -->
                <h5>Client List</h5>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Portal Access</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($clients as $client): ?>
                            <tr>
                                <td><?php echo $client['id']; ?></td>
                                <td><?php echo htmlspecialchars($client['name']); ?></td>
                                <td><?php echo htmlspecialchars($client['email']); ?></td>
                                <td><?php echo htmlspecialchars($client['phone']); ?></td>
                                <td>
                                    <?php if(isset($client['login_enabled'])): ?>
                                    <span class="badge bg-<?php echo $client['login_enabled'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $client['login_enabled'] ? 'Enabled' : 'Disabled'; ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <?php if(isset($client['login_enabled'])): ?>
                                        <a href="clients.php?toggle_login=<?php echo $client['id']; ?>" 
                                           class="btn btn-<?php echo $client['login_enabled'] ? 'warning' : 'success'; ?>">
                                            <i class="bi bi-<?php echo $client['login_enabled'] ? 'lock' : 'unlock'; ?>"></i>
                                            <?php echo $client['login_enabled'] ? 'Disable' : 'Enable'; ?>
                                        </a>
                                        <?php endif; ?>
                                        <a href="clients.php?edit=<?php echo $client['id']; ?>" class="btn btn-outline-primary">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <a href="clients.php?delete=<?php echo $client['id']; ?>" 
                                           class="btn btn-outline-danger" 
                                           onclick="return confirm('Are you sure you want to delete this client?')">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                    </div>
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

<script>
// Show/hide login email field based on checkbox
document.getElementById('loginEnabled').addEventListener('change', function() {
    document.getElementById('loginEmailField').style.display = this.checked ? 'block' : 'none';
});
</script>

<?php include 'footer.php'; ?>