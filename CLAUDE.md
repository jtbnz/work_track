# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a web-based project tracking tool designed for managing client projects with scheduling, calendar views, and reporting capabilities.

## Technology Stack

- **Frontend**: HTML, CSS, JavaScript
- **Backend**: PHP
- **Database**: SQLite
- **Deployment**: Initially local testing, later to hosting service

## Core Features to Implement

### 1. Authentication System
- User login/logout with session management
- Protect sensitive project data

### 2. Client Management
- CRUD operations for clients with fields: Name, Address, Phone, Email, Remarks
- View active and historical projects per client

### 3. Project Management
- Fields: Title, Details, Client, Start Date, Completion Date, Status (Quote Required/Pending/In Progress/Completed/On Hold/Cancelled), Created Date, Fabric
- Allow new client creation during project definition

### 4. Calendar Views
- **Monthly Calendar**: Classic calendar format with draggable project cards for rescheduling
- **Gantt Chart**: Timeline visualization with daily/weekly/monthly views (timelines only, no dependencies)
- **Kanban Board**: Status columns with draggable cards for status updates
- Click on cards for popup editing in all views

### 5. Reporting & Export
- Sortable/filterable project list by all fields
- iCal export for iPhone calendar integration (read-only)

## Development Commands

Since the project is not yet initialized, here are the commands to set up and run:

```bash
# Initialize PHP project (when starting development)
php -S localhost:8000  # Start local PHP server

# SQLite database operations
sqlite3 database.db  # Access database directly
```

## Architecture Guidelines

### Database Schema
- Design normalized tables for users, clients, and projects
- Ensure proper foreign key relationships
- Include indexes for common query patterns (status, dates, client_id)

### File Structure Recommendation
```
/
├── index.php          # Main entry point
├── config/            # Configuration files
├── database/          # SQLite database and migrations
├── public/            # Static assets (CSS, JS, images)
│   ├── css/
│   ├── js/
│   └── images/
├── includes/          # PHP includes and classes
│   ├── auth.php       # Authentication functions
│   ├── db.php         # Database connection
│   └── models/        # Data models
├── views/             # HTML templates
│   ├── calendar.php
│   ├── kanban.php
│   ├── gantt.php
│   └── projects.php
└── api/               # API endpoints for AJAX calls
```

### Key Implementation Considerations

1. **Drag-and-Drop**: Implement using JavaScript libraries like SortableJS or native HTML5 drag-and-drop API
2. **Calendar Rendering**: Consider using a library like FullCalendar for the monthly view
3. **Responsive Design**: Mobile-first approach for all views
4. **Session Security**: Use secure session handling with proper timeout and regeneration
5. **SQLite Optimization**: Use prepared statements and proper indexing for performance

### Testing Approach

- Test locally using PHP's built-in server
- Create sample data for testing all views and interactions
- Ensure cross-browser compatibility
- Test drag-and-drop on touch devices

## UI/UX Requirements

- Modern, clean interface
- Mobile-friendly responsive design
- Intuitive navigation between views
- Consistent card styling across calendar, kanban, and gantt views
- Clear visual feedback for drag-and-drop operations