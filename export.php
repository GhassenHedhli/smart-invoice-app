<?php
// export.php - Export functionality for CSV/XLSX
session_start();
//if(!isset($_SESSION['admin'])){
    //header("Location: login.php");
   // exit;
//}

include 'config.php';

// Check if export is requested
if(isset($_GET['export'])) {
    $export_type = $_GET['export'];
    $format = $_GET['format'] ?? 'csv';
    
    // Validate export type
    $allowed_types = ['clients', 'products', 'invoices'];
    if(!in_array($export_type, $allowed_types)) {
        die("Invalid export type");
    }
    
    // Validate format
    $allowed_formats = ['csv', 'xlsx'];
    if(!in_array($format, $allowed_formats)) {
        die("Invalid format");
    }
    
    // Fetch data based on type
    switch($export_type) {
        case 'clients':
            $data = $conn->query("
                SELECT id, name, email, phone, address, 
                       CASE WHEN login_enabled = 1 THEN 'Enabled' ELSE 'Disabled' END as portal_access,
                       created_at 
                FROM clients 
                ORDER BY created_at DESC
            ")->fetchAll(PDO::FETCH_ASSOC);
            $filename = "clients_export_" . date('Y-m-d');
            $headers = ['ID', 'Name', 'Email', 'Phone', 'Address', 'Portal Access', 'Created At'];
            break;
            
        case 'products':
            $data = $conn->query("
                SELECT id, name, description, price, created_at 
                FROM products 
                ORDER BY created_at DESC
            ")->fetchAll(PDO::FETCH_ASSOC);
            $filename = "products_export_" . date('Y-m-d');
            $headers = ['ID', 'Name', 'Description', 'Price', 'Created At'];
            break;
            
        case 'invoices':
            $data = $conn->query("
                SELECT i.id, c.name as client_name, i.total, i.date, i.status, 
                       i.template_id, i.created_at 
                FROM invoices i 
                JOIN clients c ON i.client_id = c.id 
                ORDER BY i.created_at DESC
            ")->fetchAll(PDO::FETCH_ASSOC);
            $filename = "invoices_export_" . date('Y-m-d');
            $headers = ['ID', 'Client Name', 'Total', 'Date', 'Status', 'Template ID', 'Created At'];
            break;
    }
    
    // Log the export
    $stmt = $conn->prepare("
        INSERT INTO export_logs (export_type, format, exported_by, file_name, record_count) 
        VALUES (:export_type, :format, :exported_by, :file_name, :record_count)
    ");
    $stmt->execute([
        ':export_type' => $export_type,
        ':format' => $format,
        ':exported_by' => $_SESSION['admin'],
        ':file_name' => $filename . '.' . $format,
        ':record_count' => count($data)
    ]);
    
    // Export based on format
    if($format == 'csv') {
        exportToCSV($data, $filename, $headers);
    } else {
        exportToXLSX($data, $filename, $headers);
    }
    exit;
}

function exportToCSV($data, $filename, $headers) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, $headers);
    
    foreach($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
}

function exportToXLSX($data, $filename, $headers) {
    // Simple XLSX generation using XML (basic implementation)
    // For more advanced XLSX, consider using PhpSpreadsheet library
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
    
    // Basic XML structure for XLSX (simplified)
    $xml = '<?xml version="1.0"?>
    <Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
              xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
    <Worksheet ss:Name="Export">
    <Table>';
    
    // Headers
    $xml .= '<Row>';
    foreach($headers as $header) {
        $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars($header) . '</Data></Cell>';
    }
    $xml .= '</Row>';
    
    // Data rows
    foreach($data as $row) {
        $xml .= '<Row>';
        foreach($row as $cell) {
            $type = is_numeric($cell) ? 'Number' : 'String';
            $xml .= '<Cell><Data ss:Type="' . $type . '">' . htmlspecialchars($cell) . '</Data></Cell>';
        }
        $xml .= '</Row>';
    }
    
    $xml .= '</Table></Worksheet></Workbook>';
    
    echo $xml;
}

// If not exporting, show export page
$pageTitle = "Export Data";
include 'header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Export Data</h4>
                <a href="dashboard.php" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            <div class="card-body">
                <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
                <?php endif; ?>
                
                <div class="row g-4">
                    <!-- Clients Export -->
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-people display-4 text-primary mb-3"></i>
                                <h5>Export Clients</h5>
                                <p class="text-muted">Export all client data</p>
                                <div class="btn-group">
                                    <a href="export.php?export=clients&format=csv" class="btn btn-outline-success">
                                        <i class="bi bi-file-earmark-spreadsheet"></i> CSV
                                    </a>
                                    <a href="export.php?export=clients&format=xlsx" class="btn btn-outline-primary">
                                        <i class="bi bi-file-earmark-spreadsheet"></i> XLSX
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Products Export -->
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-box-seam display-4 text-success mb-3"></i>
                                <h5>Export Products</h5>
                                <p class="text-muted">Export all product data</p>
                                <div class="btn-group">
                                    <a href="export.php?export=products&format=csv" class="btn btn-outline-success">
                                        <i class="bi bi-file-earmark-spreadsheet"></i> CSV
                                    </a>
                                    <a href="export.php?export=products&format=xlsx" class="btn btn-outline-primary">
                                        <i class="bi bi-file-earmark-spreadsheet"></i> XLSX
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Invoices Export -->
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-receipt display-4 text-warning mb-3"></i>
                                <h5>Export Invoices</h5>
                                <p class="text-muted">Export all invoice data</p>
                                <div class="btn-group">
                                    <a href="export.php?export=invoices&format=csv" class="btn btn-outline-success">
                                        <i class="bi bi-file-earmark-spreadsheet"></i> CSV
                                    </a>
                                    <a href="export.php?export=invoices&format=xlsx" class="btn btn-outline-primary">
                                        <i class="bi bi-file-earmark-spreadsheet"></i> XLSX
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Export History -->
                <div class="mt-5">
                    <h5>Export History</h5>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Format</th>
                                    <th>Records</th>
                                    <th>File Name</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $exports = $conn->query("
                                    SELECT el.*, a.username 
                                    FROM export_logs el 
                                    LEFT JOIN admins a ON el.exported_by = a.id 
                                    ORDER BY el.exported_at DESC 
                                    LIMIT 10
                                ")->fetchAll(PDO::FETCH_ASSOC);
                                
                                foreach($exports as $export):
                                ?>
                                <tr>
                                    <td><?php echo date('M j, Y g:i A', strtotime($export['exported_at'])); ?></td>
                                    <td><span class="badge bg-primary"><?php echo ucfirst($export['export_type']); ?></span></td>
                                    <td><span class="badge bg-<?php echo $export['format'] == 'csv' ? 'success' : 'info'; ?>">
                                        <?php echo strtoupper($export['format']); ?>
                                    </span></td>
                                    <td><?php echo $export['record_count']; ?> records</td>
                                    <td><small class="text-muted"><?php echo $export['file_name']; ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>