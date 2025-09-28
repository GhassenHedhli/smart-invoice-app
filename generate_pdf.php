<?php
// pdf_generator.php - Fixed version with proper logo handling
require('vendor/fpdf/fpdf.php');

function generateInvoicePDF($invoice_id, $template_id = 1) {
    include 'config.php';

    if(empty($invoice_id)) throw new Exception("Invoice ID not provided.");

    // Fetch invoice settings
    $settings = $conn->query("SELECT * FROM invoice_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    $logo_path = $settings['logo_path'] ?? '';
    $primaryColor = $settings['primary_color'] ?? '#0d6efd';
    $secondaryColor = $settings['secondary_color'] ?? '#6c757d';
    $companyName = $settings['company_name'] ?? 'My Company';
    $companyEmail = $settings['company_email'] ?? '';
    $companyPhone = $settings['company_phone'] ?? '';
    $companyAddress = $settings['company_address'] ?? '';
    $taxRate = floatval($settings['tax_rate'] ?? 0);

    // Convert hex color to RGB array
    function hex2rgb($hex) {
        $hex = ltrim($hex, '#');
        return [
            hexdec(substr($hex,0,2)),
            hexdec(substr($hex,2,2)),
            hexdec(substr($hex,4,2))
        ];
    }

    // Base class to include company info & logo
    class BaseInvoice extends FPDF {
        public $logo_path, $companyName, $companyAddress, $companyEmail, $companyPhone;
        public $primaryColor, $secondaryColor;

        function addCompanyHeader() {
            $y_position = $this->GetY();
            
            // Add logo if it exists
            if($this->logo_path && file_exists($this->logo_path)) {
                $ext = strtolower(pathinfo($this->logo_path, PATHINFO_EXTENSION));
                
                // FPDF only supports JPG and PNG directly
                if(in_array($ext, ['jpg', 'jpeg', 'png'])) {
                    try {
                        // Get image dimensions to maintain aspect ratio
                        list($width, $height) = getimagesize($this->logo_path);
                        
                        // Calculate scaled dimensions (max 40 width, 30 height)
                        $max_width = 40;
                        $max_height = 30;
                        $scale = min($max_width/$width, $max_height/$height);
                        $new_width = $width * $scale;
                        $new_height = $height * $scale;
                        
                        $this->Image($this->logo_path, 10, $y_position, $new_width, $new_height);
                        $logo_width = $new_width + 10; // Space after logo
                    } catch(Exception $e) {
                        // If image fails to load, continue without logo
                        $logo_width = 0;
                        error_log("Logo loading failed: " . $e->getMessage());
                    }
                } else {
                    $logo_width = 0;
                }
            } else {
                $logo_width = 0;
            }

            // Company info next to logo
            $this->SetXY(10 + $logo_width, $y_position);
            $this->SetFont('Arial','B',14);
            $this->Cell(0,6, $this->companyName, 0, 1);
            
            $this->SetX(10 + $logo_width);
            $this->SetFont('Arial','',10);
            
            // Split address into lines and add each
            if($this->companyAddress) {
                $address_lines = explode("\n", $this->companyAddress);
                foreach($address_lines as $line) {
                    if(trim($line)) {
                        $this->Cell(0,4, trim($line), 0, 1);
                        $this->SetX(10 + $logo_width);
                    }
                }
            }
            
            if($this->companyEmail) {
                $this->Cell(0,4, 'Email: ' . $this->companyEmail, 0, 1);
                $this->SetX(10 + $logo_width);
            }
            
            if($this->companyPhone) {
                $this->Cell(0,4, 'Phone: ' . $this->companyPhone, 0, 1);
            }
            
            $this->Ln(10);
        }

        function addClientInfo($invoice) {
            $this->SetFont('Arial','B',12);
            $this->Cell(0,6,'Invoice #'.$invoice['id'],0,1);
            $this->Cell(0,6,'Date: '.date('M d, Y', strtotime($invoice['date'])),0,1);
            $this->Ln(5);

            $this->SetFont('Arial','B',10);
            $this->Cell(0,6,'Bill To:',0,1);
            $this->SetFont('Arial','',10);
            $this->Cell(0,5,$invoice['client_name'],0,1);
            if($invoice['email']) {
                $this->Cell(0,5,'Email: '.$invoice['email'],0,1);
            }
            if($invoice['phone']) {
                $this->Cell(0,5,'Phone: '.$invoice['phone'],0,1);
            }
            $this->Ln(10);
        }
    }

    // Templates with proper color handling
    class ModernInvoice extends BaseInvoice {
        function Header() {
            $rgb = hex2rgb($this->primaryColor);
            $this->SetFillColor($rgb[0], $rgb[1], $rgb[2]);
            $this->Rect(0,0,210,40,'F');
            $this->SetY(15);
            $this->SetFont('Arial','B',24);
            $this->SetTextColor(255,255,255);
            $this->Cell(0,10,'INVOICE',0,1,'C');
            $this->SetTextColor(0,0,0); // Reset text color
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
            $this->SetY(15);
            $this->SetFont('Arial','B',16);
            $this->SetTextColor(0,0,0);
            $this->Cell(0,10,'INVOICE',0,1,'L');
            $this->Line(10,25,200,25);
            $this->Ln(10);
        }
    }

    class MinimalInvoice extends BaseInvoice {
        function Header() {
            $rgb = hex2rgb($this->primaryColor);
            $this->SetTextColor($rgb[0], $rgb[1], $rgb[2]);
            $this->SetFont('Arial','B',14);
            $this->Cell(0,10,'INVOICE',0,1,'R');
            $this->SetTextColor(0,0,0); // Reset text color
            $this->Ln(5);
        }
    }

    class ProfessionalInvoice extends BaseInvoice {
        function Header() {
            $rgb = hex2rgb($this->primaryColor);
            $this->SetFont('Arial','B',18);
            $this->SetTextColor($rgb[0], $rgb[1], $rgb[2]);
            $this->Cell(0,10,'PROFESSIONAL INVOICE',0,1,'C');
            $this->SetTextColor(0,0,0); // Reset text color
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
    $pdf->logo_path = __DIR__ . '/' . $logo_path; // make absolute path for FPDF
 // Use full path, not basename
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

    // Table header with colors
    $rgb = hex2rgb($primaryColor);
    $pdf->SetFillColor($rgb[0], $rgb[1], $rgb[2]);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(100,8,'Product',1,0,'L',true);
    $pdf->Cell(25,8,'Qty',1,0,'C',true);
    $pdf->Cell(30,8,'Price',1,0,'R',true);
    $pdf->Cell(35,8,'Subtotal',1,1,'R',true);

    // Reset colors for table content
    $pdf->SetFillColor(245,245,245);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial','',9);

    // Table rows
    $subtotal = 0;
    $fill = false;
    foreach($items as $item){
        $lineSubtotal = $item['quantity'] * $item['price'];
        $subtotal += $lineSubtotal;
        
        $pdf->Cell(100,6,$item['name'],1,0,'L',$fill);
        $pdf->Cell(25,6,$item['quantity'],1,0,'C',$fill);
        $pdf->Cell(30,6,'$'.number_format($item['price'],2),1,0,'R',$fill);
        $pdf->Cell(35,6,'$'.number_format($lineSubtotal,2),1,1,'R',$fill);
        
        $fill = !$fill; // Alternate row colors
    }

    // Totals section
    $taxAmount = $subtotal * ($taxRate / 100);
    $total = $subtotal + $taxAmount;

    $pdf->Ln(10);
    $pdf->SetFont('Arial','B',11);
    
    // Right-align totals
    $pdf->Cell(155,6,'',0,0); // Spacer
    $pdf->Cell(35,6,'Subtotal: $'.number_format($subtotal,2),0,1,'R');
    
    $pdf->Cell(155,6,'',0,0); // Spacer
    $pdf->Cell(35,6,'Tax ('.$taxRate.'%): $'.number_format($taxAmount,2),0,1,'R');
    
    // Total with background
    $pdf->SetFillColor($rgb[0], $rgb[1], $rgb[2]);
    $pdf->SetTextColor(255,255,255);
    $pdf->Cell(155,8,'',0,0); // Spacer
    $pdf->Cell(35,8,'Total: $'.number_format($total,2),1,1,'R',true);

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
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        echo $pdf_content;
    } catch (Exception $e) {
        die('Error generating PDF: ' . $e->getMessage());
    }
}
?>