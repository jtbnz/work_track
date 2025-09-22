# Testing Plan - Work Track Project Management System

## Overview
This document outlines the comprehensive testing strategy for the Work Track application, covering all implemented features and user workflows.

## Test Environment Setup

### Prerequisites
1. PHP 8.0+ with SQLite support
2. Web server (Apache/Nginx) or PHP built-in server
3. Modern web browser (Chrome, Firefox, Safari, Edge)
4. Internet connection for testing drag-and-drop features

### Setup Instructions
```bash
# Start the application
php -S localhost:8000

# Navigate to login page
http://localhost:8000/login.php

# Default credentials
Username: admin
Password: admin
```

## Test Categories

### 1. Authentication & Security Tests

#### 1.1 Login Functionality
- [ ] **Valid Login**: Test with default credentials (admin/admin)
- [ ] **Invalid Login**: Test with incorrect username/password
- [ ] **Empty Fields**: Test with missing username or password
- [ ] **Session Timeout**: Wait 1 hour and verify session expires
- [ ] **Session Security**: Verify session regeneration on login
- [ ] **Logout**: Test logout functionality and session destruction

#### 1.2 Access Control
- [ ] **Protected Pages**: Try accessing dashboard without login (should redirect to login)
- [ ] **Direct URL Access**: Test accessing restricted pages via direct URLs
- [ ] **API Endpoints**: Test API endpoints without authentication

### 2. Database & Core Functionality Tests

#### 2.1 Database Integrity
- [ ] **Initial Setup**: Verify database creation and default data
- [ ] **Foreign Keys**: Test referential integrity between tables
- [ ] **Audit Logging**: Verify all changes are logged in audit_log table
- [ ] **User Tracking**: Verify created_by and updated_by fields are populated

#### 2.2 CRUD Operations
- [ ] **Create Operations**: Test creating clients, projects, statuses, templates
- [ ] **Read Operations**: Test listing and viewing records
- [ ] **Update Operations**: Test editing existing records
- [ ] **Delete Operations**: Test deletion with proper validation

### 3. Client Management Tests

#### 3.1 Client CRUD
- [ ] **Create Client**: Add new client with all fields
- [ ] **Create Client (Minimal)**: Add client with only required fields
- [ ] **Edit Client**: Update existing client information
- [ ] **Delete Client (No Projects)**: Delete client without associated projects
- [ ] **Delete Client (With Projects)**: Test warning for clients with projects
- [ ] **Search Clients**: Test client search functionality

#### 3.2 Client Validation
- [ ] **Required Fields**: Test name field validation
- [ ] **Email Format**: Test email validation (if applicable)
- [ ] **Duplicate Handling**: Test behavior with duplicate client names

### 4. Project Status Management Tests

#### 4.1 Status CRUD
- [ ] **Create Status**: Add new project status with color
- [ ] **Edit Status**: Modify existing status name and color
- [ ] **Delete Status (Unused)**: Delete status not used by projects
- [ ] **Delete Status (In Use)**: Test prevention of deleting used status
- [ ] **Toggle Active/Inactive**: Test enable/disable functionality

#### 4.2 Status Ordering
- [ ] **Drag and Drop**: Test reordering statuses via drag and drop
- [ ] **Order Persistence**: Verify order is saved and maintained
- [ ] **Default Order**: Test initial ordering of statuses

### 5. Project Template Tests

#### 5.1 Template CRUD
- [ ] **Create Template**: Add new template with default values
- [ ] **Edit Template**: Modify existing template
- [ ] **Delete Template**: Delete unused template
- [ ] **Default Template**: Test setting/changing default template
- [ ] **Duplicate Template**: Test template duplication functionality

#### 5.2 Template Usage
- [ ] **Create Project from Template**: Test project creation using template
- [ ] **Template Field Population**: Verify template fields populate correctly
- [ ] **Default Template Selection**: Verify default template is pre-selected

### 6. Project Management Tests

#### 6.1 Project CRUD
- [ ] **Create Project (Full)**: Add project with all fields
- [ ] **Create Project (Minimal)**: Add project with only required fields
- [ ] **Create Project from Template**: Use template to create project
- [ ] **Edit Project**: Update existing project information
- [ ] **Delete Project**: Remove project and verify audit logging

#### 6.2 Project Relationships
- [ ] **Client Assignment**: Assign and reassign clients to projects
- [ ] **Status Assignment**: Assign different statuses to projects
- [ ] **Template Assignment**: Link projects to templates
- [ ] **Date Validation**: Test start/end date relationships

#### 6.3 Project Filtering & Search
- [ ] **Filter by Client**: Test client-based filtering
- [ ] **Filter by Status**: Test status-based filtering
- [ ] **Text Search**: Test search in title, details, fabric fields
- [ ] **Date Range Filter**: Test filtering by date ranges
- [ ] **Combined Filters**: Test multiple filters simultaneously

### 7. Dashboard Tests

#### 7.1 Statistics Display
- [ ] **Project Counts**: Verify total, active, completed project counts
- [ ] **Client Count**: Verify total client count
- [ ] **Real-time Updates**: Verify stats update after changes

#### 7.2 Recent Activity
- [ ] **Activity Feed**: Verify recent changes appear in activity log
- [ ] **Activity Details**: Test activity detail display and formatting
- [ ] **Activity Timestamps**: Verify correct time display

#### 7.3 Upcoming Deadlines
- [ ] **Deadline Display**: Verify upcoming deadlines show correctly
- [ ] **Overdue Projects**: Test display of overdue projects
- [ ] **Date Calculations**: Verify "days remaining" calculations

### 8. Calendar View Tests

#### 8.1 Calendar Display
- [ ] **Monthly View**: Test calendar grid display for current month
- [ ] **Navigation**: Test previous/next month navigation
- [ ] **Project Display**: Verify projects appear on correct dates
- [ ] **Multiple Projects**: Test multiple projects on same date

#### 8.2 Drag and Drop
- [ ] **Drag Project**: Test dragging project cards between dates
- [ ] **Date Update**: Verify project dates update when dropped
- [ ] **Visual Feedback**: Test drag state visual indicators
- [ ] **Drop Validation**: Test dropping on invalid targets

#### 8.3 Project Interaction
- [ ] **Click to Edit**: Test clicking project cards opens edit modal
- [ ] **Quick Edit**: Test editing projects from calendar view
- [ ] **Save Changes**: Verify changes save and update calendar

### 9. Kanban Board Tests

#### 9.1 Board Display
- [ ] **Column Layout**: Verify status columns display correctly
- [ ] **Project Cards**: Test project card display in correct columns
- [ ] **Card Information**: Verify all project info displays on cards
- [ ] **Column Counts**: Test project count display per column

#### 9.2 Drag and Drop
- [ ] **Drag Between Columns**: Test moving projects between statuses
- [ ] **Status Update**: Verify project status updates when moved
- [ ] **Visual Feedback**: Test drag state and drop zone indicators
- [ ] **Prevent Invalid Drops**: Test drop validation

#### 9.3 Card Interactions
- [ ] **Edit from Card**: Test editing projects from kanban cards
- [ ] **Card Details**: Verify all project information displays correctly
- [ ] **Responsive Layout**: Test kanban on mobile devices

### 10. API Endpoint Tests

#### 10.1 Authentication
- [ ] **Authenticated Requests**: Test API calls with valid session
- [ ] **Unauthenticated Requests**: Test API calls without authentication
- [ ] **Invalid Session**: Test API calls with expired session

#### 10.2 Project APIs
- [ ] **Get Project**: Test `/api/get_project.php`
- [ ] **Update Project**: Test `/api/update_project.php`
- [ ] **Update Project Date**: Test `/api/update_project_date.php`
- [ ] **Update Project Status**: Test `/api/update_project_status.php`

#### 10.3 API Response Validation
- [ ] **Success Responses**: Verify correct JSON format for successful requests
- [ ] **Error Responses**: Verify proper error handling and messages
- [ ] **HTTP Status Codes**: Test appropriate status codes (200, 400, 401, 404)

### 11. User Interface Tests

#### 11.1 Responsive Design
- [ ] **Desktop (1920x1080)**: Test on large desktop screens
- [ ] **Laptop (1366x768)**: Test on laptop screens
- [ ] **Tablet (768x1024)**: Test on tablet devices
- [ ] **Mobile (375x667)**: Test on mobile phones
- [ ] **Navigation Menu**: Test responsive navigation behavior

#### 11.2 Browser Compatibility
- [ ] **Chrome**: Test all features in Chrome browser
- [ ] **Firefox**: Test all features in Firefox browser
- [ ] **Safari**: Test all features in Safari browser
- [ ] **Edge**: Test all features in Edge browser

#### 11.3 User Experience
- [ ] **Form Validation**: Test client-side form validation
- [ ] **Error Messages**: Test error message display and clarity
- [ ] **Success Messages**: Test success notification display
- [ ] **Loading States**: Test loading indicators where applicable

### 12. Data Validation Tests

#### 12.1 Input Validation
- [ ] **Required Fields**: Test all required field validations
- [ ] **Data Types**: Test date, email, and other field type validations
- [ ] **Length Limits**: Test field length restrictions
- [ ] **Special Characters**: Test handling of special characters

#### 12.2 Business Logic
- [ ] **Date Logic**: Test start date before end date validation
- [ ] **Status Dependencies**: Test status change business rules
- [ ] **Deletion Rules**: Test cascade delete and constraint validations

### 13. Performance Tests

#### 13.1 Load Testing
- [ ] **Large Dataset**: Test with 100+ projects and clients
- [ ] **Calendar Performance**: Test calendar with many projects
- [ ] **Kanban Performance**: Test kanban with full columns
- [ ] **Search Performance**: Test search with large datasets

#### 13.2 User Experience Performance
- [ ] **Page Load Times**: Measure and verify acceptable load times
- [ ] **Drag and Drop Responsiveness**: Test smooth drag operations
- [ ] **AJAX Response Times**: Test API call response times

### 14. Security Tests

#### 14.1 Data Protection
- [ ] **SQL Injection**: Test SQL injection attempts on all inputs
- [ ] **XSS Prevention**: Test cross-site scripting prevention
- [ ] **CSRF Protection**: Test cross-site request forgery protection
- [ ] **File Upload Security**: Test file upload restrictions (if implemented)

#### 14.2 Session Security
- [ ] **Session Hijacking**: Test session security measures
- [ ] **Concurrent Sessions**: Test multiple session handling
- [ ] **Session Data**: Verify no sensitive data in client-side storage

## Test Execution Checklist

### Pre-Testing Setup
- [ ] Fresh database initialization
- [ ] Clear browser cache and cookies
- [ ] Verify server is running correctly
- [ ] Check all file permissions

### Test Data Preparation
- [ ] Create test clients (5-10 clients)
- [ ] Create test projects (20-30 projects across different statuses)
- [ ] Create custom project statuses
- [ ] Create custom project templates

### Post-Testing Verification
- [ ] Database integrity check
- [ ] Audit log verification
- [ ] Performance measurement recording
- [ ] Bug report compilation

## Bug Reporting Template

```
**Bug Title**: [Brief description]
**Severity**: [High/Medium/Low]
**Steps to Reproduce**:
1. Step 1
2. Step 2
3. Step 3

**Expected Result**: [What should happen]
**Actual Result**: [What actually happened]
**Environment**: [Browser, OS, Screen size]
**Additional Notes**: [Screenshots, error messages, etc.]
```

## Test Sign-off

### Functional Testing
- [ ] All CRUD operations tested and working
- [ ] All user workflows tested and working
- [ ] All validation rules tested and working

### Integration Testing
- [ ] Database integration verified
- [ ] API integration verified
- [ ] UI integration verified

### Performance Testing
- [ ] Load testing completed
- [ ] Response time testing completed
- [ ] Mobile performance verified

### Security Testing
- [ ] Authentication testing completed
- [ ] Authorization testing completed
- [ ] Input validation testing completed

### Browser Compatibility
- [ ] Chrome testing completed
- [ ] Firefox testing completed
- [ ] Safari testing completed
- [ ] Edge testing completed

**Test Completion Date**: _______________
**Tested By**: _______________
**Approved By**: _______________