<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Path to SQLite database file
$dbFile = __DIR__ . '/database.sqlite';

try {
    // Create (or open) SQLite database
    $conn = new PDO("sqlite:$dbFile");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create tables if they don't exist
    $conn->exec("
        CREATE TABLE IF NOT EXISTS admins (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            email TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS clients (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT,
            phone TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            price REAL NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS invoices (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            client_id INTEGER NOT NULL,
            date TEXT NOT NULL,
            total REAL NOT NULL,
            status TEXT DEFAULT 'pending',
            template_id INTEGER DEFAULT 1,
            FOREIGN KEY(client_id) REFERENCES clients(id)
        );

        CREATE TABLE IF NOT EXISTS invoice_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            invoice_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            quantity INTEGER NOT NULL,
            price REAL NOT NULL,
            FOREIGN KEY(invoice_id) REFERENCES invoices(id),
            FOREIGN KEY(product_id) REFERENCES products(id)
        );
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin','manager','viewer') DEFAULT 'viewer',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

    ");

    // Check if columns exist and add them if missing (for existing tables)
    $tablesToAlter = [
        'clients' => [
            ['column' => 'address', 'type' => 'TEXT'],
            ['column' => 'created_at', 'type' => 'DATETIME DEFAULT CURRENT_TIMESTAMP']
        ],
        'products' => [
            ['column' => 'description', 'type' => 'TEXT'],
            ['column' => 'created_at', 'type' => 'DATETIME DEFAULT CURRENT_TIMESTAMP']
        ],
        'invoices' => [
            ['column' => 'status', 'type' => 'TEXT DEFAULT "pending"'],
            ['column' => 'template_id', 'type' => 'INTEGER DEFAULT 1']
        ]
    ];

    foreach ($tablesToAlter as $tableName => $columns) {
        foreach ($columns as $columnDef) {
            try {
                // Check if column exists by trying to select it
                $conn->query("SELECT {$columnDef['column']} FROM {$tableName} LIMIT 1");
            } catch (Exception $e) {
                // Column doesn't exist, so add it
                $colType = $columnDef['type'];

                // SQLite limitation: strip DEFAULT CURRENT_TIMESTAMP for ALTER TABLE
                if (stripos($colType, 'DEFAULT CURRENT_TIMESTAMP') !== false) {
                    $colType = 'DATETIME'; // add column without default
                }

                $conn->exec("ALTER TABLE {$tableName} ADD COLUMN {$columnDef['column']} {$colType}");
                error_log("Added column {$columnDef['column']} to table {$tableName}");

                error_log("Added column {$columnDef['column']} to table {$tableName}");
            }
        }
    }

    // Check if admin user exists, if not create default one
    $checkAdmin = $conn->query("SELECT COUNT(*) as count FROM admins")->fetch(PDO::FETCH_ASSOC);
    
    if ($checkAdmin['count'] == 0) {
        // Create default admin user (using password_hash for security)
        $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO admins (username, password, email) VALUES (:username, :password, :email)");
        $stmt->execute([
            ':username' => 'admin',
            ':password' => $defaultPassword,
            ':email' => 'admin@invoicepro.com'
        ]);
        
        // Add some sample data for demonstration (only if tables are empty)
        $clientCount = $conn->query("SELECT COUNT(*) as count FROM clients")->fetch(PDO::FETCH_ASSOC)['count'];
        $productCount = $conn->query("SELECT COUNT(*) as count FROM products")->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($clientCount == 0) {
            $conn->exec("
                INSERT INTO clients (name, email, phone, address) VALUES 
                ('John Smith', 'john@example.com', '+1234567890', '123 Main St, City, State'),
                ('Acme Corporation', 'contact@acme.com', '+0987654321', '456 Business Ave, City, State');
            ");
        }
        
        if ($productCount == 0) {
            $conn->exec("
                INSERT INTO products (name, description, price) VALUES 
                ('Web Design Service', 'Professional website design and development', 999.99),
                ('SEO Package', 'Search engine optimization services', 499.99),
                ('Consulting Hour', 'Business consulting per hour', 150.00);
            ");
        }
        
        error_log("Default admin user created: username: 'admin', password: 'admin123'");
    }

} catch(PDOException $e) {
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Database Error</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    </head>
    <body class='d-flex align-items-center justify-content-center vh-100 bg-light'>
        <div class='card shadow-sm p-4'>
            <h4 class='text-danger'>Database Connection Failed</h4>
            <p>Please check your configuration. Error:</p>
            <pre style='white-space: pre-wrap;'>" . htmlspecialchars($e->getMessage()) . "</pre>
        </div>
    </body>
    </html>";
    exit;
}

?>