InvoicePro – PHP Invoice Management System
📋 Description

InvoicePro is a lightweight PHP-based invoice management system that allows administrators to manage clients, products, and invoices easily. It provides a client portal for customers to view and download their invoices, while also offering admin tools such as exporting data, managing permissions/roles, and generating PDF invoices with multiple templates.

The system uses SQLite by default (no external database server needed) and is styled with Bootstrap 5 for a modern UI.

🚀 Features

Role-based access control (Admin, Manager, Viewer).

Manage clients, products, and invoices.

Client portal with login to view/download invoices.

Multiple invoice templates (Modern, Classic, Minimal, Professional).

Automatic PDF generation with your company branding.

Export data to CSV or XLSX.

SQLite database with auto-setup on first run.

⚙️ Requirements

PHP 7.4+ with PDO extension enabled.

Web server (Apache/Nginx) or local development server (XAMPP/Laragon).

Composer (optional, if using FPDF or external libraries).

📦 Installation

Download or clone the repository into your server root:
Configure permissions so PHP can write to the project folder (needed for SQLite).

Edit config.php if you want to change the database file location or use MySQL instead of SQLite.

Start your server and open the project in your browser (e.g. http://localhost/invoicepro).
🔑 Default Login

After first run, the system creates a default admin account automatically:

Username: admin

Password: admin123

Login via login.php as admin.
