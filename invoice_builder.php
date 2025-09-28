<?php
// invoice_builder.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'config.php';

// Fetch invoice settings
$settings = $conn->query("SELECT * FROM invoice_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$taxRate = floatval($settings['tax_rate'] ?? 0);
$logo = $settings['logo_path'] ?? '';
$primaryColor = $settings['primary_color'] ?? '#0d6efd';
$secondaryColor = $settings['secondary_color'] ?? '#6c757d';
$companyName = $settings['company_name'] ?? 'My Company';
$companyEmail = $settings['company_email'] ?? '';
$companyPhone = $settings['company_phone'] ?? '';
$companyAddress = $settings['company_address'] ?? '';

// Fetch clients and products
$clients = $conn->query("SELECT * FROM clients")->fetchAll(PDO::FETCH_ASSOC);
$products = $conn->query("SELECT * FROM products")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Drag & Drop Invoice Builder";
include 'header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Components Sidebar -->
        <div class="col-md-3 col-lg-2 bg-light p-3">
            <h5 class="mb-3">Components</h5>
            
            <!-- Layout Components -->
            <div class="mb-4">
                <h6 class="text-muted">Layout</h6>
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary component-draggable" data-type="header" draggable="true">
                        <i class="bi bi-header"></i> Header Section
                    </button>
                    <button class="btn btn-outline-primary component-draggable" data-type="two-column" draggable="true">
                        <i class="bi bi-layout-split"></i> Two Columns
                    </button>
                    <button class="btn btn-outline-primary component-draggable" data-type="table" draggable="true">
                        <i class="bi bi-table"></i> Items Table
                    </button>
                </div>
            </div>

            <!-- Content Components -->
            <div class="mb-4">
                <h6 class="text-muted">Content</h6>
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-success component-draggable" data-type="logo" draggable="true">
                        <i class="bi bi-image"></i> Company Logo
                    </button>
                    <button class="btn btn-outline-success component-draggable" data-type="company-info" draggable="true">
                        <i class="bi bi-building"></i> Company Info
                    </button>
                    <button class="btn btn-outline-success component-draggable" data-type="client-info" draggable="true">
                        <i class="bi bi-person"></i> Client Info
                    </button>
                    <button class="btn btn-outline-success component-draggable" data-type="text" draggable="true">
                        <i class="bi bi-text-paragraph"></i> Text Block
                    </button>
                </div>
            </div>

            <!-- Dynamic Components -->
            <div class="mb-4">
                <h6 class="text-muted">Dynamic Data</h6>
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-info component-draggable" data-type="invoice-number" draggable="true">
                        <i class="bi bi-hash"></i> Invoice Number
                    </button>
                    <button class="btn btn-outline-info component-draggable" data-type="date" draggable="true">
                        <i class="bi bi-calendar"></i> Date
                    </button>
                    <button class="btn btn-outline-info component-draggable" data-type="totals" draggable="true">
                        <i class="bi bi-calculator"></i> Totals
                    </button>
                </div>
            </div>
        </div>

        <!-- Builder Canvas -->
        <div class="col-md-9 col-lg-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center" style="background-color: <?= htmlspecialchars($primaryColor) ?>; color: #fff;">
                    <h4 class="card-title mb-0">Invoice Builder</h4>
                    <div>
                        <button type="button" class="btn btn-light btn-sm" id="previewInvoice">
                            <i class="bi bi-eye"></i> Preview
                        </button>
                        <button type="button" class="btn btn-light btn-sm" id="saveTemplate">
                            <i class="bi bi-save"></i> Save Template
                        </button>
                        <button type="button" class="btn btn-light btn-sm" id="resetCanvas">
                            <i class="bi bi-arrow-clockwise"></i> Reset
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Form Controls -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Select Client</label>
                            <select name="client_id" class="form-select" id="clientSelect">
                                <option value="">Choose a client...</option>
                                <?php foreach ($clients as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Template Name</label>
                            <input type="text" class="form-control" id="templateName" placeholder="My Custom Template">
                        </div>
                    </div>

                    <!-- Builder Canvas Area -->
                    <div id="builderCanvas" class="builder-canvas border rounded p-4 min-vh-75">
                        <div class="empty-state text-center text-muted py-5">
                            <i class="bi bi-arrow-left display-4 d-block mb-3"></i>
                            <h5>Drag components from the sidebar</h5>
                            <p>Start building your invoice by dragging components to this area</p>
                        </div>
                    </div>

                    <!-- Preview Modal -->
                    <div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-xl">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Invoice Preview</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body" id="previewContent">
                                    <!-- Preview content will be loaded here -->
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-primary" id="generatePdf">Generate PDF</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Component Templates (Hidden) -->
<div id="componentTemplates" class="d-none">
    <!-- Header Section -->
    <div data-template="header" class="builder-component">
        <div class="component-header d-flex justify-content-between align-items-center bg-primary text-white p-3 rounded">
            <h4 class="mb-0">Invoice Header</h4>
            <div class="component-actions">
                <button class="btn btn-sm btn-light edit-component" data-type="header">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-light remove-component">
                    <i class="bi bi-x"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Two Column Layout -->
    <div data-template="two-column" class="builder-component">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="column-placeholder border rounded p-3 text-center text-muted h-100">
                    <i class="bi bi-arrow-left"></i> Drop components here
                </div>
            </div>
            <div class="col-md-6">
                <div class="column-placeholder border rounded p-3 text-center text-muted h-100">
                    <i class="bi bi-arrow-right"></i> Drop components here
                </div>
            </div>
        </div>
    </div>

    <!-- Logo Component -->
    <div data-template="logo" class="builder-component">
        <div class="component-content text-center p-3 border rounded bg-light">
            <?php if($logo): ?>
                <img src="<?= htmlspecialchars($logo) ?>" alt="Logo" height="60" class="mb-2">
            <?php else: ?>
                <i class="bi bi-image display-4 text-muted mb-2"></i>
                <p class="text-muted mb-0">Company Logo</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Company Info -->
    <div data-template="company-info" class="builder-component">
        <div class="component-content p-3 border rounded">
            <h5><?= htmlspecialchars($companyName) ?></h5>
            <p class="mb-1"><?= htmlspecialchars($companyAddress) ?></p>
            <p class="mb-1">Email: <?= htmlspecialchars($companyEmail) ?></p>
            <p class="mb-0">Phone: <?= htmlspecialchars($companyPhone) ?></p>
        </div>
    </div>

    <!-- Items Table -->
    <div data-template="table" class="builder-component">
        <div class="component-content">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th width="100">Qty</th>
                        <th width="120">Price</th>
                        <th width="120">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Product/Service description</td>
                        <td>1</td>
                        <td>$0.00</td>
                        <td>$0.00</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Totals Section -->
    <div data-template="totals" class="builder-component">
        <div class="component-content bg-light p-3 rounded">
            <div class="row text-end">
                <div class="col-6">Subtotal:</div>
                <div class="col-6">$0.00</div>
                <div class="col-6">Tax (<?= $taxRate ?>%):</div>
                <div class="col-6">$0.00</div>
                <div class="col-6 fw-bold">Total:</div>
                <div class="col-6 fw-bold">$0.00</div>
            </div>
        </div>
    </div>
</div>

<style>
.builder-canvas {
    background-color: #f8f9fa;
    min-height: 500px;
    transition: all 0.3s ease;
}

.builder-component {
    margin-bottom: 1rem;
    position: relative;
    border: 2px dashed transparent;
    border-radius: 0.375rem;
    transition: all 0.3s ease;
}

.builder-component:hover {
    border-color: #0d6efd;
}

.builder-component.dragging {
    opacity: 0.5;
}

.builder-component .component-actions {
    opacity: 0;
    transition: opacity 0.3s ease;
}

.builder-component:hover .component-actions {
    opacity: 1;
}

.column-placeholder {
    min-height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
}

.empty-state {
    user-select: none;
}

.component-draggable {
    cursor: grab;
}

.component-draggable:active {
    cursor: grabbing;
}

.drop-zone.active {
    background-color: #e3f2fd;
    border-color: #0d6efd !important;
}
</style>

<script>
class InvoiceBuilder {
    constructor() {
        this.canvas = document.getElementById('builderCanvas');
        this.components = [];
        this.init();
    }

    init() {
        this.initDragAndDrop();
        this.initEventListeners();
        this.loadFromStorage();
    }

    initDragAndDrop() {
        // Make components draggable
        document.querySelectorAll('.component-draggable').forEach(component => {
            component.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('text/plain', component.dataset.type);
                component.classList.add('dragging');
            });

            component.addEventListener('dragend', () => {
                component.classList.remove('dragging');
            });
        });

        // Canvas drop zone
        this.canvas.addEventListener('dragover', (e) => {
            e.preventDefault();
            this.canvas.classList.add('drop-zone', 'active');
        });

        this.canvas.addEventListener('dragleave', () => {
            this.canvas.classList.remove('active');
        });

        this.canvas.addEventListener('drop', (e) => {
            e.preventDefault();
            this.canvas.classList.remove('active');
            
            const componentType = e.dataTransfer.getData('text/plain');
            this.addComponent(componentType);
        });
    }

    initEventListeners() {
        // Preview button
        document.getElementById('previewInvoice').addEventListener('click', () => {
            this.previewInvoice();
        });

        // Save template
        document.getElementById('saveTemplate').addEventListener('click', () => {
            this.saveTemplate();
        });

        // Reset canvas
        document.getElementById('resetCanvas').addEventListener('click', () => {
            if(confirm('Are you sure you want to reset the canvas? All unsaved changes will be lost.')) {
                this.resetCanvas();
            }
        });

        // Generate PDF
        document.getElementById('generatePdf').addEventListener('click', () => {
            this.generatePdf();
        });

        // Delegate events for dynamic components
        this.canvas.addEventListener('click', (e) => {
            if(e.target.closest('.remove-component')) {
                this.removeComponent(e.target.closest('.builder-component'));
            }
            
            if(e.target.closest('.edit-component')) {
                this.editComponent(e.target.closest('.builder-component'));
            }
        });
    }

    addComponent(type) {
        const template = document.querySelector(`[data-template="${type}"]`);
        if(!template) return;

        const clone = template.cloneNode(true);
        clone.removeAttribute('data-template');
        clone.dataset.componentType = type;
        clone.dataset.componentId = Date.now();

        // Remove empty state if it exists
        const emptyState = this.canvas.querySelector('.empty-state');
        if(emptyState) {
            emptyState.remove();
        }

        this.canvas.appendChild(clone);
        this.components.push({
            type: type,
            id: clone.dataset.componentId,
            content: clone.innerHTML
        });

        this.saveToStorage();
        this.makeComponentDraggable(clone);
    }

    makeComponentDraggable(component) {
        component.setAttribute('draggable', 'true');
        
        component.addEventListener('dragstart', (e) => {
            e.dataTransfer.setData('text/plain', 'move');
            component.classList.add('dragging');
        });

        component.addEventListener('dragend', () => {
            component.classList.remove('dragging');
        });

        component.addEventListener('dragover', (e) => {
            e.preventDefault();
        });

        component.addEventListener('drop', (e) => {
            e.preventDefault();
            // Handle component reordering logic here
        });
    }

    removeComponent(component) {
        if(confirm('Are you sure you want to remove this component?')) {
            component.remove();
            this.components = this.components.filter(comp => comp.id != component.dataset.componentId);
            this.saveToStorage();
            
            if(this.canvas.children.length === 0) {
                this.showEmptyState();
            }
        }
    }

    editComponent(component) {
        // Implement component-specific editing logic
        const type = component.dataset.componentType;
        alert(`Editing ${type} component - implement custom editor here`);
    }

    showEmptyState() {
        this.canvas.innerHTML = `
            <div class="empty-state text-center text-muted py-5">
                <i class="bi bi-arrow-left display-4 d-block mb-3"></i>
                <h5>Drag components from the sidebar</h5>
                <p>Start building your invoice by dragging components to this area</p>
            </div>
        `;
    }

    previewInvoice() {
        const previewContent = document.getElementById('previewContent');
        const clientId = document.getElementById('clientSelect').value;
        
        // Build preview HTML
        let html = `
            <div class="invoice-preview">
                <div class="container">
                    ${this.canvas.innerHTML}
                </div>
            </div>
        `;

        previewContent.innerHTML = html;
        new bootstrap.Modal(document.getElementById('previewModal')).show();
    }

    saveTemplate() {
        const templateName = document.getElementById('templateName').value.trim();
        if(!templateName) {
            alert('Please enter a template name');
            return;
        }

        const templateData = {
            name: templateName,
            components: this.components,
            client_id: document.getElementById('clientSelect').value,
            created_at: new Date().toISOString()
        };

        // Save to database via AJAX
        fetch('save_invoice_template.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(templateData)
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert('Template saved successfully!');
                localStorage.removeItem('invoiceBuilderData'); // Clear local storage
            } else {
                alert('Error saving template: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error saving template');
        });
    }

    generatePdf() {
        // Implement PDF generation logic
        alert('PDF generation would be implemented here');
    }

    resetCanvas() {
        this.canvas.innerHTML = '';
        this.components = [];
        this.showEmptyState();
        localStorage.removeItem('invoiceBuilderData');
    }

    saveToStorage() {
        const data = {
            components: this.components,
            html: this.canvas.innerHTML
        };
        localStorage.setItem('invoiceBuilderData', JSON.stringify(data));
    }

    loadFromStorage() {
        const saved = localStorage.getItem('invoiceBuilderData');
        if(saved) {
            const data = JSON.parse(saved);
            this.canvas.innerHTML = data.html;
            this.components = data.components || [];
            
            // Reattach event listeners to loaded components
            this.canvas.querySelectorAll('.builder-component').forEach(component => {
                this.makeComponentDraggable(component);
            });
        }
    }
}

// Initialize the builder when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new InvoiceBuilder();
});
</script>

<?php include 'footer.php'; ?>