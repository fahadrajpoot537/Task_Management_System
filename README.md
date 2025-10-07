# Task Management System

A complete Laravel 10 and Livewire 3 task management system with role-based access control, email notifications, and responsive design.

## Features

- **Authentication System**: Login and registration with role-based access control
- **Role-Based Permissions**: Super Admin, Admin, Manager, and Employee roles
- **Project Management**: Create and manage projects
- **Task Management**: Create, assign, and track tasks with status updates
- **Team Management**: Assign employees to managers
- **Email Notifications**: Automatic notifications for task assignments
- **File Attachments**: Upload and manage task attachments
- **Activity Logging**: Track all user actions
- **Responsive Design**: Bootstrap 5 with light/dark theme toggle
- **Real-time Updates**: Livewire components for dynamic interactions

## Technology Stack

- **Backend**: Laravel 10
- **Frontend**: Livewire 3, Bootstrap 5
- **Database**: MySQL/PostgreSQL
- **Email**: Laravel Mail (SMTP)
- **Icons**: Bootstrap Icons

## Installation

### Prerequisites

- PHP 8.1 or higher
- Composer
- MySQL/PostgreSQL
- Web server (Apache/Nginx)

### Setup Instructions

1. **Clone or extract the project files to your web server directory**

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Environment Configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure your `.env` file**
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
   ```

5. **Run database migrations and seeders**
   ```bash
   php artisan migrate --seed
   ```

6. **Set up file storage**
   ```bash
   php artisan storage:link
   ```

7. **Start the development server**
   ```bash
   php artisan serve
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
- View all projects and tasks
- Access to permission management and team management

### Admin
- Manage projects and tasks
- View all users
- Cannot manage user roles or permissions

### Manager
- Manage projects and tasks for their team
- View and assign tasks to team members
- Cannot access user management

### Employee
- Create projects and tasks
- View assigned tasks
- Update task status and add notes/attachments

## Project Structure

```
app/
├── Livewire/
│   ├── Auth/           # Authentication components
│   ├── Dashboard/      # Dashboard component
│   ├── Project/        # Project management components
│   ├── Task/           # Task management components
│   ├── Permission/     # Permission management
│   └── Team/           # Team management
├── Mail/               # Email templates
├── Models/             # Eloquent models
└── Providers/          # Service providers

database/
├── migrations/         # Database migrations
└── seeders/           # Database seeders

resources/views/
├── layouts/           # Layout templates
├── livewire/          # Livewire component views
└── emails/            # Email templates
```

## Key Features Explained

### Authentication
- Secure login/registration system
- Password hashing and validation
- Session management

### Role-Based Access Control
- Hierarchical user roles
- Permission-based access control
- Dynamic permission checking

### Task Management
- Create tasks with priority levels
- Assign tasks to users
- Track task status (pending, in-progress, completed)
- Add notes and file attachments
- Due date tracking with overdue indicators

### Email Notifications
- Automatic email notifications for task assignments
- Super admin notifications for new tasks
- Professional email templates

### Activity Logging
- Track all user actions
- Log task creation, updates, and deletions
- Monitor user login/logout

### Responsive Design
- Bootstrap 5 responsive layout
- Light/dark theme toggle
- Mobile-friendly interface
- Professional UI/UX

## Customization

### Adding New Permissions
1. Add permission to `PermissionSeeder`
2. Update role permissions in `RolePermissionSeeder`
3. Use `$user->can('permission_name')` in components

### Adding New Roles
1. Add role to `RoleSeeder`
2. Define permissions in `RolePermissionSeeder`
3. Update authorization logic in components

### Styling
- Modify CSS in layout files
- Bootstrap 5 classes for responsive design
- Custom CSS variables for theme support

## Troubleshooting

### Common Issues

1. **Permission Denied Errors**
   - Check file permissions on storage directory
   - Run `php artisan storage:link`

2. **Database Connection Issues**
   - Verify database credentials in `.env`
   - Ensure database exists
   - Run `php artisan migrate:fresh --seed`

3. **Email Not Working**
   - Check SMTP settings in `.env`
   - Test with a simple mail command
   - Check mail logs

4. **Livewire Components Not Loading**
   - Clear cache: `php artisan cache:clear`
   - Clear config: `php artisan config:clear`
   - Check Livewire configuration

## Security Features

- CSRF protection
- SQL injection prevention
- XSS protection
- Secure password hashing
- Role-based access control
- Input validation and sanitization

## Performance Optimization

- Database indexing
- Efficient queries with Eloquent relationships
- Pagination for large datasets
- Optimized Livewire components
- CDN resources for Bootstrap and icons

## Contributing

1. Fork the project
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Support

For support and questions, please create an issue in the project repository or contact the development team.