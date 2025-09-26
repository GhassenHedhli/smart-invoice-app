<?php
// create_invoice.php - FIXED VERSION

// Process form submission first (before any output)
if(isset($_POST['create'])){
    include 'config.php';
    
    // Create invoice
    $client_id = intval($_POST['client_id']);
    $template_id = intval($_POST['template_id']);
    $date = date('Y-m-d');
    $total = 0;
    
    // Calculate total
    if(isset($_POST['product_id'])) {
        foreach($_POST['product_id'] as $index => $product_id) {
            $quantity = intval($_POST['quantity'][$index]);
            $price = floatval($_POST['price'][$index]);
            $total += $quantity * $price;
        }
    }
    
    // Insert invoice
    $stmt = $conn->prepare("INSERT INTO invoices (client_id, date, total, template_id) VALUES (:client_id, :date, :total, :template_id)");
    $stmt->execute([
        ':client_id' => $client_id,
        ':date' => $date,
        ':total' => $total,
        ':template_id' => $template_id
    ]);
    
    $invoice_id = $conn->lastInsertId();
    
    // Insert invoice items
    if(isset($_POST['product_id'])) {
        foreach($_POST['product_id'] as $index => $product_id) {
            $quantity = intval($_POST['quantity'][$index]);
            $price = floatval($_POST['price'][$index]);
            
            $stmt = $conn->prepare("INSERT INTO invoice_items (invoice_id, product_id, quantity, price) VALUES (:invoice_id, :product_id, :quantity, :price)");
            $stmt->execute([
                ':invoice_id' => $invoice_id,
                ':product_id' => $product_id,
                ':quantity' => $quantity,
                ':price' => $price
            ]);
        }
    }
    
    // Send email if requested
    if(isset($_POST['send_email']) && $_POST['send_email'] == '1') {
    // Include the send_invoice function
    include 'send_invoice.php';
    $emailSent = sendInvoiceEmail($invoice_id, $template_id);
    
    if($emailSent) {
        // Update invoice status
        $stmt = $conn->prepare("UPDATE invoices SET sent_via_email = 1 WHERE id = :id");
        $stmt->execute([':id' => $invoice_id]);
        $successMessage = "Invoice created and sent successfully!";
    } else {
        $errorMessage = "Invoice created but email sending failed!";
    }
} else {
    $successMessage = "Invoice created successfully!";
}
    
    header("Location: invoices.php?success=Invoice created successfully!");
    exit;
}

// Now include headers and display the form
$pageTitle = "Create Invoice";
include 'header.php';
include 'config.php';

// Fetch clients and products
$clients = $conn->query("SELECT * FROM clients")->fetchAll(PDO::FETCH_ASSOC);
$products = $conn->query("SELECT * FROM products")->fetchAll(PDO::FETCH_ASSOC);

// Invoice templates
$templates = [
    ['id' => 1, 'name' => 'Modern', 'preview' => 'modern.jpg'],
    ['id' => 2, 'name' => 'Classic', 'preview' => 'classic.jpg'],
    ['id' => 3, 'name' => 'Minimal', 'preview' => 'minimal.jpg'],
    ['id' => 4, 'name' => 'Professional', 'preview' => 'professional.jpg']
];
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Create New Invoice</h4>
            </div>
            <div class="card-body">
                <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
                <?php endif; ?>
                
                <form method="post" id="invoiceForm">
                    <div class="row g-4">
                        <!-- Client Selection -->
                        <div class="col-md-6">
                            <label class="form-label">Select Client</label>
                            <select name="client_id" class="form-select" required id="clientSelect">
                                <option value="">Choose a client...</option>
                                <?php foreach($clients as $c): ?>
                                    <option value="<?php echo $c['id']; ?>" data-email="<?php echo htmlspecialchars($c['email']); ?>">
                                        <?php echo htmlspecialchars($c['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Template Selection -->
                        <div class="col-md-6">
                            <label class="form-label">Invoice Template</label>
                            <div class="row g-2">
                                <?php foreach($templates as $template): ?>
                                <div class="col-6">
                                    <div class="invoice-template p-2 text-center border rounded" data-template="<?php echo $template['id']; ?>">
                                        <div class="template-preview bg-light rounded p-3 mb-2">
                                            <i class="bi bi-file-text display-6 text-muted"></i>
                                        </div>
                                        <small class="text-muted"><?php echo $template['name']; ?></small>
                                        <input type="radio" name="template_id" value="<?php echo $template['id']; ?>" 
                                               class="d-none" <?php echo $template['id'] == 1 ? 'checked' : ''; ?>>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Email Option -->
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="send_email" value="1" id="sendEmail">
                                <label class="form-check-label" for="sendEmail">
                                    Send invoice via email to client
                                </label>
                            </div>
                            <small class="text-muted" id="clientEmailDisplay"></small>
                        </div>
                    </div>

                    <!-- Products Section -->
                    <div class="mt-4">
                        <h5 class="mb-3">Products & Services</h5>
                        <div id="productItems">
                            <?php if(!empty($products)): ?>
                                <?php foreach($products as $index => $product): ?>
                                <div class="product-item row g-2 align-items-center mb-2">
                                    <div class="col-md-5">
                                        <div class="form-check">
                                            <input class="form-check-input product-check" type="checkbox" 
                                                   name="product_id[]" value="<?php echo $product['id']; ?>" 
                                                   id="product<?php echo $product['id']; ?>">
                                            <label class="form-check-label" for="product<?php echo $product['id']; ?>">
                                                <?php echo htmlspecialchars($product['name']); ?>
                                            </label>
                                        </div>
                                        <small class="text-muted">$<?php echo number_format($product['price'], 2); ?></small>
                                        <input type="hidden" name="price[]" value="<?php echo $product['price']; ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <input type="number" name="quantity[]" class="form-control quantity-input" 
                                               value="1" min="1" placeholder="Qty" disabled>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="text" class="form-control subtotal" value="0.00" readonly>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    No products found. Please add products first.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Total Section -->
                    <div class="row mt-4">
                        <div class="col-md-6 offset-md-6">
                            <div class="total-section bg-light p-3 rounded">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal:</span>
                                    <span id="subtotal">$0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Tax (0%):</span>
                                    <span id="tax">$0.00</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between fw-bold fs-5">
                                    <span>Total:</span>
                                    <span id="total">$0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" name="create" class="btn btn-primary btn-lg">
                            <i class="bi bi-file-earmark-plus"></i> Create Invoice
                        </button>
                        <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Template selection
document.querySelectorAll('.invoice-template').forEach(template => {
    template.addEventListener('click', function() {
        document.querySelectorAll('.invoice-template').forEach(t => t.classList.remove('border-primary'));
        this.classList.add('border-primary');
        this.querySelector('input[type="radio"]').checked = true;
    });
});

// Client email display
document.getElementById('clientSelect').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const clientEmail = selectedOption.getAttribute('data-email');
    document.getElementById('clientEmailDisplay').textContent = clientEmail ? `Client email: ${clientEmail}` : '';
});

// Product selection and calculations
document.querySelectorAll('.product-check').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const quantityInput = this.closest('.product-item').querySelector('.quantity-input');
        quantityInput.disabled = !this.checked;
        if(this.checked) quantityInput.focus();
        calculateTotals();
    });
});

document.querySelectorAll('.quantity-input').forEach(input => {
    input.addEventListener('input', calculateTotals);
});

function calculateTotals() {
    let subtotal = 0;
    
    document.querySelectorAll('.product-item').forEach(item => {
        const checkbox = item.querySelector('.product-check');
        const quantityInput = item.querySelector('.quantity-input');
        const subtotalField = item.querySelector('.subtotal');
        const priceInput = item.querySelector('input[name="price[]"]');
        
        if (checkbox.checked && quantityInput.value > 0 && priceInput) {
            const price = parseFloat(priceInput.value);
            const quantity = parseInt(quantityInput.value);
            const itemTotal = price * quantity;
            
            subtotal += itemTotal;
            subtotalField.value = itemTotal.toFixed(2);
        } else {
            subtotalField.value = '0.00';
        }
    });
    
    document.getElementById('subtotal').textContent = '$' + subtotal.toFixed(2);
    document.getElementById('total').textContent = '$' + subtotal.toFixed(2);
}

// Initialize calculations and template selection
calculateTotals();
document.querySelector('.invoice-template').classList.add('border-primary');
</script>

<?php include 'footer.php'; ?>