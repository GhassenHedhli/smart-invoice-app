<?php
include 'config.php';

// Add new product
if (isset($_POST['add_product'])) {
    $stmt = $conn->prepare("INSERT INTO products (name, price) VALUES (:name, :price)");
    $stmt->execute([
        ':name' => $_POST['name'],
        ':price' => $_POST['price']
    ]);
    header("Location: products.php");
    exit;
}

$products = $conn->query("SELECT * FROM products")->fetchAll(PDO::FETCH_ASSOC);
include 'header.php';
?>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Add New Product</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Product Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price</label>
                        <input type="number" step="0.01" name="price" class="form-control" required>
                    </div>
                    <button type="submit" name="add_product" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Add Product
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Existing Products</h5>
    </div>
    <div class="card-body">
        <?php if (count($products) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead><tr><th>ID</th><th>Name</th><th>Price</th></tr></thead>
                    <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td><?php echo $p['id']; ?></td>
                            <td><?php echo htmlspecialchars($p['name']); ?></td>
                            <td>$<?php echo number_format($p['price'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted">No products yet. Add one above.</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
