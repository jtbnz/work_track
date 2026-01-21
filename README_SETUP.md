# Work Track - Setup Instructions

## Quick Start

1. **Install PHP dependencies (required for PDF and Email features):**
   ```bash
   # If you don't have Composer installed globally, download it first:
   curl -sS https://getcomposer.org/installer | php

   # Install dependencies
   php composer.phar install
   ```

2. **Start the PHP development server:**
   ```bash
   php -S localhost:8000
   ```

3. **Open your browser and navigate to:**
   ```
   http://localhost:8000/login.php
   ```

4. **Login with default credentials:**
   - Username: `admin`
   - Password: `admin`

## Upgrading

WorkTrack supports in-place upgrades. Your data is preserved when updating.

```bash
# Pull the latest changes
git pull

# Install any new dependencies
php composer.phar install
```

The database schema will be automatically updated when you access the application. Migrations are tracked to avoid re-running.

**Note:** The database file (`database/work_track.db`) is not tracked in git, so your data is never overwritten by updates.

## Features Implemented

✅ **Phase 1-3: Foundation**
- Project structure with organized directories
- SQLite database with full schema
- Authentication system with session management
- Secure login/logout functionality

✅ **Phase 4: Client Management**
- Full CRUD operations for clients
- Client search functionality
- Project association tracking
- Deletion warnings for clients with projects

✅ **Phase 8: Dashboard**
- Statistics cards (total projects, active, completed, clients)
- Quick access navigation cards to all modules
- Recent activity feed from audit log
- Upcoming project deadlines
- Quick action buttons for new projects/clients

✅ **Quoting Module**
- **Suppliers**: Manage material suppliers with contact details
- **Materials**: Inventory management with stock tracking, CSV import
- **Quote Builder**: Create quotes with materials, labour, and misc charges
- **PDF Generation**: Professional quote PDFs with company branding
- **Email Delivery**: Send quotes via SMTP with PDF attachments
- **Settings**: Configure company details, rates, terms, and SMTP

## Database Features

- **Multi-user support** - All users are admins
- **Audit trail** - Tracks all changes with user and timestamp
- **Customizable statuses** - Project statuses with colors
- **Project templates** - Template system ready
- **File attachments** - Database structure prepared

## Navigation Structure

The application includes these main sections:
- **Dashboard** - Overview and quick access
- **Projects** - Project management
- **Clients** - Client management
- **Calendar** - Monthly view
- **Kanban** - Drag-drop board
- **Gantt** - Timeline view
- **Reports** - Analytics & export
- **Quoting** (dropdown)
  - Quotes - Quote listing and management
  - Invoices - Invoice management (coming soon)
  - Materials - Inventory management
  - Suppliers - Supplier management
  - Settings - Company details, rates, SMTP config
- **Admin** (dropdown)
  - Statuses - Custom project statuses
  - Templates - Project templates
  - Users - User management
  - Calendar Sync - iCal integration
  - Backup - Database backup

## File Structure

```
work_track/
├── config/              # Configuration files
├── database/            # SQLite database and migrations
│   └── migrations/      # SQL migration files
├── includes/            # PHP includes
│   ├── models/          # Data models (Client, Project, Quote, etc.)
│   ├── pdf/             # PDF generators (QuotePdf, InvoicePdf)
│   ├── import/          # Import handlers (MaterialImporter)
│   ├── EmailService.php # Email sending service
│   ├── auth.php         # Authentication
│   ├── db.php           # Database connection
│   ├── header.php       # Page header
│   └── footer.php       # Page footer
├── public/              # Static assets
│   ├── css/             # Stylesheets
│   ├── js/              # JavaScript
│   └── images/          # Images
├── uploads/             # File uploads
│   ├── logos/           # Company logos
│   └── pdfs/            # Generated PDFs
├── api/                 # API endpoints
├── quoting/             # Quoting module specs & templates
├── tests/               # Playwright E2E tests
├── vendor/              # Composer dependencies (git-ignored)
├── composer.json        # PHP dependencies
├── index.php            # Dashboard
├── quotes.php           # Quote listing
├── quoteBuilder.php     # Quote editor
├── materials.php        # Materials inventory
├── suppliers.php        # Supplier management
└── quotingSettings.php  # Quoting configuration

```

## Next Steps

The following modules are ready to be implemented:
1. Project Management System
2. Project Status Customization
3. Project Templates
4. Calendar View with drag-drop
5. Kanban Board
6. Gantt Chart
7. Reporting & Export
8. iCal Integration
9. File Attachments

## Quoting Module Setup

The quoting module includes PDF generation and email delivery. These features require Composer dependencies.

### Installing Dependencies

```bash
# Download Composer if not already installed
curl -sS https://getcomposer.org/installer | php

# Install all dependencies (TCPDF for PDFs, PHPMailer for emails)
php composer.phar install
```

### Email Configuration

To send quotes via email, configure SMTP settings:

1. Navigate to **Quoting → Settings** in the menu
2. Scroll down to **Email Settings (SMTP)**
3. Enter your SMTP server details:

| Setting | Example (Gmail) | Example (Office 365) |
|---------|-----------------|----------------------|
| SMTP Host | smtp.gmail.com | smtp.office365.com |
| SMTP Port | 587 | 587 |
| Security | TLS | TLS |
| Username | your-email@gmail.com | your-email@company.com |
| Password | App Password* | Your password |
| From Email | quotes@yourcompany.com | quotes@company.com |

**Gmail users:** You must use an [App Password](https://support.google.com/accounts/answer/185833), not your regular password. Enable 2-factor authentication first, then generate an App Password.

4. Click **Test SMTP Connection** to verify settings
5. Click **Save Settings**

### PDF Generation

PDF generation works automatically once Composer dependencies are installed. Configure your company details in **Quoting → Settings**:

- Company logo (upload JPG, PNG, GIF, or WebP)
- Company name, ABN, address, phone, email, website
- Terms & conditions
- Footer text

## Security Features

- Password hashing with bcrypt
- Session timeout (1 hour)
- CSRF protection ready
- XSS prevention with htmlspecialchars
- SQL injection protection with prepared statements
- Secure session cookies