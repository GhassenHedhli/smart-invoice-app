<?php 
$pageTitle = "Clients";

include_once  'auth.php';   // this loads session, $currentUser, and hasPermission()
include_once  'config.php';

// -------------------- DELETE CLIENT --------------------
if(isset($_GET['delete'])){
    if(!hasPermission('client_delete')){
        header("Location: clients.php?error=Not authorized to delete clients");
        exit;
    }

    $client_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM clients WHERE id = :id");
    $stmt->execute([':id' => $client_id]);
    header("Location: clients.php?success=Client deleted successfully");
    exit;
}

// -------------------- EDIT CLIENT --------------------
$editClient = null;
if(isset($_GET['edit'])){
    if(!hasPermission('client_edit')){
        header("Location: clients.php?error=Not authorized to edit clients");
        exit;
    }

    $client_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM clients WHERE id = :id");
    $stmt->execute([':id' => $client_id]);
    $editClient = $stmt->fetch(PDO::FETCH_ASSOC);
}

// -------------------- UPDATE CLIENT --------------------
if(isset($_POST['update_client'])){
    if(!hasPermission('client_edit')){
        header("Location: clients.php?error=Not authorized to update clients");
        exit;
    }

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

// -------------------- ADD CLIENT --------------------
if(isset($_POST['add_client'])){
    if(!hasPermission('client_add')){
        header("Location: clients.php?error=Not authorized to add clients");
        exit;
    }

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

// -------------------- ENABLE / DISABLE CLIENT --------------------
if(isset($_GET['toggle_login'])) {
    if(!hasPermission('client_toggle')){
        header("Location: clients.php?error=Not authorized to toggle client access");
        exit;
    }

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
    
    header("Location: clients.php?success=Portal client status updated");
    exit;
}

// -------------------- FETCH CLIENTS --------------------
$clients = $conn->query("SELECT * FROM clients")->fetchAll(PDO::FETCH_ASSOC);
include 'header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Clients Management</h4>
                <div>
                    <?php if(hasPermission('client_export')): ?>
                    <div class="btn-group me-2">
                        <a href="export.php?export=clients&format=csv" class="btn btn-success btn-sm">
                            <i class="bi bi-download"></i> Export CSV
                        </a>
                        <a href="export.php?export=clients&format=xlsx" class="btn btn-primary btn-sm">
                            <i class="bi bi-download"></i> Export XLSX
                        </a>
                    </div>
                    <?php endif; ?>
                    <a href="dashboard.php" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
                <?php elseif(isset($_GET['error'])): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
                <?php endif; ?>

                <!-- Add/Edit Client Form -->
                <?php if(hasPermission('client_add') || hasPermission('client_edit')): ?>
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
                            
                            <?php if($editClient && hasPermission('client_edit')): ?>
                                <button type="submit" name="update_client" class="btn btn-primary">
                                    <i class="bi bi-check"></i> Update Client
                                </button>
                                <a href="clients.php" class="btn btn-secondary">Cancel</a>
                            <?php elseif(!$editClient && hasPermission('client_add')): ?>
                                <button type="submit" name="add_client" class="btn btn-primary">
                                    <i class="bi bi-person-plus"></i> Add Client
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

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
                                        <?php if(hasPermission('client_toggle')): ?>
                                            <?php if(isset($client['login_enabled'])): ?>
                                            <a href="clients.php?toggle_login=<?php echo $client['id']; ?>" 
                                               class="btn btn-<?php echo $client['login_enabled'] ? 'warning' : 'success'; ?>">
                                                <i class="bi bi-<?php echo $client['login_enabled'] ? 'lock' : 'unlock'; ?>"></i>
                                                <?php echo $client['login_enabled'] ? 'Disable' : 'Enable'; ?>
                                            </a>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <?php if(hasPermission('client_edit')): ?>
                                            <a href="clients.php?edit=<?php echo $client['id']; ?>" class="btn btn-outline-primary">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                        <?php endif; ?>

                                        <?php if(hasPermission('client_delete')): ?>
                                            <button class="btn btn-outline-danger btn-delete" data-id="<?php echo $client['id']; ?>">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>

                                        <?php endif; ?>
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
document.getElementById('loginEnabled')?.addEventListener('change', function() {
    document.getElementById('loginEmailField').style.display = this.checked ? 'block' : 'none';
});
</script>
<!-- SweetAlert2 CSS & JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.querySelectorAll('.btn-delete').forEach(button => {
    button.addEventListener('click', function() {
        const clientId = this.getAttribute('data-id');
        Swal.fire({
            title: 'Are you sure?',
            text: "This client will be permanently deleted!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'clients.php?delete=' + clientId;
            }
        });
    });
});
</script>

<?php include 'footer.php'; ?>
```
