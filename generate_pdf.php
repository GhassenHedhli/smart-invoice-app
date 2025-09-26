<?php
// generate_pdf.php - FIXED VERSION
require('vendor/fpdf/fpdf.php');
include 'config.php';

// Check if invoice_id is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invoice ID not provided.");
}

$invoice_id = intval($_GET['id']);
$template_id = isset($_GET['template']) ? intval($_GET['template']) : 1;

// Define invoice classes
class ModernInvoice extends FPDF {
    function Header() {
        // Modern header design
        $this->SetFillColor(67, 97, 238);
        $this->Rect(0, 0, 210, 40, 'F');
        $this->SetY(15);
        $this->SetFont('Arial', 'B', 24);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 10, 'INVOICE', 0, 1, 'C');
        $this->Ln(20);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }
}

class ClassicInvoice extends FPDF {
    function Header() {
        // Classic header design
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'INVOICE', 0, 1, 'L');
        $this->Line(10, 15, 200, 15);
        $this->Ln(10);
    }
}

class MinimalInvoice extends FPDF {
    function Header() {
        $this->SetFont('Arial','B',14);
        $this->Cell(0,10,'INVOICE',0,1,'R');
        $this->Ln(5);
    }
}

class ProfessionalInvoice extends FPDF {
    function Header() {
        $this->SetFont('Arial','B',18);
        $this->SetTextColor(0,51,102);
        $this->Cell(0,10,'PROFESSIONAL INVOICE',0,1,'C');
        $this->Ln(5);
    }
}

// Select template based on template_id
switch($template_id) {
    case 1: $pdf = new ModernInvoice(); break;
    case 2: $pdf = new ClassicInvoice(); break;
    case 3: $pdf = new MinimalInvoice(); break;
    case 4: $pdf = new ProfessionalInvoice(); break;
    default: $pdf = new ModernInvoice();
}

// Fetch invoice
$stmt = $conn->prepare("
    SELECT i.*, c.name as client_name, c.email, c.phone 
    FROM invoices i 
    JOIN clients c ON i.client_id = c.id 
    WHERE i.id = :id
");
$stmt->execute([':id' => $invoice_id]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$invoice){
    die("Invoice not found.");
}

// Fetch invoice items
$stmt_items = $conn->prepare("
    SELECT p.name, ii.quantity, ii.price 
    FROM invoice_items ii 
    JOIN products p ON ii.product_id = p.id 
    WHERE ii.invoice_id = :id
");
$stmt_items->execute([':id' => $invoice_id]);
$items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

// Create PDF content
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);

// Invoice header
$pdf->Cell(0,10,'INVOICE #'.$invoice['id'],0,1,'C');
$pdf->Ln(10);

// Client information
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,10,'Bill To:',0,1);
$pdf->SetFont('Arial','',12);
$pdf->Cell(0,10,$invoice['client_name'],0,1);
$pdf->Cell(0,10,'Email: '.$invoice['email'],0,1);
$pdf->Cell(0,10,'Phone: '.$invoice['phone'],0,1);
$pdf->Cell(0,10,'Date: '.date('M d, Y', strtotime($invoice['date'])),0,1);
$pdf->Ln(10);

// Table header
$pdf->SetFont('Arial','B',12);
$pdf->Cell(100,10,'Product',1);
$pdf->Cell(30,10,'Qty',1);
$pdf->Cell(30,10,'Price',1);
$pdf->Cell(30,10,'Subtotal',1);
$pdf->Ln();

// Table rows
$pdf->SetFont('Arial','',12);
$total = 0;
foreach($items as $item){
    $subtotal = $item['quantity'] * $item['price'];
    $total += $subtotal;
    $pdf->Cell(100,10,$item['name'],1);
    $pdf->Cell(30,10,$item['quantity'],1);
    $pdf->Cell(30,10,'$'.number_format($item['price'],2),1);
    $pdf->Cell(30,10,'$'.number_format($subtotal,2),1);
    $pdf->Ln();
}

// Total
$pdf->Ln(5);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,10,'Total: $'.number_format($total,2),0,1);

// Output PDF
if(isset($_GET['client_access'])) {
    $pdf->Output('D','invoice_'.$invoice['id'].'.pdf'); // Force download for clients
} else {
    $pdf->Output('I','invoice_'.$invoice['id'].'.pdf'); // Inline for admin
}
?>