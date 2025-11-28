# Task Management System

A comprehensive enterprise-level Task Management System built with Laravel 10 and Livewire 3. The system integrates task management, human resources, attendance tracking, salary management, lead management (CRM), and real-time communication capabilities in a single, unified platform.

## 📋 Documentation

For comprehensive documentation, please refer to:
- **[Documentation Index](DOCUMENTATION_INDEX.md)** - Master index of all documentation
- **[Project Overview](PROJECT_OVERVIEW.md)** - Executive summary and system capabilities
- **[Features Summary](FEATURES_SUMMARY.md)** - Quick reference of all features
- **[Features & Modules List](FEATURES_MODULES_LIST.md)** - Detailed feature documentation
- **[Deep Technical Analysis](DEEP_ANALYSIS.md)** - In-depth technical documentation
- **[Monthly System Report](MONTHLY_SYSTEM_REPORT.md)** - System capabilities report
- **[Multiple Assignees Feature](MULTIPLE_ASSIGNEES_FEATURE.md)** - Feature-specific documentation
- **[Email Sync Setup](EMAIL_SYNC_SETUP.md)** - Email synchronization configuration
- **[IONOS Email Config](IONOS_EMAIL_CONFIG.md)** - IONOS email server configuration

## ✨ Key Features

### Core Modules

#### 1. Authentication & Authorization
- Secure login/registration system
- Password hashing and validation
- Session management
- Role-based access control (RBAC)

#### 2. User Management
- Complete user lifecycle management
- User profile management with avatar upload
- Manager-employee relationships
- Employment status tracking (probation, permanent)
- Designation management
- User permissions management

#### 3. Project Management
- Create and manage projects
- Project progress tracking
- Project details and overview
- Project-based task organization

#### 4. Task Management
- Create tasks with priority levels, categories, and statuses
- **Multiple Assignees**: Assign same task to multiple employees
- **Recurring Tasks**: Daily, weekly, monthly, or until stopped
- **Time Tracking**: Estimated vs actual hours with variance analysis
- Task notes and comments
- File attachments (upload, download, preview)
- Due date tracking with overdue indicators
- Task approval workflow
- Task revisit functionality
- Task reminders

#### 5. Lead Management (CRM)
- Comprehensive lead management system
- Lead types and status management
- Lead import/export (CSV/Excel)
- Activity tracking per lead
- Lead-to-project association
- Custom data fields (Data1-50, Type1-50)
- Marketing source tracking
- Appointment scheduling
- Transfer and return status management

#### 6. Activity Management
- Activity logging for leads
- Activity types (call, email, meeting, etc.)
- Activity attachments
- Activity replies and threading
- Activity import/export
- Due date and priority management
- Activity assignment

#### 7. Team Management
- Manager-employee relationships
- Team hierarchy visualization
- Team-based task assignment
- Manager dashboard

#### 8. Role & Permission Management
- Hierarchical RBAC system
- Dynamic permission assignment
- Role-based access control
- User-specific permissions

#### 9. Chat & Messaging
- **Real-time Chat**: Channel-based and private messaging
- Channel creation and management
- Channel members management
- Private direct messages
- Typing indicators
- Message reactions
- Message attachments
- Online/offline status
- Slack-like chat interface

#### 10. Attendance Management
- Daily attendance tracking
- Check-in/check-out times
- Late minutes and early departure tracking
- Hours worked calculation
- **Biometric Integration**: Automatic attendance fetching from Zkteco devices
- Attendance viewer with filters
- User-specific attendance details
- Attendance records with bonuses and incentives

#### 11. Salary Management
- Automatic salary calculations
- Monthly salary tracking
- Bonuses and incentives management
- Salary summary reports
- Printable salary summaries
- Employment status-based calculations
- Grace periods and penalties

#### 12. Probation Management
- Automated probation tracking
- Probation end date management
- Automatic conversion to permanent status
- Probation period monitoring

#### 13. Email Notifications
- **10+ Email Types** with role-based templates:
  - Task Created
  - Task Assigned (to Employee, Manager, Super Admin)
  - Task Status Changed
  - Task Updated
  - Task Completed
  - Task Reminder
  - Task Revisit Notification
  - Task Note/Comment Added
  - Activity Email
  - Monthly Salary Summary
  - User Invitation
- Role-based recipient selection
- Email validation and error handling

#### 14. Email Synchronization
- IMAP email sync functionality
- Email-to-activity conversion
- Automated email processing
- Email logging and tracking

#### 15. File & Attachments
- Upload, download, and preview functionality
- Support for PDF, images, videos, and documents
- Attachment management
- Comment attachments
- Message attachments

#### 16. Activity Logging
- Complete audit trail of user actions
- Log task creation, updates, and deletions
- Monitor user login/logout
- System activity tracking

#### 17. Dashboard
- Role-based system overview
- Statistics and analytics
- Task overview
- Project progress
- Attendance summary
- Recent activities

#### 18. Settings & Theme
- System configuration
- Light/dark theme toggle
- User preferences
- Theme persistence

#### 19. Task Configuration
- Task Status Management (customizable statuses)
- Task Priority Management (customizable priorities)
- Task Category Management (customizable categories)

#### 20. Zkteco Integration
- Biometric device integration
- Automatic attendance fetching
- Device user mapping
- Attendance synchronization

#### 21. Excel/Spreadsheet Support
- Lead import/export
- Activity import/export
- Data manipulation with PHPSpreadsheet

#### 22. Real-time Updates
- Livewire 3 real-time components
- Laravel Echo integration
- Pusher support for real-time events

### Advanced Features

- ✅ **Multiple Assignees**: Assign same task to multiple employees
- ✅ **Recurring Tasks**: Daily, weekly, monthly, or until stopped
- ✅ **Time Tracking**: Estimated vs actual hours with variance analysis
- ✅ **Biometric Integration**: Automatic attendance fetching from Zkteco devices
- ✅ **Salary Calculations**: Automatic calculations with grace periods and penalties
- ✅ **Real-time Chat**: Channel-based and private messaging with typing indicators
- ✅ **Email Notifications**: 10+ email types with role-based templates
- ✅ **File Management**: Upload, download, preview (PDF, images, videos)
- ✅ **Responsive Design**: Bootstrap 5 with light/dark theme toggle
- ✅ **Lead Management**: Complete CRM functionality with import/export
- ✅ **Activity Tracking**: Comprehensive activity logging and management
- ✅ **Email Sync**: IMAP integration for automated email processing

## Technology Stack

### Backend
- **Framework**: Laravel 10
- **PHP**: 8.1+
- **Database**: MySQL/PostgreSQL
- **Authentication**: Laravel Sanctum

### Frontend
- **Framework**: Livewire 3
- **CSS Framework**: Bootstrap 5
- **Build Tool**: Vite 7
- **CSS Preprocessor**: Tailwind CSS 4
- **Icons**: Bootstrap Icons
- **JavaScript**: Axios, Laravel Echo, Pusher.js

### Additional Libraries
- **Zkteco Integration**: jmrashed/zkteco (^1.2)
- **Spreadsheet**: PHPSpreadsheet (^1.29)
- **HTTP Client**: Guzzle (^7.2)

### Development Tools
- **Testing**: PHPUnit (^10.1)
- **Code Quality**: Laravel Pint (^1.0)
- **Mocking**: Mockery (^1.4.4)

## Installation

### Prerequisites

- PHP 8.1 or higher
- Composer
- Node.js and npm (for frontend assets)
- MySQL/PostgreSQL
- Web server (Apache/Nginx) or PHP built-in server
- IMAP extension (for email sync functionality)

### Setup Instructions

1. **Clone or extract the project files to your web server directory**
   ```bash
   cd /path/to/your/web/directory
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment Configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure your `.env` file**
   ```env
   APP_NAME="Task Management System"
   APP_ENV=local
   APP_DEBUG=true
   APP_URL=http://localhost

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=task_management
   DB_USERNAME=root
   DB_PASSWORD=

   MAIL_MAILER=smtp
   MAIL_HOST=your-smtp-host
   MAIL_PORT=587
   MAIL_USERNAME=your-email
   MAIL_PASSWORD=your-password
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS="noreply@yourdomain.com"
   MAIL_FROM_NAME="${APP_NAME}"

   # For real-time features (optional)
   BROADCAST_DRIVER=pusher
   PUSHER_APP_ID=your-pusher-app-id
   PUSHER_APP_KEY=your-pusher-key
   PUSHER_APP_SECRET=your-pusher-secret
   PUSHER_APP_CLUSTER=mt1

   # For Zkteco integration (optional)
   ZKTECO_IP=192.168.1.201
   ZKTECO_PORT=4370
   ```

6. **Run database migrations and seeders**
   ```bash
   php artisan migrate --seed
   ```

7. **Set up file storage**
   ```bash
   php artisan storage:link
   ```

8. **Build frontend assets**
   ```bash
   npm run build
   # Or for development:
   npm run dev
   ```

9. **Start the development server**
   ```bash
   php artisan serve
   ```
   
   Or use the built-in server with Vite:
   ```bash
   # Terminal 1: Laravel server
   php artisan serve
   
   # Terminal 2: Vite dev server (for development)
   npm run dev
   ```

## Default Login Credentials

After running the seeders, you can log in with these default accounts:

### Super Admin
- **Email**: superadmin@example.com
- **Password**: password

### Manager
- **Email**: manager@example.com
- **Password**: password

### Employee
- **Email**: employee1@example.com
- **Password**: password

## User Roles and Permissions

### Super Admin
- Full system access
- Manage all users, roles, and permissions
- View all projects, tasks, leads, and activities
- Access to permission management and team management
- System configuration access
- Salary management
- Attendance management

### Admin
- Manage projects, tasks, leads, and activities
- View all users
- Cannot manage user roles or permissions
- Access to reports and analytics

### Manager
- Manage projects and tasks for their team
- View and assign tasks to team members
- View team attendance
- Cannot access user management
- View team leads and activities

### Employee
- Create projects and tasks
- View assigned tasks
- Update task status and add notes/attachments
- View own attendance
- View assigned leads and activities
- Access to chat and messaging

## Project Structure

```
app/
├── Console/
│   └── Commands/          # Artisan commands
├── Events/                # Event classes
├── Exceptions/            # Exception handlers
├── Http/
│   ├── Controllers/       # HTTP controllers
│   ├── Kernel.php         # HTTP kernel
│   └── Middleware/        # Custom middleware
├── Livewire/              # Livewire components
│   ├── Admin/             # Admin components
│   ├── Attendance/        # Attendance management
│   ├── Auth/              # Authentication
│   ├── Chat/              # Chat components
│   ├── Dashboard/         # Dashboard
│   ├── Employee/          # Employee components
│   ├── Manager/           # Manager components
│   ├── Permission/        # Permission management
│   ├── Project/           # Project management
│   ├── Task/              # Task management
│   ├── Team/              # Team management
│   ├── User/              # User management
│   └── Zkteco/            # Zkteco integration
├── Mail/                  # Email templates (14 types)
├── Models/                # Eloquent models (24 models)
├── Providers/             # Service providers
└── Services/              # Business logic services
    ├── EmailNotificationService.php
    ├── EmailSyncService.php
    ├── PasswordGeneratorService.php
    └── RecurringTaskService.php

database/
├── factories/             # Model factories
├── migrations/            # Database migrations (77+)
└── seeders/               # Database seeders (12)

resources/
├── css/                   # Stylesheets
├── js/                    # JavaScript files
└── views/                 # Blade templates
    ├── layouts/           # Layout templates
    ├── livewire/          # Livewire component views
    └── emails/            # Email templates

routes/
├── api.php                # API routes
├── channels.php           # Broadcast channels
├── console.php            # Console routes
└── web.php                # Web routes

config/
├── app.php                # Application config
├── database.php           # Database config
├── mail.php               # Mail config
├── livewire.php           # Livewire config
├── zkteco.php             # Zkteco config
└── ...                    # Other config files

public/
├── uploads/               # User uploads
├── TMSleads/              # Lead attachments
└── ...                    # Public assets
```

## Key Features Explained

### Authentication
- Secure login/registration system
- Password hashing and validation
- Session management
- Remember me functionality

### Role-Based Access Control
- Hierarchical user roles (Super Admin, Admin, Manager, Employee)
- Permission-based access control
- Dynamic permission checking
- User-specific permissions override

### Task Management
- Create tasks with priority levels, categories, and statuses
- Multiple assignees support
- Recurring task automation
- Time tracking (estimated vs actual)
- Task notes and comments
- File attachments
- Due date tracking with overdue indicators
- Task approval workflow
- Task revisit functionality
- Task reminders

### Lead Management (CRM)
- Comprehensive lead tracking
- Lead types and status management
- Import/export functionality
- Activity tracking per lead
- Custom data fields
- Marketing source tracking
- Appointment scheduling

### Activity Management
- Activity logging for leads
- Multiple activity types
- Activity attachments
- Activity replies and threading
- Import/export capabilities

### Email Notifications
- 14 different email notification types
- Role-based recipient selection
- Professional email templates
- Email validation and error handling
- Automatic notifications for:
  - Task creation, assignment, updates
  - Status changes
  - Comments and notes
  - Reminders and revisits
  - Activities
  - Salary summaries

### Chat & Messaging
- Real-time channel-based chat
- Private direct messages
- Typing indicators
- Message reactions
- File attachments
- Online/offline status
- Slack-like interface

### Attendance Management
- Daily attendance tracking
- Check-in/check-out times
- Late and early departure tracking
- Hours worked calculation
- Biometric device integration (Zkteco)
- Attendance reports
- Bonuses and incentives tracking

### Salary Management
- Automatic salary calculations
- Monthly salary tracking
- Bonuses and incentives
- Salary summary reports
- Printable summaries
- Employment status-based calculations

## Customization

### Adding New Permissions
1. Add permission to `PermissionSeeder`
2. Update role permissions in `RolePermissionSeeder`
3. Use `$user->can('permission_name')` in components

### Adding New Roles
1. Add role to `RoleSeeder`
2. Define permissions in `RolePermissionSeeder`
3. Update authorization logic in components

### Adding New Email Notifications
1. Create new Mail class in `app/Mail/`
2. Add method to `EmailNotificationService`
3. Create email template in `resources/views/emails/`
4. Call service method from appropriate component

### Styling
- Modify CSS in `resources/css/app.css`
- Bootstrap 5 classes for responsive design
- Tailwind CSS 4 for utility classes
- Custom CSS variables for theme support
- Theme toggle in `app/Livewire/ThemeToggle.php`

### Zkteco Configuration
- Configure device IP and port in `.env`
- Map device user IDs to system users
- Set up scheduled task for automatic sync

## Troubleshooting

### Common Issues

1. **Permission Denied Errors**
   - Check file permissions on `storage/` and `bootstrap/cache/` directories
   - Run `php artisan storage:link`
   - Ensure web server has write permissions

2. **Database Connection Issues**
   - Verify database credentials in `.env`
   - Ensure database exists
   - Run `php artisan migrate:fresh --seed` (⚠️ This will delete all data)

3. **Email Not Working**
   - Check SMTP settings in `.env`
   - Verify IMAP extension is enabled for email sync
   - Test with a simple mail command
   - Check mail logs in `storage/logs/laravel.log`

4. **Livewire Components Not Loading**
   - Clear cache: `php artisan cache:clear`
   - Clear config: `php artisan config:clear`
   - Clear view cache: `php artisan view:clear`
   - Rebuild assets: `npm run build`

5. **Vite Assets Not Loading**
   - Run `npm install` to ensure dependencies are installed
   - Run `npm run build` for production or `npm run dev` for development
   - Ensure Vite dev server is running in development mode

6. **Zkteco Integration Issues**
   - Verify device IP and port in `.env`
   - Check network connectivity to device
   - Ensure device user IDs are mapped correctly
   - Check Zkteco logs

7. **Email Sync Not Working**
   - Verify IMAP extension is enabled: `php -m | grep imap`
   - Check email credentials in `.env`
   - Review email sync logs
   - See `EMAIL_SYNC_SETUP.md` for detailed setup

## Security Features

- CSRF protection on all forms
- SQL injection prevention (Eloquent ORM)
- XSS protection (Blade templating)
- Secure password hashing (bcrypt)
- Role-based access control
- Input validation and sanitization
- File upload validation
- Session security
- Rate limiting on authentication

## Performance Optimization

- Database indexing on frequently queried columns
- Efficient queries with Eloquent relationships
- Eager loading to prevent N+1 queries
- Pagination for large datasets
- Optimized Livewire components
- Asset compilation and minification with Vite
- CDN resources for Bootstrap and icons
- Caching for configuration and routes

## Development

### Running Tests
```bash
php artisan test
```

### Code Formatting
```bash
./vendor/bin/pint
```

### Building Assets
```bash
# Production build
npm run build

# Development with hot reload
npm run dev
```

### Database Seeding
```bash
# Seed all data
php artisan db:seed

# Seed specific seeder
php artisan db:seed --class=UserSeeder
```

## Contributing

1. Fork the project
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Make your changes
4. Test thoroughly
5. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
6. Push to the branch (`git push origin feature/AmazingFeature`)
7. Open a Pull Request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 📚 Additional Documentation

- **[Documentation Index](DOCUMENTATION_INDEX.md)** - Complete documentation navigation
- **[Project Overview](PROJECT_OVERVIEW.md)** - Executive summary and system capabilities
- **[Features Summary](FEATURES_SUMMARY.md)** - Quick feature reference
- **[Features & Modules List](FEATURES_MODULES_LIST.md)** - Detailed feature documentation
- **[Deep Technical Analysis](DEEP_ANALYSIS.md)** - Technical deep dive
- **[Monthly System Report](MONTHLY_SYSTEM_REPORT.md)** - System capabilities report
- **[Multiple Assignees Feature](MULTIPLE_ASSIGNEES_FEATURE.md)** - Feature documentation
- **[Email Sync Setup](EMAIL_SYNC_SETUP.md)** - Email synchronization configuration
- **[IONOS Email Config](IONOS_EMAIL_CONFIG.md)** - IONOS email server configuration
- **[Enable IMAP XAMPP](ENABLE_IMAP_XAMPP.md)** - IMAP extension setup for XAMPP

## Support

For support and questions, please create an issue in the project repository or contact the development team.

---

**System Version**: Laravel 10 + Livewire 3  
**Status**: ✅ Production Ready  
**Total Modules**: 22+ Major Modules  
**Total Models**: 24 Eloquent Models  
**Total Email Types**: 14 Notification Types  
**Total Migrations**: 77+ Database Migrations  
**Total Seeders**: 12 Database Seeders
