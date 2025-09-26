<?php
$pageTitle = "Clients";
include 'header.php';
include 'config.php';

// Add new client
if(isset($_POST['add_client'])){
    $stmt = $conn->prepare("INSERT INTO clients (name, email, phone, address) VALUES (:name, :email, :phone, :address)");
    $stmt->execute([
        ':name' => $_POST['name'],
        ':email' => $_POST['email'],
        ':phone' => $_POST['phone'],
        ':address' => $_POST['address'] ?? '' // Handle address if it exists
    ]);
    header("Location: clients.php");
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
                <!-- Add Client Form -->
                <div class="row">
                    <div class="col-md-6">
                        <h5>Add New Client</h5>
                        <form method="POST" class="mb-4">
                            <div class="mb-3">
                                <label class="form-label">Client Name</label>
                                <input type="text" name="name" class="form-control" placeholder="Client Name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" placeholder="Email">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" placeholder="Phone">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" placeholder="Address" rows="2"></textarea>
                            </div>
                            <button type="submit" name="add_client" class="btn btn-primary">
                                <i class="bi bi-person-plus"></i> Add Client
                            </button>
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
                                <th>Address</th>
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
                                <td><?php echo htmlspecialchars($client['address'] ?? 'N/A'); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
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

<?php include 'footer.php'; ?>