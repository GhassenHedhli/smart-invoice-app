<?php
include 'config.php';

$settings = $conn->query("SELECT * FROM invoice_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if(isset($_POST['save'])) {
    $company_name = $_POST['company_name'];
    $company_email = $_POST['company_email'];
    $company_phone = $_POST['company_phone'];
    $company_address = $_POST['company_address'];
    $primary_color = $_POST['primary_color'];
    $secondary_color = $_POST['secondary_color'];
    $tax_rate = floatval($_POST['tax_rate']);

    // Handle logo upload
    $logo_path = $settings['logo_path'];
    if(isset($_FILES['logo']) && $_FILES['logo']['error'] === 0){
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png'];
        if(!in_array($ext, $allowed)){
            $errorMessage = "Unsupported logo format. Please upload JPG or PNG.";
        } else {
            $uploadDir = __DIR__ . '/uploads/'; // absolute server path
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true); // ensure uploads folder exists
            }

            $absolutePath = $uploadDir . 'logo_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['logo']['tmp_name'], $absolutePath);

            // Save only the relative path into DB (for HTML display)
            $logo_path = 'uploads/' . basename($absolutePath);
        }
}



    $stmt = $conn->prepare("UPDATE invoice_settings SET 
        company_name=:company_name, company_email=:company_email, company_phone=:company_phone, company_address=:company_address, 
        logo_path=:logo_path, primary_color=:primary_color, secondary_color=:secondary_color, tax_rate=:tax_rate
        WHERE id=:id");
    $stmt->execute([
        ':company_name'=>$company_name,
        ':company_email'=>$company_email,
        ':company_phone'=>$company_phone,
        ':company_address'=>$company_address,
        ':logo_path'=>$logo_path,
        ':primary_color'=>$primary_color,
        ':secondary_color'=>$secondary_color,
        ':tax_rate'=>$tax_rate,
        ':id'=>$settings['id']
    ]);

    $successMessage = "Settings saved successfully!";
}
?>

<?php include 'header.php'; ?>

<div class="container my-4">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="bi bi-gear"></i> Invoice Settings</h4>
        </div>
        <div class="card-body">
            <?php if(isset($successMessage)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">Company Name</label>
                        <input type="text" name="company_name" class="form-control" value="<?=htmlspecialchars($settings['company_name'])?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Company Email</label>
                        <input type="email" name="company_email" class="form-control" value="<?=htmlspecialchars($settings['company_email'])?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="text" name="company_phone" class="form-control" value="<?=htmlspecialchars($settings['company_phone'])?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Address</label>
                        <textarea name="company_address" class="form-control" rows="3"><?=htmlspecialchars($settings['company_address'])?></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Logo</label>
                        <input type="file" name="logo" class="form-control">
                        <?php if($settings['logo_path']): ?>
                            <img src="<?=htmlspecialchars($settings['logo_path'])?>" alt="Logo" class="img-thumbnail mt-2" style="max-height:100px;">
                        <?php endif; ?>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Primary Color</label>
                        <input type="color" name="primary_color" class="form-control form-control-color" value="<?=htmlspecialchars($settings['primary_color'])?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Secondary Color</label>
                        <input type="color" name="secondary_color" class="form-control form-control-color" value="<?=htmlspecialchars($settings['secondary_color'])?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Tax Rate (%)</label>
                        <input type="number" step="0.01" name="tax_rate" class="form-control" value="<?=htmlspecialchars($settings['tax_rate'])?>">
                    </div>

                    <div class="col-12 mt-3">
                        <button type="submit" name="save" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Save Settings
                        </button>
                        <a href="create_invoice.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Invoices
                        </a>
                    </div>

                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
