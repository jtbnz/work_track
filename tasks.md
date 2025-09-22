# Project Tasks: Web-Based Project Tracking Tool

## Phase 1: Foundation Setup
- [ ] Initialize project structure
  - [ ] Create directory structure (public/, includes/, views/, api/, config/, database/)
  - [ ] Set up index.php as main entry point
  - [ ] Create .htaccess for URL routing
  - [ ] Set up config.php for environment variables

## Phase 2: Database Design & Setup
- [ ] Design database schema
  - [ ] Create users table (id, username, password_hash, email, created_at, last_login)
  - [ ] Create clients table (id, name, address, phone, email, remarks, created_at, updated_at, created_by, updated_by)
  - [ ] Create projects table (id, title, details, client_id, start_date, completion_date, status_id, created_date, fabric, created_by, updated_by, updated_at, template_id)
  - [ ] Create project_statuses table (id, name, color, sort_order, is_active)
  - [ ] Create project_templates table (id, name, default_title, default_details, default_fabric, is_default)
  - [ ] Create project_attachments table (id, project_id, filename, file_path, uploaded_by, uploaded_at)
  - [ ] Create audit_log table (id, table_name, record_id, action, changes_json, user_id, timestamp)
  - [ ] Define foreign key relationships
  - [ ] Create indexes for performance
- [ ] Implement database connection class
  - [ ] Create db.php with PDO connection
  - [ ] Add prepared statement helpers
  - [ ] Implement error handling
- [ ] Create database initialization script
  - [ ] Write SQL schema file
  - [ ] Create initial admin user (username: admin, password: admin)
  - [ ] Create default project statuses (Quote Required, Pending, In Progress, Completed, On Hold, Cancelled)
  - [ ] Create default project template
  - [ ] Create sample data insertion script

## Phase 3: Authentication System
- [ ] Build login functionality
  - [ ] Create login form (views/login.php)
  - [ ] Implement login validation
  - [ ] Set up password hashing (bcrypt)
  - [ ] Create session management
- [ ] Build logout functionality
  - [ ] Implement session destruction
  - [ ] Add logout redirect logic
- [ ] Implement session security
  - [ ] Add session timeout
  - [ ] Implement session regeneration
  - [ ] Create auth middleware for protected pages
- [ ] Create user management
  - [ ] Build user management interface (admin only initially)
  - [ ] Add user creation form
  - [ ] Add validation rules
  - [ ] Implement account creation for multiple admin users

## Phase 4: Client Management Module
- [ ] Create client model
  - [ ] Build Client class with CRUD methods
  - [ ] Add validation methods
- [ ] Build client views
  - [ ] Create client list view
  - [ ] Create add/edit client form
  - [ ] Create client detail view
- [ ] Implement client CRUD operations
  - [ ] Add client creation endpoint
  - [ ] Add client update endpoint
  - [ ] Add client deletion endpoint with project warning
  - [ ] Add client search/filter functionality
  - [ ] Implement audit logging for client changes
- [ ] Link clients to projects
  - [ ] Display active projects per client
  - [ ] Display project history per client

## Phase 5: Project Status Management
- [ ] Create status management interface
  - [ ] Build status list view
  - [ ] Create add/edit status form
  - [ ] Add color picker for status colors
  - [ ] Implement sort order management
  - [ ] Add enable/disable status functionality
- [ ] Implement status CRUD operations
  - [ ] Add status creation endpoint
  - [ ] Add status update endpoint
  - [ ] Add status deletion validation (check for projects using it)
  - [ ] Add status reordering endpoint

## Phase 6: Project Template System
- [ ] Create template management
  - [ ] Build template list view
  - [ ] Create add/edit template form
  - [ ] Set default template selection
  - [ ] Add template preview functionality
- [ ] Implement template CRUD operations
  - [ ] Add template creation endpoint
  - [ ] Add template update endpoint
  - [ ] Add template deletion endpoint
  - [ ] Add "create project from template" functionality
- [ ] Create default template
  - [ ] Define standard project fields
  - [ ] Set default values for common projects

## Phase 7: Project Management Module
- [ ] Create project model
  - [ ] Build Project class with CRUD methods
  - [ ] Add status management methods
  - [ ] Add date validation
- [ ] Build project forms
  - [ ] Create add/edit project form
  - [ ] Add template selection dropdown
  - [ ] Add client selection dropdown
  - [ ] Add inline client creation option
  - [ ] Implement date pickers
  - [ ] Add dynamic status dropdown (from status table)
  - [ ] Add file attachment interface
- [ ] Implement project CRUD operations
  - [ ] Add project creation endpoint
  - [ ] Add project update endpoint with audit logging
  - [ ] Add project deletion endpoint
  - [ ] Add bulk status update endpoint
  - [ ] Implement file attachment upload/download
  - [ ] Create uploads folder structure
  - [ ] Add file size and type validation

## Phase 8: Dashboard
- [ ] Create main dashboard
  - [ ] Design dashboard layout with cards
  - [ ] Create navigation cards for all views (Calendar, Kanban, Gantt, Reports, Clients, Projects)
  - [ ] Add statistics widgets (total projects, active projects, completed this month)
  - [ ] Show recent activity feed from audit log
  - [ ] Display upcoming project deadlines
  - [ ] Add quick action buttons (New Project, New Client)
- [ ] Implement dashboard data fetching
  - [ ] Create dashboard statistics queries
  - [ ] Fetch recent activity from audit log
  - [ ] Get upcoming deadlines
  - [ ] Cache dashboard data for performance

## Phase 9: Calendar View (Monthly)
- [ ] Set up calendar infrastructure
  - [ ] Integrate calendar library (FullCalendar or custom)
  - [ ] Create calendar.php view
  - [ ] Set up calendar grid layout
- [ ] Implement project display
  - [ ] Fetch projects for date range
  - [ ] Render projects as cards on calendar
  - [ ] Add project color coding by status
- [ ] Implement drag-and-drop
  - [ ] Enable draggable project cards
  - [ ] Handle drop events for date changes
  - [ ] Add AJAX update for date changes
  - [ ] Add visual feedback during drag
- [ ] Add popup editing
  - [ ] Create modal for project editing
  - [ ] Implement click handler on cards
  - [ ] Add form validation in modal
  - [ ] Handle save/cancel actions

## Phase 10: Kanban Board View
- [ ] Create Kanban layout
  - [ ] Build column structure for each status
  - [ ] Style status columns
  - [ ] Add responsive layout
- [ ] Implement project cards
  - [ ] Create card template (title, dates)
  - [ ] Fetch and display projects by status
  - [ ] Add card styling and hover effects
- [ ] Enable drag-and-drop between columns
  - [ ] Implement sortable columns
  - [ ] Handle status change on drop
  - [ ] Add AJAX status update
  - [ ] Add animation for card movement
- [ ] Add click-to-edit functionality
  - [ ] Reuse modal from calendar view
  - [ ] Handle status-specific validations

## Phase 11: Gantt Chart View
- [ ] Set up Gantt infrastructure
  - [ ] Choose/implement Gantt library
  - [ ] Create gantt.php view
  - [ ] Set up timeline axis
- [ ] Implement timeline rendering
  - [ ] Calculate project durations
  - [ ] Render project bars on timeline
  - [ ] Add project labels
  - [ ] Color code by status
- [ ] Add view controls
  - [ ] Implement daily view toggle
  - [ ] Implement weekly view toggle
  - [ ] Implement monthly view toggle
  - [ ] Add zoom in/out functionality
- [ ] Add filtering
  - [ ] Filter by status
  - [ ] Filter by client
  - [ ] Filter by date range

## Phase 12: Reporting & Project List
- [ ] Create project list view
  - [ ] Build table layout
  - [ ] Display all project fields
  - [ ] Add pagination
- [ ] Implement sorting
  - [ ] Add sortable column headers
  - [ ] Implement multi-column sort
  - [ ] Add sort direction indicators
- [ ] Implement filtering
  - [ ] Add status filter dropdown
  - [ ] Add client filter dropdown
  - [ ] Add date range picker
  - [ ] Add text search for title/details
- [ ] Add export functionality
  - [ ] Export to CSV
  - [ ] Export to PDF (optional)

## Phase 13: iCal Integration
- [ ] Implement iCal generation
  - [ ] Create iCal format generator
  - [ ] Map project data to iCal events
  - [ ] Set up unique calendar URL per user
- [ ] Create calendar endpoint
  - [ ] Build api/calendar.php endpoint
  - [ ] Add authentication token
  - [ ] Set proper content headers
- [ ] Test iPhone integration
  - [ ] Generate subscription URL
  - [ ] Test in iPhone Calendar app
  - [ ] Verify read-only behavior

## Phase 14: Database Export/Backup
- [ ] Create backup functionality
  - [ ] Build database export interface
  - [ ] Implement SQLite database export
  - [ ] Add scheduled backup reminders
  - [ ] Create restore functionality
- [ ] Add data export options
  - [ ] Export full database backup
  - [ ] Export specific data sets (clients, projects)
  - [ ] Create import functionality for backups

## Phase 15: UI/UX Polish
- [ ] Implement responsive design
  - [ ] Add viewport meta tag
  - [ ] Create mobile breakpoints
  - [ ] Test on various devices
- [ ] Add CSS framework/custom styles
  - [ ] Choose CSS approach (Bootstrap/Tailwind/custom)
  - [ ] Create consistent color scheme
  - [ ] Add loading states
  - [ ] Implement error states
- [ ] Enhance user feedback
  - [ ] Add success/error messages
  - [ ] Implement loading spinners
  - [ ] Add confirmation dialogs
  - [ ] Create tooltips for help

## Phase 16: Testing & Optimization
- [ ] Create test data
  - [ ] Generate sample clients
  - [ ] Generate sample projects
  - [ ] Test edge cases
- [ ] Performance optimization
  - [ ] Optimize database queries
  - [ ] Add database indexes
  - [ ] Implement caching where needed
  - [ ] Minimize JavaScript/CSS
- [ ] Cross-browser testing
  - [ ] Test in Chrome
  - [ ] Test in Firefox
  - [ ] Test in Safari
  - [ ] Test in Edge
- [ ] Security hardening
  - [ ] Implement CSRF protection
  - [ ] Add XSS prevention
  - [ ] Validate all inputs
  - [ ] Secure file uploads with type checking
  - [ ] Implement file access controls

## Phase 17: Deployment Preparation
- [ ] Create deployment configuration
  - [ ] Set up production database config
  - [ ] Configure error logging
  - [ ] Set up backup strategy
- [ ] Create installation documentation
  - [ ] Write setup instructions
  - [ ] Document configuration options
  - [ ] Create user guide
- [ ] Prepare for hosting
  - [ ] Choose hosting provider
  - [ ] Set up domain/subdomain
  - [ ] Configure SSL certificate
  - [ ] Set up deployment process

## Summary of Key Features Based on Requirements:
✅ **Multi-user system** - All users are admins, initial user: admin/admin
✅ **Customizable project statuses** - User-defined statuses with colors
✅ **Client deletion** - Warns about linked projects but allows deletion
✅ **File attachments** - Projects support file uploads in dedicated folders
✅ **Project templates** - Template system with default template
✅ **Dashboard** - Main dashboard with navigation cards and statistics
✅ **Database export** - Full database backup/export functionality
✅ **Audit trail** - Tracks all changes with user and timestamp
✅ **Mobile-friendly** - Responsive web design for mobile devices
❌ **Email notifications** - Not implemented in initial version
❌ **Time tracking** - Not required
❌ **Invoicing** - Not required
❌ **Comments** - Not required
❌ **REST API** - Not in initial version
❌ **Role-based permissions** - All users have admin access