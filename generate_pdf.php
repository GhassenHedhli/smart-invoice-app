<?php
// pdf_generator.php - Fully integrated for all templates with logo, colors, and tax
require('vendor/fpdf/fpdf.php');

function generateInvoicePDF($invoice_id, $template_id = 1) {
    include 'config.php';

    if(empty($invoice_id)) throw new Exception("Invoice ID not provided.");

    // Fetch invoice settings
    $settings = $conn->query("SELECT * FROM invoice_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $logo = isset($settings['logo_path']) ? basename($settings['logo_path']) : '';
    $primaryColor = $settings['primary_color'] ?? '#0d6efd';
    $secondaryColor = $settings['secondary_color'] ?? '#6c757d';
    $companyName = $settings['company_name'] ?? 'My Company';
    $companyEmail = $settings['company_email'] ?? '';
    $companyPhone = $settings['company_phone'] ?? '';
    $companyAddress = $settings['company_address'] ?? '';
    $taxRate = floatval($settings['tax_rate'] ?? 0);

    // Convert hex color to RGB array
    function hex2rgb($hex) {
        return [
            hexdec(substr($hex,1,2)),
            hexdec(substr($hex,3,2)),
            hexdec(substr($hex,5,2))
        ];
    }

    // Base class to include company info & logo
    class BaseInvoice extends FPDF {
        public $logo, $companyName, $companyAddress, $companyEmail, $companyPhone;
        public $primaryColor, $secondaryColor;

        function addCompanyHeader() {
            if($this->logo && file_exists($this->logo)) {
            $ext = strtolower(pathinfo($this->logo, PATHINFO_EXTENSION));
            $pdfExt = $ext === 'png' ? 'PNG' : '';
            $this->Image($logo, 10, 10, 40, 30, $pdfExt);
            }

            $this->SetXY(60,10);
            $this->SetFont('Arial','B',14);
            $this->Cell(0,6, $this->companyName, 0, 1);
            $this->SetFont('Arial','',12);
            $this->MultiCell(0,5, $this->companyAddress."\nEmail: {$this->companyEmail}\nPhone: {$this->companyPhone}");
            $this->Ln(10);
        }

        function addClientInfo($invoice) {
            $this->SetFont('Arial','B',12);
            $this->Cell(0,6,'Invoice #'.$invoice['id'],0,1);
            $this->Cell(0,6,'Date: '.date('M d, Y', strtotime($invoice['date'])),0,1);
            $this->Ln(5);

            $this->Cell(0,6,'Bill To:',0,1);
            $this->SetFont('Arial','',12);
            $this->Cell(0,6,$invoice['client_name'],0,1);
            $this->Cell(0,6,'Email: '.$invoice['email'],0,1);
            $this->Cell(0,6,'Phone: '.$invoice['phone'],0,1);
            $this->Ln(10);
        }
    }

    // Templates
    class ModernInvoice extends BaseInvoice {
        function Header() {
            $rgb = hex2rgb($this->primaryColor);
            $this->SetFillColor($rgb[0], $rgb[1], $rgb[2]);
            $this->Rect(0,0,210,40,'F');
            $this->SetY(15);
            $this->SetFont('Arial','B',24);
            $this->SetTextColor(255,255,255);
            $this->Cell(0,10,'INVOICE',0,1,'C');
            $this->Ln(20);
        }
        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial','I',8);
            $this->SetTextColor(128,128,128);
            $this->Cell(0,10,'Page '.$this->PageNo(),0,0,'C');
        }
    }

    class ClassicInvoice extends BaseInvoice {
        function Header() {
            $rgb = hex2rgb($this->primaryColor);
            $this->SetFillColor($rgb[0], $rgb[1], $rgb[2]);
            $this->Rect(0,0,210,10,'F');
            $this->SetFont('Arial','B',16);
            $this->SetTextColor(0,0,0);
            $this->Cell(0,10,'INVOICE',0,1,'L');
            $this->Line(10,15,200,15);
            $this->Ln(10);
        }
    }

    class MinimalInvoice extends BaseInvoice {
        function Header() {
            $rgb = hex2rgb($this->primaryColor);
            $this->SetTextColor($rgb[0], $rgb[1], $rgb[2]);
            $this->SetFont('Arial','B',14);
            $this->Cell(0,10,'INVOICE',0,1,'R');
            $this->Ln(5);
        }
    }

    class ProfessionalInvoice extends BaseInvoice {
        function Header() {
            $rgb = hex2rgb($this->primaryColor);
            $this->SetFont('Arial','B',18);
            $this->SetTextColor($rgb[0], $rgb[1], $rgb[2]);
            $this->Cell(0,10,'PROFESSIONAL INVOICE',0,1,'C');
            $this->Ln(5);
        }
    }

    // Select template
    switch($template_id) {
        case 1: $pdf = new ModernInvoice(); break;
        case 2: $pdf = new ClassicInvoice(); break;
        case 3: $pdf = new MinimalInvoice(); break;
        case 4: $pdf = new ProfessionalInvoice(); break;
        default: $pdf = new ModernInvoice();
    }

    // Set company info & colors
    $pdf->logo = $logo;
    $pdf->companyName = $companyName;
    $pdf->companyAddress = $companyAddress;
    $pdf->companyEmail = $companyEmail;
    $pdf->companyPhone = $companyPhone;
    $pdf->primaryColor = $primaryColor;
    $pdf->secondaryColor = $secondaryColor;

    // Fetch invoice
    $stmt = $conn->prepare("
        SELECT i.*, c.name as client_name, c.email, c.phone 
        FROM invoices i 
        JOIN clients c ON i.client_id = c.id 
        WHERE i.id = :id
    ");
    $stmt->execute([':id' => $invoice_id]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$invoice) throw new Exception("Invoice not found.");

    // Fetch items
    $stmt_items = $conn->prepare("
        SELECT p.name, ii.quantity, ii.price 
        FROM invoice_items ii 
        JOIN products p ON ii.product_id = p.id 
        WHERE ii.invoice_id = :id
    ");
    $stmt_items->execute([':id' => $invoice_id]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    // Generate PDF
    $pdf->AddPage();
    $pdf->addCompanyHeader();
    $pdf->addClientInfo($invoice);

    // Table header
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(100,10,'Product',1);
    $pdf->Cell(30,10,'Qty',1);
    $pdf->Cell(30,10,'Price',1);
    $pdf->Cell(30,10,'Subtotal',1);
    $pdf->Ln();

    // Table rows
    $pdf->SetFont('Arial','',12);
    $subtotal = 0;
    foreach($items as $item){
        $lineSubtotal = $item['quantity'] * $item['price'];
        $subtotal += $lineSubtotal;
        $pdf->Cell(100,10,$item['name'],1);
        $pdf->Cell(30,10,$item['quantity'],1);
        $pdf->Cell(30,10,'$'.number_format($item['price'],2),1);
        $pdf->Cell(30,10,'$'.number_format($lineSubtotal,2),1);
        $pdf->Ln();
    }

    // Totals
    $taxAmount = $subtotal * ($taxRate / 100);
    $total = $subtotal + $taxAmount;

    $pdf->Ln(5);
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,6,'Subtotal: $'.number_format($subtotal,2),0,1);
    $pdf->Cell(0,6,'Tax ('.$taxRate.'%): $'.number_format($taxAmount,2),0,1);
    $pdf->Cell(0,6,'Total: $'.number_format($total,2),0,1);

    return $pdf->Output('S'); // Return PDF as string
}

// Direct URL access
if(isset($_GET['id'])) {
    try {
        $invoice_id = intval($_GET['id']);
        $template_id = isset($_GET['template']) ? intval($_GET['template']) : 1;
        $pdf_content = generateInvoicePDF($invoice_id, $template_id);

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="invoice_'.$invoice_id.'.pdf"');
        echo $pdf_content;
    } catch (Exception $e) {
        die($e->getMessage());
    }
}
?>
