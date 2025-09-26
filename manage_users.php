<?php
// manage_users.php - FIXED VERSION

// Process form submissions first
if(isset($_POST['add_user'])) {
    include 'config.php';
    
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role_id = intval($_POST['role_id']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (username, password, email, role_id) VALUES (:username, :password, :email, :role_id)");
    $stmt->execute([
        ':username' => $username,
        ':password' => $password,
        ':email' => $email,
        ':role_id' => $role_id
    ]);
    
    header("Location: manage_users.php?success=User added successfully");
    exit;
}

// Delete user
if(isset($_GET['delete'])){
    include 'config.php';
    $user_id = intval($_GET['delete']);
    
    // Prevent deleting current user
    if($user_id != $_SESSION['user']) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute([':id' => $user_id]);
    }
    
    header("Location: manage_users.php?success=User deleted successfully");
    exit;
}

// Edit user functionality
$editUser = null;
if(isset($_GET['edit'])){
    include 'config.php';
    $user_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute([':id' => $user_id]);
    $editUser = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Update user
if(isset($_POST['update_user'])){
    include 'config.php';
    $user_id = intval($_POST['user_id']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role_id = intval($_POST['role_id']);
    
    $stmt = $conn->prepare("UPDATE users SET username = :username, email = :email, role_id = :role_id WHERE id = :id");
    $stmt->execute([
        ':username' => $username,
        ':email' => $email,
        ':role_id' => $role_id,
        ':id' => $user_id
    ]);
    
    header("Location: manage_users.php?success=User updated successfully");
    exit;
}

// Now include the header and display the page
$pageTitle = "User Management";
include 'header.php';
include 'config.php';

// Check if user has permission
if(!hasPermission('users')) {
    echo "<div class='alert alert-danger'>You don't have permission to access this page.</div>";
    include 'footer.php';
    exit;
}

// Fetch users and roles
$users = $conn->query("SELECT u.*, r.name as role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id")->fetchAll(PDO::FETCH_ASSOC);
$roles = $conn->query("SELECT * FROM roles")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">User Management</h4>
                <a href="dashboard.php" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            <div class="card-body">
                <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
                <?php endif; ?>

                <!-- Add/Edit User Form -->
                <div class="row mb-5">
                    <div class="col-md-6">
                        <h5><?php echo $editUser ? 'Edit User' : 'Add New User'; ?></h5>
                        <form method="POST" class="mb-4">
                            <?php if($editUser): ?>
                                <input type="hidden" name="user_id" value="<?php echo $editUser['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" 
                                       value="<?php echo $editUser ? htmlspecialchars($editUser['username']) : ''; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?php echo $editUser ? htmlspecialchars($editUser['email']) : ''; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><?php echo $editUser ? 'New Password (leave blank to keep current)' : 'Password'; ?></label>
                                <input type="password" name="password" class="form-control" <?php echo $editUser ? '' : 'required'; ?>>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Role</label>
                                <select name="role_id" class="form-select" required>
                                    <?php foreach($roles as $role): ?>
                                    <option value="<?php echo $role['id']; ?>" 
                                        <?php echo ($editUser && $editUser['role_id'] == $role['id']) ? 'selected' : ''; ?>>
                                        <?php echo $role['name']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <?php if($editUser): ?>
                                <button type="submit" name="update_user" class="btn btn-primary">
                                    <i class="bi bi-check"></i> Update User
                                </button>
                                <a href="manage_users.php" class="btn btn-secondary">Cancel</a>
                            <?php else: ?>
                                <button type="submit" name="add_user" class="btn btn-primary">
                                    <i class="bi bi-person-plus"></i> Add User
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Users List -->
                <h5>System Users</h5>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $user['role_name'] == 'Administrator' ? 'danger' : 
                                             ($user['role_name'] == 'Manager' ? 'warning' : 'info'); 
                                    ?>">
                                        <?php echo $user['role_name']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="manage_users.php?edit=<?php echo $user['id']; ?>" class="btn btn-outline-primary">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <?php if($user['id'] != $_SESSION['user']): ?>
                                        <a href="manage_users.php?delete=<?php echo $user['id']; ?>" 
                                           class="btn btn-outline-danger" 
                                           onclick="return confirm('Are you sure you want to delete this user?')">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
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

<?php include 'footer.php'; ?>