<?php
require 'vendor/autoload.php'; // Path to PHPMailer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendInvoiceEmail($invoice_id, $template_id = 1) {
    include 'config.php';
    
    // Fetch invoice details
    $stmt = $conn->prepare("
        SELECT i.*, c.name as client_name, c.email as client_email 
        FROM invoices i 
        JOIN clients c ON i.client_id = c.id 
        WHERE i.id = :id
    ");
    $stmt->execute([':id' => $invoice_id]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$invoice || empty($invoice['client_email'])) {
        return false;
    }
    
    // Generate PDF - FIX: Set the GET parameters before including generate_pdf.php
    $_GET['id'] = $invoice_id;
    $_GET['template'] = $template_id;
    
    ob_start();
    require_once 'generate_pdf.php'; // This will now have access to $_GET['id']
    generateInvoicePDF($invoice_id, $template_id);
    
    // Create PHPMailer instance
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->SMTPDebug = 2; // or 3 for more details
        $mail->Debugoutput = 'error_log'; // log to PHP error log
        $mail->isSMTP();
        $mail->Host = 'smtp.example.com'; // Set your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'marketbusinessofall@gmail.com'; // SMTP username
        $mail->Password = 'synl vqrq inyh fzan'; // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Recipients
        $mail->setFrom('marketbusinessofall@gmail.com', 'Your Company Billing');
        $mail->addAddress($invoice['client_email'], $invoice['client_name']);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Invoice #' . $invoice_id . ' from Your Company';
        $mail->Body = "
            <h2>Invoice #{$invoice_id}</h2>
            <p>Dear {$invoice['client_name']},</p>
            <p>Please find your invoice attached.</p>
            <p><strong>Amount Due: $" . number_format($invoice['total'], 2) . "</strong></p>
            <p>Due Date: " . date('M j, Y', strtotime('+30 days')) . "</p>
            <p>Thank you for your business!</p>
        ";
        $mail->AltBody = "Invoice #{$invoice_id}\nAmount: $" . number_format($invoice['total'], 2);
        
        // Attach PDF
        $mail->addStringAttachment($pdf_content, "invoice_{$invoice_id}.pdf");
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

// Fallback function using basic mail() if PHPMailer is not available
function sendInvoiceEmailBasic($invoice_id) {
    include 'config.php';
    
    $stmt = $conn->prepare("
        SELECT i.*, c.name as client_name, c.email as client_email 
        FROM invoices i 
        JOIN clients c ON i.client_id = c.id 
        WHERE i.id = :id
    ");
    $stmt->execute([':id' => $invoice_id]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$invoice || empty($invoice['client_email'])) {
        return false;
    }
    
    $subject = "Invoice #{$invoice_id} from Your Company";
    $message = "
        Invoice #{$invoice_id}
        
        Client: {$invoice['client_name']}
        Amount: $" . number_format($invoice['total'], 2) . "
        Date: " . date('M j, Y', strtotime($invoice['date'])) . "
        
        Please log in to your client portal to download the PDF.
        
        Thank you!
    ";
    
    $headers = "From: billing@yourcompany.com\r\n";
    
    return mail($invoice['client_email'], $subject, $message, $headers);
}
?>