# WorkTrack - Project Management System

A comprehensive web-based project tracking and management application designed for businesses to efficiently manage clients, projects, and workflows.

**Repository**: [https://github.com/jtbnz/work_track](https://github.com/jtbnz/work_track)

## 🌟 Features

### ✅ Implemented Features

- **🔐 Authentication System**
  - Secure login/logout with session management
  - Multi-user support (all users are admins)
  - Session timeout and security measures

- **👥 Client Management**
  - Complete CRUD operations for clients
  - Client search and filtering
  - Project association tracking
  - Deletion warnings for clients with active projects

- **📋 Project Management**
  - Full project lifecycle management
  - Template-based project creation
  - Customizable project statuses with colors
  - Client assignment and date tracking
  - Project search and filtering

- **🎨 Project Status System**
  - Customizable status creation and management
  - Color-coded status indicators
  - Drag-and-drop status reordering
  - Usage tracking and validation

- **📝 Project Templates**
  - Reusable project templates
  - Default template system
  - Template duplication
  - Pre-filled project creation

- **📊 Dashboard**
  - Real-time project statistics
  - Recent activity feed with audit trail
  - Upcoming project deadlines
  - Quick navigation to all modules

- **📅 Calendar View**
  - Monthly project visualization
  - Drag-and-drop date rescheduling
  - Click-to-edit project details
  - Multi-project date support

- **📈 Kanban Board**
  - Status-based project organization
  - Drag-and-drop status updates
  - Visual project cards with details
  - Real-time status changes

- **📊 Gantt Chart**
  - Timeline visualization with day/week/month views
  - Date picker for navigation
  - Project duration bars with color coding
  - Filter by client and status
  - Today button for quick navigation

- **📊 Reports & Analytics**
  - Project filtering and search
  - Export to CSV functionality
  - Status and client filtering
  - Date range selection

- **👤 User Management**
  - Create and manage admin users
  - Password reset functionality
  - Last login tracking
  - User activity monitoring

- **💾 Backup System**
  - Database backup and download
  - One-click restore functionality
  - Automatic backup file generation
  - Secure backup storage

- **📱 Calendar Integration**
  - iCal/ICS feed for external calendars
  - iOS/iPhone subscription support
  - Google Calendar and Outlook compatibility
  - Automatic sync with project updates
  - Step-by-step setup instructions in admin area

### 🔧 Technical Features

- **Database Management**
  - SQLite database with full relational schema
  - Automatic audit logging for all changes
  - Foreign key constraints and data integrity
  - Prepared statements for security

- **Security**
  - Password hashing with bcrypt
  - Session management and timeout
  - CSRF protection ready
  - XSS prevention with input sanitization
  - SQL injection protection

- **User Experience**
  - Responsive design for all devices
  - Modern, clean interface
  - Real-time notifications
  - Smooth animations and transitions

## 🚀 Quick Start

### Prerequisites
- PHP 8.0 or higher
- SQLite support
- Web server (Apache/Nginx) or PHP built-in server

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/jtbnz/work_track.git
   cd work_track
   ```

2. **Initialize the database**
   ```bash
   php database/init.php
   ```

3. **Start the development server**
   ```bash
   php -S localhost:8000
   ```

4. **Access the application**
   - Open your browser to `http://localhost:8000/login.php`
   - Login with default credentials:
     - **Username**: `admin`
     - **Password**: `admin`

### Deployment to Production

#### Subdirectory Installation
If deploying to a subdirectory (e.g., `example.com/work_track/`):

1. Upload all files to your subdirectory
2. Ensure the web server user has write permissions for:
   - `database/` directory
   - `uploads/` directory
   - `backups/` directory (if it exists)
3. The application automatically detects subdirectory installation
4. All URLs will be relative to the subdirectory

#### Root Installation
For root domain installation (e.g., `example.com`):

1. Upload all files to your document root
2. Follow the same permission settings as above
3. Configure your web server to point to the application root

## 🏗️ Project Structure

```
work_track/
├── config/              # Configuration files
│   └── config.php       # Main configuration
├── database/            # Database files and schema
│   ├── schema.sql       # Database schema
│   ├── init.php         # Database initialization
│   └── work_track.db    # SQLite database (created)
├── includes/            # PHP includes and utilities
│   ├── models/          # Data models
│   │   ├── Client.php
│   │   ├── Project.php
│   │   ├── ProjectStatus.php
│   │   └── ProjectTemplate.php
│   ├── auth.php         # Authentication system
│   ├── db.php           # Database connection
│   ├── header.php       # Page header
│   └── footer.php       # Page footer
├── public/              # Static assets
│   ├── css/
│   │   └── style.css    # Main stylesheet
│   └── js/
│       └── main.js      # JavaScript utilities
├── api/                 # API endpoints
│   ├── get_project.php
│   ├── update_project.php
│   ├── update_project_date.php
│   ├── update_project_status.php
│   ├── calendar_feed.php # iCal feed endpoint
│   ├── upload_file.php
│   ├── download_file.php
│   └── delete_file.php
├── uploads/             # File uploads directory
├── views/               # View templates (future use)
├── index.php            # Dashboard
├── login.php            # Login page
├── logout.php           # Logout handler
├── clients.php          # Client management
├── projects.php         # Project management
├── status.php           # Status management
├── templates.php        # Template management
├── calendar.php         # Calendar view
├── kanban.php           # Kanban board
├── gantt.php            # Gantt chart view
├── reports.php          # Reports and analytics
├── users.php            # User management
├── backup.php           # Backup management
├── calendar-sync.php    # Calendar sync instructions
└── .htaccess            # URL routing and security
```

## 📱 User Interface

### Dashboard
- **Statistics Overview**: Total projects, active projects, completed projects, client count
- **Quick Navigation**: Cards linking to all major modules
- **Recent Activity**: Audit trail of recent changes
- **Upcoming Deadlines**: Projects approaching completion dates

### Project Management
- **Project List**: Filterable and searchable project table
- **Create/Edit**: Form-based project creation with template support
- **Status Tracking**: Visual status indicators with colors
- **Client Assignment**: Link projects to clients

### Client Management
- **Client Directory**: Searchable client list with project counts
- **CRUD Operations**: Create, edit, and delete clients
- **Project Association**: View all projects for each client

### Calendar View
- **Monthly Grid**: Visual calendar showing project timelines
- **Drag-and-Drop**: Reschedule projects by dragging between dates
- **Project Cards**: Color-coded cards showing project details

### Kanban Board
- **Status Columns**: Organized by project status
- **Drag-and-Drop**: Move projects between statuses
- **Visual Cards**: Rich project information display

## 🔒 Security Features

- **Authentication**: Secure login with password hashing
- **Session Management**: Automatic timeout and regeneration
- **Input Validation**: Server-side validation for all inputs
- **SQL Injection Prevention**: Prepared statements throughout
- **XSS Protection**: HTML escaping for all outputs
- **Access Control**: Authentication required for all pages

## 🗄️ Database Schema

### Key Tables
- **users**: User authentication and management
- **clients**: Client information and contact details
- **projects**: Project details with relationships
- **project_statuses**: Customizable status definitions
- **project_templates**: Reusable project templates
- **project_attachments**: File attachment support (ready)
- **audit_log**: Complete change tracking

### Relationships
- Projects belong to clients (optional)
- Projects have statuses and templates
- All changes are logged with user attribution
- Foreign key constraints ensure data integrity

## 🎨 Customization

### Project Statuses
- Create custom status names and colors
- Reorder statuses via drag-and-drop
- Track usage across projects
- Prevent deletion of statuses in use

### Project Templates
- Define default project structures
- Set default titles, descriptions, and fabric types
- Duplicate existing templates
- Set system-wide default template

## 🚧 Future Enhancements

### Recently Implemented Features
- **📊 Gantt Chart View**: Timeline visualization with day/week/month views and date picker
- **📈 Reports & Analytics**: Project reporting with export capabilities
- **📎 File Attachments**: Document upload and management for projects
- **📱 iCal Integration**: Calendar subscription for iOS/Android/Outlook with sync instructions
- **💾 Database Backup**: Backup and restore functionality with download options
- **👤 User Management**: Interface for creating and managing admin users
- **🔗 Subdirectory Support**: Works in both root and subdirectory installations

### Technical Improvements
- **API Documentation**: REST API with proper documentation
- **Automated Testing**: PHPUnit test suite
- **Performance Optimization**: Caching and query optimization
- **Email Notifications**: Project deadline and change alerts

## 🧪 Testing

A comprehensive testing plan is available in `TESTING_PLAN.md` covering:
- Functional testing for all features
- User interface and experience testing
- Security and performance testing
- Browser compatibility testing
- API endpoint testing

## 🤝 Contributing

1. Fork the repository from [https://github.com/jtbnz/work_track](https://github.com/jtbnz/work_track)
2. Create a feature branch (`git checkout -b feature/new-feature`)
3. Commit your changes (`git commit -am 'Add new feature'`)
4. Push to the branch (`git push origin feature/new-feature`)
5. Create a Pull Request

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🆘 Support

For support, issues, or feature requests:
1. Check the `TESTING_PLAN.md` for common issues
2. Review the codebase documentation in `CLAUDE.md`
3. Create an issue at [https://github.com/jtbnz/work_track/issues](https://github.com/jtbnz/work_track/issues)

## 🎯 Use Cases

### Perfect For:
- **Small to Medium Businesses**: Project tracking and client management
- **Freelancers**: Individual project organization
- **Teams**: Collaborative project management
- **Service Providers**: Client work tracking
- **Creative Agencies**: Campaign and project management

### Key Benefits:
- **No External Dependencies**: Self-hosted with SQLite
- **Quick Setup**: Running in minutes
- **Customizable**: Adapt to your workflow
- **Audit Trail**: Complete change history
- **Multi-View**: Calendar, Kanban, and list views
- **Responsive**: Works on all devices

## 📊 Technical Specifications

- **Backend**: PHP 8.0+
- **Database**: SQLite 3
- **Frontend**: HTML5, CSS3, JavaScript (ES6)
- **Architecture**: MVC pattern with model classes
- **Security**: Session-based authentication with CSRF protection
- **Performance**: Optimized queries with prepared statements
- **Compatibility**: Modern browsers (Chrome, Firefox, Safari, Edge)
