<?php
include 'config.php';
$invoices = $conn->query("
    SELECT i.id, c.name as client_name, i.total, i.date 
    FROM invoices i
    JOIN clients c ON i.client_id = c.id
")->fetchAll(PDO::FETCH_ASSOC);
include 'header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Invoices</h5>
        <a href="dashboard.php" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>ID</th><th>Client</th><th>Total</th><th>Date</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($invoices as $row): ?>
                    <tr>
                        <td>#<?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                        <td>$<?php echo number_format($row['total'],2); ?></td>
                        <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                        <td>
                            <a href="generate_pdf.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-download"></i> PDF
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
