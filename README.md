# Church Management System

A modern, advanced Church Management System designed for church administrators. Built with clean architecture, modular structure, and reusable components.

## Features

### Core Modules
1. **Dashboard** - Overview with statistics and quick actions
2. **Members** - Complete member profile management with lifecycle tracking
3. **Visitors** - Visitor tracking and follow-up management
4. **Attendance** - Digital check-in and attendance tracking with QR code support
5. **Finance** - Comprehensive financial management
6. **Bulk SMS** - Group messaging and communication
7. **Equipment** - Equipment inventory and management
8. **Reports** - Analytics and reporting system
9. **Settings** - System configuration and user management
10. **Cluster Follow-up** - Cluster management and follow-up tracking

### Advanced Features

#### Membership Management
- **Membership Lifecycle** - Status tracking, new member onboarding, membership class tracking
- **Status Changes History** - Complete audit trail of membership status changes
- **Membership Renewal** - Automated renewal tracking and reminders
- **Transfer Management** - Handle member transfers between clusters/departments
- **Document Attachments** - Upload and manage member documents (baptism certificates, ID cards, etc.)

#### Financial Management
- **Donation Management** - Online giving, multiple payment methods, recurring donations
- **Campaign-Specific Giving** - Track donations for specific campaigns and projects
- **Pledge System** - Pledge tracking, progress monitoring, reminder system
- **Campaign Management** - Create and manage fundraising campaigns with goals
- **Budget Control** - Department-wise budget allocation and tracking
- **Expense Approvals** - Approval workflow for expenses
- **Donor Statements** - Generate statements for donors
- **Financial Reports** - Monthly statements, annual reports, trend analysis

#### Communication
- **Notification System** - Automated notifications for birthdays, anniversaries, milestones
- **Custom Event Reminders** - Schedule reminders for special events
- **Follow-up Notifications** - Automated follow-up reminders for visitors and members
- **Bulk SMS** - Group messaging with template management and scheduling
- **Internal Messaging** - Direct messaging, group chats, file sharing
- **Message Groups** - Create groups for departments, clusters, committees

#### Spiritual Care
- **Prayer Request System** - Submit and manage prayer requests
- **Prayer Chain** - Distribute prayer requests to prayer team
- **Privacy Controls** - Control visibility of prayer requests
- **Response Tracking** - Track prayers and responses

#### Attendance System
- **Digital Check-in** - Multiple check-in methods
- **QR Code Attendance** - Generate QR codes for members
- **Mobile Check-in** - Mobile-friendly check-in options
- **Multiple Service Tracking** - Track attendance for different services
- **Attendance History** - Complete attendance records
- **Automated Absence Notifications** - Notify leaders of member absences

#### Security & Audit
- **Audit Logging** - Complete audit trail of all system actions
- **User Action Logs** - Track all user activities
- **System Changes** - Log all configuration changes
- **Access Logs** - Monitor system access
- **Security Incident Tracking** - Track and respond to security events

## Technology Stack

- **Backend**: PHP with MVC Architecture
- **Frontend**: HTML5, CSS3, JavaScript
- **Database**: MySQL
- **Design**: Custom CSS with gradient green, black, and white theme

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- XAMPP (for local development)

### Setup Instructions

1. **Clone or download the project to your web server directory**
   ```bash
   cd c:\xampp\htdocs\AG
   ```

2. **Create the database**
   - Open phpMyAdmin
   - Create a new database named `church_management`
   - Or run the following SQL command:
     ```sql
     CREATE DATABASE church_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
     ```

3. **Run database migrations**
   ```bash
   php database/migrate.php
   ```

   Or for local XAMPP installs, open:
   ```text
   http://localhost/AG/setup.php
   ```
   The installer now lets each church choose its church name, logo, and theme during setup.

4. **Configure database connection**
   - Edit `app/Config/database.php` if needed
   - Default configuration:
     - Host: localhost
     - Database: church_management
     - Username: root
     - Password: (empty)

5. **Access the application**
   - Open your browser and navigate to: `http://localhost/AG`
   - Login with demo credentials:
     - Email: `admin@church.com`
     - Password: `admin123`
   - After setup, you can still change church name, logo, and theme from `Settings`

## Shared Database On Other PCs

If you want multiple PCs to use this same app with one shared Supabase database, or you want to open the app across the same Wi-Fi/LAN, see:

```text
SHARED_DATABASE_MULTI_PC.md
```

## Directory Structure

```
AG/
├── app/
│   ├── Config/          # Configuration files
│   ├── Controllers/     # Application controllers
│   ├── Helpers/         # Helper classes (Database, Router, Session, View)
│   ├── Middleware/      # Middleware classes
│   ├── Models/          # Data models
│   └── Views/           # View templates
│       ├── auth/        # Authentication views
│       └── layouts/     # Layout templates
├── database/
│   └── migrations/      # Database migration files
├── public/
│   ├── css/             # Stylesheets
│   ├── js/              # JavaScript files
│   └── images/          # Image assets
├── index.php            # Application entry point
└── README.md            # This file
```

## Database Schema

### Tables Created
- `users` - System users and administrators
- `members` - Church members with full profile management
- `visitors` - Visitor records with follow-up tracking
- `attendance` - Attendance records with check-in/out tracking
- `finances` - Financial transactions (income and expenses)
- `clusters` - Cluster/fellowship groups
- `equipment` - Equipment inventory and management
- `departments` - Church departments
- `settings` - System configuration settings
- `membership_history` - Membership lifecycle and status changes
- `notifications` - Automated notifications and reminders
- `donations` - Donation records with recurring support
- `pledges` - Pledge tracking and management
- `campaigns` - Fundraising campaigns with goals
- `prayer_requests` - Prayer request management
- `messages` - Internal messaging system
- `message_groups` - Group messaging
- `group_members` - Group membership management
- `documents` - Document attachments for members
- `audit_logs` - System audit trail
- `budgets` - Budget allocation and tracking
- `expense_approvals` - Expense approval workflow
- `qr_codes` - QR code generation for attendance

## Theme

The system uses a modern gradient green, black, and white color scheme:
- Primary: Gradient green (#10B981 to #059669)
- Secondary: Black (#000000)
- Accent: Light green (#34D399)
- Background: White (#FFFFFF)

## Security Features

- Session-based authentication
- Password protection (demo credentials provided)
- SQL injection prevention (prepared statements)
- XSS protection (output escaping)
- CSRF protection (to be implemented)

## Future Enhancements

- Two-factor authentication
- Enhanced role-based access control
- API endpoints for mobile app integration
- Advanced reporting with interactive charts
- Email notification system integration
- Payment gateway integration (Paystack, Flutterwave, etc.)
- QR code generation and scanning interface
- Advanced analytics dashboard
- Mobile app companion
- Calendar integration
- Event management system
- Volunteer scheduling
- Resource booking system

## Development Guidelines

### Code Style
- Follow PSR-12 coding standards
- Use meaningful variable and function names
- Add comments for complex logic
- Keep functions focused and small

### Architecture
- MVC pattern
- Separation of concerns
- DRY (Don't Repeat Yourself)
- SOLID principles

## Support

For issues or questions, please contact the development team.

## License

This project is proprietary software for church management purposes.

---

**Note**: This is a basic structure implementation. Additional features, forms, validations, and business logic should be added as per specific requirements.
