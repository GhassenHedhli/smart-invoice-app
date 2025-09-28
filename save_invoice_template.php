<?php
// save_invoice_template.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'error' => 'Invalid input data']);
        exit;
    }

    try {
        $stmt = $conn->prepare("
            INSERT INTO invoice_templates 
            (name, template_data, client_id, created_at) 
            VALUES (:name, :template_data, :client_id, NOW())
        ");

        $stmt->execute([
            ':name' => $input['name'],
            ':template_data' => json_encode($input['components']),
            ':client_id' => $input['client_id'] ?: null
        ]);

        echo json_encode(['success' => true, 'id' => $conn->lastInsertId()]);
        
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>