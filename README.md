# OJTRoute System

A comprehensive web-based On-the-Job Training (OJT) management system designed for educational institutions to streamline the monitoring and management of student internships.

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [System Requirements](#system-requirements)
- [Installation](#installation)
- [User Roles](#user-roles)
- [Key Features by Role](#key-features-by-role)
- [Technology Stack](#technology-stack)
- [Project Structure](#project-structure)
- [Configuration](#configuration)
- [Usage Guide](#usage-guide)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)
- [License](#license)

## 🎯 Overview

OJTRoute is a modern, feature-rich OJT management system that helps educational institutions efficiently manage student internships. The system provides real-time attendance tracking, document management, workplace location verification, and comprehensive reporting capabilities.

### Key Highlights

- **GPS-based Attendance Tracking** - Students can clock in/out with location verification
- **Document Management** - Upload, review, and approve student documents
- **Workplace Management** - Set and verify workplace locations with map integration
- **Real-time Monitoring** - Track student progress and attendance in real-time
- **Comprehensive Reporting** - Generate detailed reports for students and administrators
- **Multi-role Support** - Separate interfaces for students, instructors, and administrators

## ✨ Features

### Core Features

- ✅ **User Authentication & Authorization**
  - Secure login system with role-based access control
  - Password encryption and session management
  - Account archiving and restoration

- ✅ **Attendance Management**
  - GPS-based time in/out tracking
  - Photo verification for attendance
  - Location radius verification (40-60 meters)
  - Multiple time blocks (Morning, Afternoon, Overtime)
  - Automatic hours calculation

- ✅ **Document Management**
  - Upload and submit required documents
  - Pre-required document validation
  - Document approval workflow
  - Template downloads
  - File type validation (PDF, DOC, DOCX, Images)

- ✅ **Workplace Management**
  - Interactive map for workplace location selection
  - Location search functionality with geocoding
  - Workplace change request system
  - Supervisor and position tracking

- ✅ **Progress Tracking**
  - Real-time OJT hours monitoring
  - Progress visualization with charts
  - Target hours tracking (default 600 hours)
  - Completion percentage calculation

- ✅ **Calendar & Scheduling**
  - Visual calendar with attendance history
  - Color-coded attendance status
  - Excuse/timeout tracking
  - Monthly view with navigation

- ✅ **Reporting & Analytics**
  - Student performance reports
  - Attendance summaries
  - Document submission tracking
  - Export capabilities

## 💻 System Requirements

### Server Requirements

- **Web Server**: Apache 2.4+ or Nginx
- **PHP**: 7.4 or higher (8.0+ recommended)
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Storage**: Minimum 500MB free space

### PHP Extensions Required

- PDO
- PDO_MySQL
- mbstring
- fileinfo
- gd or imagick (for image processing)
- curl (for geocoding API)

### Client Requirements

- Modern web browser (Chrome 90+, Firefox 88+, Safari 14+, Edge 90+)
- JavaScript enabled
- GPS/Location services (for attendance tracking)
- Camera access (for photo verification)

## 🚀 Installation

### Step 1: Clone or Download

```bash
# Clone the repository
git clone <repository-url> ojtlast

# Or download and extract to your web server directory
# For XAMPP: C:\xampp\htdocs\ojtlast
# For Linux: /var/www/html/ojtlast
```

### Step 2: Database Setup

1. Create a new MySQL database:

```sql
CREATE DATABASE ojt_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Import the database schema:

```bash
mysql -u root -p ojt_system < database/schema.sql
```

Or use phpMyAdmin to import the SQL file.

### Step 3: Configure Database Connection

Edit `config/database.php`:

```php
return [
    'host' => 'localhost',
    'dbname' => 'ojt_system',
    'username' => 'root',
    'password' => 'your_password',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
```

### Step 4: Set Directory Permissions

```bash
# For Linux/Mac
chmod -R 755 storage/
chmod -R 755 storage/uploads/
chmod -R 755 storage/images/

# Ensure web server can write to these directories
chown -R www-data:www-data storage/
```
For Windows (XAMPP), ensure the `storage` folder has write permissions.


## 👥 User Roles

### 1. Administrator (Admin)
- Full system access
- Manage all users (students, instructors, admins)
- View all reports and analytics
- System configuration
- Approve workplace changes

### 2. Instructor
- Manage assigned students
- Review and approve documents
- Monitor student attendance
- Create document requirements
- Generate student reports
- Approve/reject workplace changes

### 3. Student
- Track attendance (time in/out)
- Submit documents
- View progress and hours
- Set workplace location
- Request workplace changes
- View calendar and history

## 🎯 Key Features by Role

### Student Features

| Feature | Description |
|---------|-------------|
| **Profile Management** | Update contact info, profile picture, view instructor details |
| **Attendance Tracking** | GPS-based time in/out with photo verification |
| **Document Submission** | Upload required documents for approval |
| **Workplace Setup** | Set workplace location with map and search |
| **Progress Monitoring** | View OJT hours progress and completion percentage |
| **Calendar View** | Visual calendar showing attendance history |
| **Timeout Requests** | Submit excuse/timeout requests |

### Instructor Features

| Feature | Description |
|---------|-------------|
| **Student Management** | View and manage assigned students |
| **Document Review** | Approve/reject student document submissions |
| **Attendance Monitoring** | View student attendance records and patterns |
| **Document Creation** | Create new document requirements |
| **Reports** | Generate comprehensive student reports |
| **Workplace Approval** | Approve/reject workplace change requests |

### Administrator Features

| Feature | Description |
|---------|-------------|
| **User Management** | Create, edit, archive users (students, instructors, admins) |
| **Section Management** | Create and manage sections/classes |
| **System Reports** | View system-wide analytics and reports |
| **Bulk Operations** | Import students via CSV, bulk actions |
| **System Configuration** | Configure system settings and parameters |
| **Audit Logs** | View system activity and changes |

## 🛠️ Technology Stack

### Frontend
- **HTML5** - Structure and markup
- **CSS3** - Styling with custom properties (CSS variables)
- **JavaScript (ES6+)** - Client-side interactivity
- **Leaflet.js** - Interactive maps for workplace location
- **Font Awesome** - Icons
- **Chart.js** - Data visualization (optional)

### Backend
- **PHP 7.4+** - Server-side logic
- **MySQL/MariaDB** - Database management
- **PDO** - Database abstraction layer
- **Composer** - Dependency management

### APIs & Services
- **Nominatim (OpenStreetMap)** - Geocoding and location search
- **Geolocation API** - Browser-based location tracking
- **MediaDevices API** - Camera access for photo verification

### Architecture
- **MVC Pattern** - Model-View-Controller architecture
- **Service Layer** - Business logic separation
- **RESTful API** - AJAX endpoints for dynamic operations

## ⚙️ Configuration

### Database Configuration

Edit `config/database.php` to match your database settings

### File Upload Limits

Edit `php.ini` to adjust upload limits:

```ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
```

### Attendance Radius Configuration

Default workplace radius: 40 meters (with 60-meter tolerance)

To modify, edit `app/services/StudentService.php`:

```php
// Line ~690
if ($distance > 60) {  // Change this value
    return [
        'success' => false,
        'message' => 'You are too far from your workplace...'
    ];
}
```

## 📖 Usage Guide

### For Students

1. **First Login**
   - Login with credentials provided by your instructor
   - Complete your profile information
   - Set your workplace location

2. **Daily Attendance**
   - Navigate to Attendance page
   - Select time block (Morning/Afternoon/Overtime)
   - Click "Time In" and allow location access
   - Take a selfie for verification
   - Click "Time Out" when leaving

3. **Submit Documents**
   - Go to Submissions > Documents
   - Upload required documents
   - Wait for instructor approval

4. **Track Progress**
   - View your profile for OJT hours progress
   - Check calendar for attendance history

### For Instructors

1. **Manage Students**
   - View assigned students in your dashboard
   - Monitor attendance and progress

2. **Review Documents**
   - Check pending document submissions
   - Approve or reject with feedback

3. **Create Requirements**
   - Add new document requirements
   - Set deadlines and templates

### For Administrators

1. **User Management**
   - Create new users (students, instructors)
   - Assign students to sections
   - Archive inactive users

2. **System Monitoring**
   - View system-wide reports
   - Monitor attendance patterns
   - Generate analytics

## 🤝 Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

### Coding Standards

- Follow PSR-12 coding standards for PHP
- Use meaningful variable and function names
- Comment complex logic
- Test thoroughly before submitting

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 📞 Support

For support and questions:
- **Email**: coloradomanuel.002@gmail.com
- **Documentation**: Under school university institution 

## 🙏 Acknowledgments

- APIs used 
- Coffee
- All contributors and testers

## 🔄 Version History

### Version 1.0.0 (Current)
- Initial release
- Core attendance tracking
- Document management
- Workplace location features
- User management
- Reporting capabilities

---

**Made with hatred and complaints for educational institutions of CARLOS HILADO MEMORIAL STATE UNIVERSITY - Alijis Campus**

*Last Updated: January 2026*
