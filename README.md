# CIRAS - Cybercrime Incident Reporting & Analysis System

![CIRAS Banner](images/FireShot%20Capture%20009%20-%20CIRAS%20-%20Cybercrime%20Incident%20Reporting%20%26%20Analysis%20System%20-%20localhost.png)

## 📋 Project Overview

**CIRAS** (Cybercrime Incident Reporting & Analysis System) is a comprehensive web-based platform designed to streamline the reporting, tracking, and analysis of cybercrime incidents. It serves as a centralized hub for law enforcement agencies, organizations, and the public to report and manage cyber threats effectively.

Built with a **LAMP Stack** (Linux, Apache, MySQL, PHP) and styled with **Tailwind CSS**, CIRAS offers a modern, responsive, and secure interface for all stakeholders.

---

## ✨ Features

### 🔐 Security & Authentication
- **Role-Based Access Control (RBAC)**: secure login for Admins, Officers, Analysts, and Viewers.
- **Secure Authentication**: Password hashing (Bcrypt) and session management.
- **Account Lockout Policy**: Prevents brute-force attacks by locking accounts after failed attempts.
- **Audit Logging**: Tracks critical user actions (e.g., logins, report creation, evidence handling).

### 📝 Incident Management
- **Report Incidents**: User-friendly detailed forms for reporting various cybercrimes (Phishing, Malware, Data Breach, etc.).
- **Case Assignment**: Automated or manual assignment of cases to officers.
- **Status Tracking**: Track incident lifecycle (New -> Under Investigation -> Resolved -> Closed).
- **Priority Handling**: Categorize incidents by severity (Low, Medium, High, Critical).

### 🕵️ Investigation & Evidence
- **Case Timeline**: visual timeline of all actions taken on a case.
- **Investigation Notes**: Internal and external notes for officers to document findings.
- **Evidence Management**: Upload, secure storage, and hashing (SHA-256) of digital evidence files.
- **Geolocation**: Record and view incident locations.

### 📊 Analytics & Reporting
- **Interactive Dashboards**: Role-specific dashboards with charts (Chart.js) and real-time stats.
- **Advanced Reports**: Filterable reports by date, status, category, and priority.
- **Export to Excel**: Download comprehensive datasets for offline analysis.

---

## 🛠️ Technology Stack

- **Frontend**: 
  - HTML5, CSS3
  - **Tailwind CSS** (CDN) for styling
  - **Chart.js** for analytics visualization
  - **FontAwesome** for icons
- **Backend**: 
  - **PHP 8.x** (Core logic)
  - **MySQL / MariaDB** (Relational Database)
- **Server**: Apache HTTP Server

---

## 🚀 Setup Instructions

### Prerequisites
- XAMPP / LAMP / MAMP Stack installed.
- PHP 8.0 or higher.
- MySQL 5.7 or MariaDB.

### Installation Steps

1. **Clone the Repository**
   ```bash
   git clone https://github.com/yourusername/ciras.git
   cd ciras
   ```

2. **Configure Database**
   - Open phpMyAdmin (usually `http://localhost/phpmyadmin`).
   - Create a new database named `ciras_db`.
   - Import the script located at `database/ciras_db.sql`.
   - *Note*: The script includes default users and sample data.

3. **Configure Connection**
   - Open `includes/db.php` (if not exists, ensure environment variables or hardcoded credentials match your local setup).
   - Default Configuration:
     - Host: `localhost`
     - DB Name: `ciras_db`
     - User: `root`
     - Password: `` (empty)

4. **Run the Application**
   - Move the project folder to your web root (e.g., `/opt/lampp/htdocs/` or `C:\xampp\htdocs\`).
   - Access via browser: `http://localhost/ciras/`

---

## 👥 Default Credentials

| Role | Email | Password | Access Level |
|------|-------|----------|--------------|
| **Admin** | `admin@ciras.com` | `admin123` | Full System Access |
| **Officer** | `officer1@ciras.com` | `password` | Incident Management |
| **Analyst** | `analyst1@ciras.com` | `analyst123` | Analytics & Reports |
| **Viewer** | `viewer1@ciras.com` | `viewer123` | Read-Only Access |

---

## 📸 Screenshots

### Login Page
*Secure login with glassmorphism design.*
![Login Page](images/FireShot%20Capture%20011%20-%20Login%20-%20CIRAS%20-%20localhost.png)

### Admin Dashboard
*Global overview of system health and incident metrics.*
![Admin Dashboard](images/FireShot%20Capture%20003%20-%20Dashboard%20-%20CIRAS%20-%20localhost.png)

### Incident Reporting Form
*Detailed form for capturing incident specifics.*
![Reporting Form](images/FireShot%20Capture%20005%20-%20Report%20New%20Incident%20-%20CIRAS%20-%20localhost.png)

### Investigation View
*Case details, timeline, and evidence management.*
![Case View](images/FireShot%20Capture%20014%20-%20View%20Incident%20-%20CIRAS%20-%20localhost.png)

---

## 📂 Project Structure

```
ciras/
├── assets/             # Images, CSS, JS resources
├── database/           # SQL scripts (Schema & Seed data)
├── includes/           # Reusable PHP components (Header, Footer, DB, Functions)
├── uploads/            # Secure storage for evidence files
├── index.php           # Routing / Entry point
├── login.php           # Authentication page
├── dashboard.php       # Main dashboard (Role-specific views)
├── incidents.php       # Incident listing
├── add-incident.php    # Create new report
├── view-incident.php   # Detailed case view
├── reports.php         # Analytics & Export
└── profile.php         # User profile management
```

---

## 📜 License

This project is licensed under the MIT License - see the LICENSE file for details.
# CIRAS
