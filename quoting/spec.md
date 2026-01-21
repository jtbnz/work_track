# Quoting Module Specification

## Overview

A comprehensive quoting and invoicing module for WorkTrack that manages suppliers, materials inventory, quote creation, and invoice generation with PDF output and email delivery.

---

## Development Guidelines

### Naming Conventions
- **PHP Variables & Functions**: camelCase (e.g., `$quoteNumber`, `calculateTotals()`)
- **PHP Classes**: PascalCase (e.g., `QuotePDF`, `MaterialImporter`)
- **Database Columns**: snake_case (e.g., `quote_number`, `created_at`)
- **JavaScript Variables & Functions**: camelCase (e.g., `updateTotals()`, `materialList`)
- **CSS Classes**: kebab-case (e.g., `.quote-builder`, `.material-row`)
- **API Endpoints**: snake_case filenames (e.g., `quote_create.php`)
- **Settings Keys**: snake_case (e.g., `labour_rate_standard`)

### Code Patterns
- Follow existing WorkTrack patterns in `includes/models/`
- Use `Database::getInstance()` singleton for all DB operations
- Use prepared statements via `$db->query()`, `$db->fetchAll()`, `$db->insert()`
- Call `logAudit()` for all create/update/delete operations
- Use `baseUrl()` helper for all internal URLs
- Return JSON with proper HTTP status codes from API endpoints
- Include `requireLogin()` at top of all protected pages/APIs

### TODO Markers
- Mark incomplete features with `// TODO:` comment
- Mark deferred functionality with `// TODO: DEFERRED -` comment
- Mark known issues with `// TODO: FIX -` comment

### Error Handling
- Use try/catch for database operations
- Return meaningful error messages in JSON responses
- Log errors with `error_log()` in production
- Display user-friendly messages, not raw errors

## Core Requirements

### Quote-Project Relationship
- Quotes and projects are **independent** with optional linking
- A quote can exist without a project, and vice versa
- Can optionally link a quote to an existing project

### Document Delivery
- **PDF format** for email delivery (non-editable by recipients)
- Professional template design with company branding

### Data Management
- Database storage for suppliers and materials
- CSV import capability for bulk material uploads
- Separate table for misc materials (fixed-price consumables)

### Numbering Format
- Quotes: `Q2026-0001` (year prefix, resets annually)
- Invoices: `INV2026-0001` (year prefix, resets annually)
- Quote revisions tracked: `Q2026-0001 Rev 2`

### Quote Features
- Configurable validity/expiry period (default 30 days)
- Revision tracking when changes are made
- Status workflow: Draft → Sent → Accepted/Declined/Expired → Invoiced

### Invoice Features
- Convert quote to invoice with one click
- Payment terms and due date tracking
- Status workflow: Draft → Sent → Paid/Overdue/Cancelled
- Stock deduction when invoice is created/sent

### Inventory Management
- Track stock levels for materials
- Low stock warnings based on reorder level
- Stock movement audit trail
- Automatic stock deduction on invoicing

### Labour Rates
- Configurable in settings (not hardcoded)
- Standard rate: $75/hr (default)
- Premium rate: $95/hr (default)

### Navigation
- Integrated submenu in main nav: **Quoting** dropdown
  - Quotes
  - Invoices
  - Materials
  - Suppliers

---

## Database Schema

### New Tables

#### suppliers
| Column | Type | Description |
|--------|------|-------------|
| id | INTEGER PK | Auto-increment |
| name | TEXT | Supplier name (unique) |
| contact_name | TEXT | Contact person |
| phone | TEXT | Phone number |
| email | TEXT | Email address |
| address | TEXT | Physical address |
| notes | TEXT | Additional notes |
| is_active | BOOLEAN | Active status (default 1) |
| created_at, updated_at | DATETIME | Timestamps |
| created_by, updated_by | INTEGER FK | User references |

#### materials
| Column | Type | Description |
|--------|------|-------------|
| id | INTEGER PK | Auto-increment |
| supplier_id | INTEGER FK | Reference to suppliers |
| manufacturers_code | TEXT | Product code/SKU |
| item_name | TEXT | Product description |
| cost_excl | DECIMAL(10,2) | Cost excluding GST |
| gst | DECIMAL(10,2) | GST amount |
| cost_incl | DECIMAL(10,2) | Cost including GST |
| sell_price | DECIMAL(10,2) | Retail selling price |
| comments | TEXT | Notes |
| stock_on_hand | DECIMAL(10,2) | Current stock level |
| reorder_quantity | DECIMAL(10,2) | Minimum order quantity |
| reorder_level | DECIMAL(10,2) | Low stock threshold (default 5) |
| unit_of_measure | TEXT | Unit (each, metre, etc.) |
| is_active | BOOLEAN | Active status |
| created_at, updated_at | DATETIME | Timestamps |
| created_by, updated_by | INTEGER FK | User references |

#### misc_materials
| Column | Type | Description |
|--------|------|-------------|
| id | INTEGER PK | Auto-increment |
| name | TEXT | Item name (unique) |
| fixed_price | DECIMAL(10,2) | Fixed unit price |
| is_active | BOOLEAN | Active status |

**Default misc materials:**
- Captain Tape: $4.50
- Cardboard: $7.50
- Thread: $2.50
- Glue: $5.00
- Staples: $5.00

#### quotes
| Column | Type | Description |
|--------|------|-------------|
| id | INTEGER PK | Auto-increment |
| quote_number | TEXT | Q2026-0001 format (unique) |
| revision | INTEGER | Revision number (default 1) |
| client_id | INTEGER FK | Reference to clients |
| project_id | INTEGER FK | Optional reference to projects |
| quote_date | DATE | Quote date |
| expiry_date | DATE | Validity expiry date |
| special_instructions | TEXT | Customer instructions |
| status | TEXT | draft/sent/accepted/declined/expired/invoiced |
| labour_stripping | INTEGER | Minutes |
| labour_patterns | INTEGER | Minutes |
| labour_cutting | INTEGER | Minutes |
| labour_sewing | INTEGER | Minutes |
| labour_upholstery | INTEGER | Minutes |
| labour_assembly | INTEGER | Minutes |
| labour_handling | INTEGER | Minutes |
| labour_rate_type | TEXT | standard/premium |
| labour_rate | DECIMAL(10,2) | Rate at time of quote |
| subtotal_materials | DECIMAL(10,2) | Calculated |
| subtotal_misc | DECIMAL(10,2) | Calculated |
| subtotal_labour | DECIMAL(10,2) | Calculated |
| total_excl_gst | DECIMAL(10,2) | Calculated |
| gst_amount | DECIMAL(10,2) | Calculated |
| total_incl_gst | DECIMAL(10,2) | Calculated |
| pdf_path | TEXT | Path to generated PDF |
| created_at, updated_at | DATETIME | Timestamps |
| created_by, updated_by | INTEGER FK | User references |

#### quote_materials (line items)
| Column | Type | Description |
|--------|------|-------------|
| id | INTEGER PK | Auto-increment |
| quote_id | INTEGER FK | Reference to quotes (cascade delete) |
| material_id | INTEGER FK | Reference to materials |
| item_description | TEXT | Stored description |
| quantity | DECIMAL(10,2) | Quantity |
| unit_cost | DECIMAL(10,2) | Price at time of quote |
| line_total | DECIMAL(10,2) | Calculated |
| sort_order | INTEGER | Display order |

#### quote_misc (misc charges per quote)
| Column | Type | Description |
|--------|------|-------------|
| id | INTEGER PK | Auto-increment |
| quote_id | INTEGER FK | Reference to quotes (cascade delete) |
| misc_material_id | INTEGER FK | Reference to misc_materials |
| name | TEXT | Item name |
| price | DECIMAL(10,2) | Price |
| included | BOOLEAN | Whether included in quote |

#### invoices
| Column | Type | Description |
|--------|------|-------------|
| id | INTEGER PK | Auto-increment |
| invoice_number | TEXT | INV2026-0001 format (unique) |
| quote_id | INTEGER FK | Optional source quote |
| client_id | INTEGER FK | Reference to clients |
| project_id | INTEGER FK | Optional reference to projects |
| invoice_date | DATE | Invoice date |
| due_date | DATE | Payment due date |
| payment_terms | TEXT | e.g., "Net 14" |
| status | TEXT | draft/sent/paid/overdue/cancelled |
| subtotal_materials | DECIMAL(10,2) | Copied or calculated |
| subtotal_misc | DECIMAL(10,2) | Copied or calculated |
| subtotal_labour | DECIMAL(10,2) | Copied or calculated |
| total_excl_gst | DECIMAL(10,2) | Calculated |
| gst_amount | DECIMAL(10,2) | Calculated |
| total_incl_gst | DECIMAL(10,2) | Calculated |
| notes | TEXT | Invoice notes |
| pdf_path | TEXT | Path to generated PDF |
| paid_date | DATE | When paid |
| paid_amount | DECIMAL(10,2) | Amount paid |
| created_at, updated_at | DATETIME | Timestamps |
| created_by, updated_by | INTEGER FK | User references |

#### invoice_materials / invoice_misc
Same structure as quote_materials/quote_misc, referencing invoice_id

#### stock_movements (audit trail)
| Column | Type | Description |
|--------|------|-------------|
| id | INTEGER PK | Auto-increment |
| material_id | INTEGER FK | Reference to materials |
| movement_type | TEXT | invoice/adjustment/import/manual |
| quantity_change | DECIMAL(10,2) | +/- change |
| stock_before | DECIMAL(10,2) | Stock before change |
| stock_after | DECIMAL(10,2) | Stock after change |
| reference_type | TEXT | invoice/quote/manual |
| reference_id | INTEGER | ID of reference document |
| notes | TEXT | Notes |
| created_at | DATETIME | Timestamp |
| created_by | INTEGER FK | User reference |

#### document_sequences
| Column | Type | Description |
|--------|------|-------------|
| id | INTEGER PK | Auto-increment |
| document_type | TEXT | quote/invoice |
| year | INTEGER | Year |
| last_number | INTEGER | Last used number |

---

## Settings Keys

Stored in existing `settings` table:

| Key | Default | Description |
|-----|---------|-------------|
| labour_rate_standard | 75.00 | Standard hourly rate |
| labour_rate_premium | 95.00 | Premium hourly rate |
| quote_validity_days | 30 | Default expiry period |
| gst_rate | 15 | GST percentage |
| invoice_payment_terms | Net 14 | Default payment terms |
| company_name | | For PDF header |
| company_address | | For PDF header |
| company_phone | | For PDF header |
| company_email | | For PDF header |
| company_logo_path | | Logo for PDFs |
| low_stock_threshold | 5 | Default low stock level |

---

## File Structure

```
quoting/
├── spec.md                 # This file
└── materials_template.csv  # Material import template

includes/models/
├── Supplier.php            # Phase 2
├── Material.php            # Phase 2
├── MiscMaterial.php        # Phase 2
├── Quote.php               # Phase 3
├── Invoice.php             # TODO: Phase 5
└── StockMovement.php       # TODO: Phase 5

includes/
├── pdf/                    # Phase 4
│   ├── QuotePdf.php        # Phase 4
│   └── InvoicePdf.php      # TODO: Phase 5
├── import/
│   └── MaterialImporter.php    # Phase 2
└── EmailService.php        # TODO: Phase 6

api/
├── supplierCreate.php      # Phase 2
├── supplierUpdate.php      # Phase 2
├── supplierDelete.php      # Phase 2
├── supplierGet.php         # Phase 2
├── materialCreate.php      # Phase 2
├── materialUpdate.php      # Phase 2
├── materialDelete.php      # Phase 2
├── materialGet.php         # Phase 2
├── materialsSearch.php     # Phase 2
├── materialsImport.php     # Phase 2
├── materialsLowStock.php   # Phase 2
├── miscMaterialCreate.php  # Phase 2
├── miscMaterialUpdate.php  # Phase 2
├── miscMaterialDelete.php  # Phase 2
├── quoteCreate.php         # Phase 3
├── quoteUpdate.php         # Phase 3
├── quoteDelete.php         # Phase 3
├── quoteGet.php            # Phase 3
├── quoteAddMaterial.php    # Phase 3
├── quoteUpdateMaterial.php # Phase 3
├── quoteRemoveMaterial.php # Phase 3
├── quoteUpdateMisc.php     # Phase 3
├── quoteCalculate.php      # Phase 3
├── quoteStatus.php         # Phase 3
├── quoteRevision.php       # Phase 3
├── quoteLinkProject.php    # Phase 3
├── quotePdf.php            # Phase 4
├── quoteEmail.php          # TODO: Phase 6
├── invoiceCreate.php       # TODO: Phase 5
├── invoiceUpdate.php       # TODO: Phase 5
├── invoiceGet.php          # TODO: Phase 5
├── invoiceStatus.php       # TODO: Phase 5
├── invoiceMarkPaid.php     # TODO: Phase 5
├── invoicePdf.php          # TODO: Phase 5
├── invoiceEmail.php        # TODO: Phase 6
├── quoteToInvoice.php      # TODO: Phase 5
└── stockAdjust.php         # TODO: Phase 5

(root)/
├── quotes.php              # Phase 3 - Quote list
├── quoteBuilder.php        # Phase 3 - Quote editor
├── invoices.php            # TODO: Phase 5 - Invoice list
├── invoiceDetail.php       # TODO: Phase 5 - Invoice view/edit
├── materials.php           # Phase 2 - Materials inventory
├── suppliers.php           # Phase 2 - Supplier management
└── quotingSettings.php     # TODO: Phase 6 - Quoting configuration

public/
├── css/quoting.css         # Phase 2
└── js/quoteBuilder.js      # Phase 3

uploads/pdfs/               # Phase 4
├── quotes/                 # Phase 4
└── invoices/               # TODO: Phase 5

database/migrations/
├── 001_create_suppliers_table.sql          # Phase 1
├── 002_create_materials_table.sql          # Phase 1
├── 003_create_misc_materials_table.sql     # Phase 1
├── 004_create_quotes_tables.sql            # Phase 1
├── 005_create_invoices_tables.sql          # Phase 1
├── 006_create_stock_movements_table.sql    # Phase 1
├── 007_create_document_sequences_table.sql # Phase 1
├── 008_insert_default_misc_materials.sql   # Phase 1
├── 009_insert_quoting_settings.sql         # Phase 1
└── 010_create_indexes.sql                  # Phase 1

tests/                      # Phase 7
├── fixtures/
│   ├── auth.ts
│   ├── testData.ts
│   └── resetDb.php
├── e2e/
│   ├── auth.spec.ts
│   ├── suppliers.spec.ts
│   ├── materials.spec.ts
│   ├── quotes.spec.ts
│   ├── invoices.spec.ts
│   └── integration.spec.ts
└── playwright.config.ts
```

---

## External Dependencies

| Library | Purpose | Installation | Phase |
|---------|---------|--------------|-------|
| TCPDF | PDF generation | `composer require tecnickcom/tcpdf` | Phase 4 (done) |
| PHPMailer | Email sending | `composer require phpmailer/phpmailer` | TODO: Phase 6 |

Note: CSV import uses native PHP functions - no external library required.

---

## Quote Builder UI

### Layout
- **Left column**: Client selection, dates, special instructions
- **Right column**: Labour breakdown (7 categories in minutes)
- **Materials section**: Search/add from inventory, editable line items
- **Misc section**: Checkboxes to include/exclude standard misc items
- **Totals section**: Live-updating calculations

### Calculations
```
Labour Hours = Sum of all labour minutes / 60
Labour Cost = Labour Hours × Selected Rate

Materials Total = Sum of (quantity × unit_cost) for all line items
Misc Total = Sum of included misc items

Subtotal (Excl GST) = Materials + Misc + Labour
GST = Subtotal × GST Rate
Total (Incl GST) = Subtotal + GST
```

---

## Status Workflows

### Quote Status
```
Draft → Sent → Accepted → Invoiced
              → Declined
              → Expired (automatic based on expiry_date)
```

### Invoice Status
```
Draft → Sent → Paid
             → Overdue (automatic based on due_date)
             → Cancelled
```

---

## Testing Strategy

### Framework
- **Playwright** for end-to-end testing
- Local PHP development server (port 8000)
- Database reset between test suites

### Test Suites

| Suite | Coverage |
|-------|----------|
| `auth.spec.ts` | Login, logout, session handling |
| `suppliers.spec.ts` | Supplier CRUD, validation |
| `materials.spec.ts` | Materials CRUD, CSV import, stock tracking |
| `quotes.spec.ts` | Quote builder, calculations, revisions, status workflow |
| `invoices.spec.ts` | Invoice creation, payment tracking, stock deduction |
| `integration.spec.ts` | Full quote-to-invoice workflows |

### Key Workflows to Test

1. **Quote Creation Flow**
   - Select client → Add materials → Set labour → Calculate totals → Save

2. **CSV Import Flow**
   - Upload file → Preview → Confirm → Verify data

3. **Quote to Invoice Flow**
   - Create quote → Send → Accept → Convert → Invoice created

4. **Stock Tracking Flow**
   - Set stock → Create invoice → Stock deducted → Verify movement log

5. **Revision Flow**
   - Create quote → Send → Edit → New revision created

### Running Tests
```bash
npm install                    # Install dependencies
npm test                       # Run all tests
npm run test:ui               # Interactive UI mode
npm run test:headed           # See browser during tests
npx playwright test quotes    # Run specific suite
```
