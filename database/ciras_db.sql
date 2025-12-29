-- ============================================
-- CIRAS Database Schema
-- Cybercrime Incident Reporting & Analysis System
-- ============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Create database
CREATE DATABASE IF NOT EXISTS `ciras_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ciras_db`;

-- ============================================
-- USER MANAGEMENT TABLES
-- ============================================

-- User Roles
CREATE TABLE IF NOT EXISTS `user_roles` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  `role_description` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Role Permissions
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `permission_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `permission_name` varchar(100) NOT NULL,
  `permission_description` text,
  PRIMARY KEY (`permission_id`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `user_roles` (`role_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Users
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `badge_number` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role_id` int(11) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `user_roles` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INCIDENT MANAGEMENT TABLES
-- ============================================

-- Incident Categories (Hierarchical)
CREATE TABLE IF NOT EXISTS `incident_categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(255) NOT NULL,
  `category_description` text,
  `parent_category_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`),
  KEY `parent_category_id` (`parent_category_id`),
  CONSTRAINT `incident_categories_ibfk_1` FOREIGN KEY (`parent_category_id`) REFERENCES `incident_categories` (`category_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Incident Status
CREATE TABLE IF NOT EXISTS `incident_status` (
  `status_id` int(11) NOT NULL AUTO_INCREMENT,
  `status_name` varchar(50) NOT NULL,
  `status_description` text,
  `status_color` varchar(20) DEFAULT '#6B7280',
  `is_closed` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`status_id`),
  UNIQUE KEY `status_name` (`status_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Locations
CREATE TABLE IF NOT EXISTS `locations` (
  `location_id` int(11) NOT NULL AUTO_INCREMENT,
  `address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'USA',
  `postal_code` varchar(20) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Incidents
CREATE TABLE IF NOT EXISTS `incidents` (
  `incident_id` int(11) NOT NULL AUTO_INCREMENT,
  `incident_number` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `category_id` int(11) NOT NULL,
  `status_id` int(11) NOT NULL DEFAULT 1,
  `priority` enum('Low','Medium','High','Critical') NOT NULL DEFAULT 'Medium',
  `reported_by` int(11) NOT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `location_id` int(11) DEFAULT NULL,
  `reported_date` datetime NOT NULL,
  `occurred_date` datetime DEFAULT NULL,
  `resolved_date` datetime DEFAULT NULL,
  `estimated_loss` decimal(15,2) DEFAULT NULL,
  `affected_systems` text,
  `attack_vector` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `is_confidential` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`incident_id`),
  UNIQUE KEY `incident_number` (`incident_number`),
  KEY `category_id` (`category_id`),
  KEY `status_id` (`status_id`),
  KEY `reported_by` (`reported_by`),
  KEY `assigned_to` (`assigned_to`),
  KEY `location_id` (`location_id`),
  CONSTRAINT `incidents_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `incident_categories` (`category_id`),
  CONSTRAINT `incidents_ibfk_2` FOREIGN KEY (`status_id`) REFERENCES `incident_status` (`status_id`),
  CONSTRAINT `incidents_ibfk_3` FOREIGN KEY (`reported_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `incidents_ibfk_4` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `incidents_ibfk_5` FOREIGN KEY (`location_id`) REFERENCES `locations` (`location_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Incident Tags
CREATE TABLE IF NOT EXISTS `incident_tags` (
  `tag_id` int(11) NOT NULL AUTO_INCREMENT,
  `incident_id` int(11) NOT NULL,
  `tag_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`tag_id`),
  KEY `incident_id` (`incident_id`),
  CONSTRAINT `incident_tags_ibfk_1` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`incident_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- EVIDENCE MANAGEMENT TABLES
-- ============================================

-- Evidence Hashes
CREATE TABLE IF NOT EXISTS `evidence_hashes` (
  `hash_id` int(11) NOT NULL AUTO_INCREMENT,
  `hash_value` varchar(64) NOT NULL,
  `hash_algorithm` varchar(20) NOT NULL DEFAULT 'SHA-256',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`hash_id`),
  UNIQUE KEY `hash_value` (`hash_value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Incident Evidence
CREATE TABLE IF NOT EXISTS `incident_evidence` (
  `evidence_id` int(11) NOT NULL AUTO_INCREMENT,
  `incident_id` int(11) NOT NULL,
  `evidence_type` enum('File','Image','Log','Screenshot','Video','Other') NOT NULL DEFAULT 'File',
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `hash_id` int(11) DEFAULT NULL,
  `description` text,
  `collected_by` int(11) NOT NULL,
  `collected_date` datetime NOT NULL,
  `chain_of_custody` text,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`evidence_id`),
  KEY `incident_id` (`incident_id`),
  KEY `hash_id` (`hash_id`),
  KEY `collected_by` (`collected_by`),
  CONSTRAINT `incident_evidence_ibfk_1` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`incident_id`) ON DELETE CASCADE,
  CONSTRAINT `incident_evidence_ibfk_2` FOREIGN KEY (`hash_id`) REFERENCES `evidence_hashes` (`hash_id`) ON DELETE SET NULL,
  CONSTRAINT `incident_evidence_ibfk_3` FOREIGN KEY (`collected_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INVESTIGATION TABLES
-- ============================================

-- Investigation Notes
CREATE TABLE IF NOT EXISTS `investigation_notes` (
  `note_id` int(11) NOT NULL AUTO_INCREMENT,
  `incident_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `note_title` varchar(255) DEFAULT NULL,
  `note_content` text NOT NULL,
  `is_internal` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`note_id`),
  KEY `incident_id` (`incident_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `investigation_notes_ibfk_1` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`incident_id`) ON DELETE CASCADE,
  CONSTRAINT `investigation_notes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Case Timeline
CREATE TABLE IF NOT EXISTS `case_timeline` (
  `timeline_id` int(11) NOT NULL AUTO_INCREMENT,
  `incident_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `event_description` text NOT NULL,
  `event_date` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`timeline_id`),
  KEY `incident_id` (`incident_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `case_timeline_ibfk_1` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`incident_id`) ON DELETE CASCADE,
  CONSTRAINT `case_timeline_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SYSTEM TABLES
-- ============================================

-- Audit Logs
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action_type` varchar(100) NOT NULL,
  `action_description` text NOT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `user_id` (`user_id`),
  KEY `action_type` (`action_type`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications
CREATE TABLE IF NOT EXISTS `notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') NOT NULL DEFAULT 'info',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `related_incident_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_id`),
  KEY `user_id` (`user_id`),
  KEY `is_read` (`is_read`),
  KEY `related_incident_id` (`related_incident_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`related_incident_id`) REFERENCES `incidents` (`incident_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System Settings
CREATE TABLE IF NOT EXISTS `system_settings` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `setting_description` text,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `system_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERT SAMPLE DATA
-- ============================================

-- Insert User Roles
INSERT INTO `user_roles` (`role_id`, `role_name`, `role_description`) VALUES
(1, 'Admin', 'Full system access and user management'),
(2, 'Officer', 'Can create, edit, and manage incidents'),
(3, 'Analyst', 'Can view and analyze incidents, generate reports'),
(4, 'Viewer', 'Read-only access to incidents');

-- Insert Role Permissions
INSERT INTO `role_permissions` (`role_id`, `permission_name`, `permission_description`) VALUES
(1, 'manage_users', 'Create, edit, and delete users'),
(1, 'manage_incidents', 'Full incident management'),
(1, 'view_reports', 'Access all reports'),
(1, 'system_settings', 'Modify system settings'),
(2, 'create_incidents', 'Create new incidents'),
(2, 'edit_incidents', 'Edit assigned incidents'),
(2, 'upload_evidence', 'Upload evidence files'),
(3, 'view_incidents', 'View all incidents'),
(3, 'generate_reports', 'Generate and export reports'),
(4, 'view_incidents', 'View incidents (read-only)');

-- Insert Users (password: admin123, password, analyst123, viewer123)
INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `full_name`, `badge_number`, `phone`, `role_id`, `department`, `is_active`) VALUES
(1, 'admin', 'admin@ciras.com', '$2y$12$hZfGXngPmFmcTuQK2ovUw.rVtUnQByNZ5rCWyUc8xKhpZI4mYbtRa', 'Administrator', 'ADM-001', '+1-555-0100', 1, 'IT Security', 1),
(2, 'officer1', 'officer1@ciras.com', '$2y$12$lTKZaF1qTzwBmijSWMKxJuPM76E0PHK.rDDN.5yLqynxaEHSjNQRq', 'Detective John Smith', 'OFF-201', '+1-555-0201', 2, 'Cybercrime Unit', 1),
(3, 'officer2', 'officer2@ciras.com', '$2y$12$lTKZaF1qTzwBmijSWMKxJuPM76E0PHK.rDDN.5yLqynxaEHSjNQRq', 'Officer Sarah Johnson', 'OFF-202', '+1-555-0202', 2, 'Cybercrime Unit', 1),
(4, 'analyst1', 'analyst1@ciras.com', '$2y$12$xAmWWgq5ZIzPLtBCnvFkk.7PtRz2NaN1tP5QzZhx2yfsrRX6o5dye', 'Analyst Michael Brown', 'ANA-301', '+1-555-0301', 3, 'Data Analysis', 1),
(5, 'viewer1', 'viewer1@ciras.com', '$2y$12$5l.PLvCMqFQwsggXlggC3eKVJFOm18G2tB1RIvejNGqhcu8Buz4Gu', 'Viewer Emily Davis', 'VIE-401', '+1-555-0401', 4, 'Legal Department', 1);

-- Insert Incident Categories (Hierarchical)
INSERT INTO `incident_categories` (`category_id`, `category_name`, `category_description`, `parent_category_id`) VALUES
(1, 'Malware', 'Malicious software incidents', NULL),
(2, 'Ransomware', 'Ransomware attacks', 1),
(3, 'Trojan', 'Trojan horse malware', 1),
(4, 'Phishing', 'Phishing and social engineering', NULL),
(5, 'Email Phishing', 'Email-based phishing attacks', 4),
(6, 'SMS Phishing', 'SMS-based phishing (smishing)', 4),
(7, 'Data Breach', 'Unauthorized data access', NULL),
(8, 'SQL Injection', 'SQL injection attacks', 7),
(9, 'DDoS', 'Distributed Denial of Service', NULL),
(10, 'Identity Theft', 'Identity theft and fraud', NULL),
(11, 'Financial Fraud', 'Online financial fraud', 10),
(12, 'Unauthorized Access', 'Unauthorized system access', NULL),
(13, 'Insider Threat', 'Threats from internal sources', 12);

-- Insert Incident Status
INSERT INTO `incident_status` (`status_id`, `status_name`, `status_description`, `status_color`, `is_closed`) VALUES
(1, 'New', 'Newly reported incident', '#3B82F6', 0),
(2, 'Under Investigation', 'Active investigation in progress', '#F59E0B', 0),
(3, 'Pending Review', 'Awaiting review or approval', '#8B5CF6', 0),
(4, 'Resolved', 'Incident has been resolved', '#10B981', 1),
(5, 'Closed', 'Case closed', '#6B7280', 1),
(6, 'Escalated', 'Escalated to higher authority', '#EF4444', 0);

-- Insert Locations
INSERT INTO `locations` (`location_id`, `address`, `city`, `state`, `country`, `postal_code`, `latitude`, `longitude`) VALUES
(1, '123 Main Street', 'New York', 'NY', 'USA', '10001', 40.712776, -74.005974),
(2, '456 Oak Avenue', 'Los Angeles', 'CA', 'USA', '90001', 34.052235, -118.243683),
(3, '789 Pine Road', 'Chicago', 'IL', 'USA', '60601', 41.878113, -87.629799),
(4, '321 Elm Street', 'Houston', 'TX', 'USA', '77001', 29.760427, -95.369804),
(5, '654 Maple Drive', 'Phoenix', 'AZ', 'USA', '85001', 33.448377, -112.074037);

-- Insert Sample Incidents
INSERT INTO `incidents` (`incident_id`, `incident_number`, `title`, `description`, `category_id`, `status_id`, `priority`, `reported_by`, `assigned_to`, `location_id`, `reported_date`, `occurred_date`, `estimated_loss`, `affected_systems`, `attack_vector`, `ip_address`) VALUES
(1, 'CIR-2024-001', 'Ransomware Attack on Corporate Network', 'A sophisticated ransomware attack encrypted critical files on the corporate network. The attackers demanded $500,000 in Bitcoin. Initial investigation shows the attack originated from a phishing email.', 2, 2, 'Critical', 2, 2, 1, '2024-01-15 08:30:00', '2024-01-14 22:15:00', 500000.00, 'File Server (FS-01), Database Server (DB-02)', 'Phishing Email', '192.168.1.100'),
(2, 'CIR-2024-002', 'Data Breach - Customer Database', 'Unauthorized access detected to customer database. Approximately 50,000 customer records may have been compromised including names, emails, and encrypted passwords.', 7, 2, 'High', 3, 2, 2, '2024-01-18 14:20:00', '2024-01-17 03:45:00', 250000.00, 'Customer Database (DB-CUST-01)', 'SQL Injection', '203.0.113.45'),
(3, 'CIR-2024-003', 'Phishing Campaign Targeting Employees', 'Multiple employees received phishing emails impersonating the IT department requesting password updates. Three employees clicked the malicious link before the threat was identified.', 5, 3, 'Medium', 2, 3, 1, '2024-01-20 09:15:00', '2024-01-20 08:00:00', 5000.00, 'Email Server (MAIL-01)', 'Email Phishing', '198.51.100.22'),
(4, 'CIR-2024-004', 'DDoS Attack on Web Services', 'Distributed Denial of Service attack targeting company website and API endpoints. Service was unavailable for approximately 4 hours.', 9, 4, 'High', 3, 2, 3, '2024-01-22 11:00:00', '2024-01-22 10:30:00', 15000.00, 'Web Server (WEB-01), API Server (API-01)', 'DDoS', '192.0.2.100'),
(5, 'CIR-2024-005', 'Identity Theft - Credit Card Fraud', 'Multiple reports of unauthorized credit card transactions. Investigation reveals identity theft through compromised personal information from a third-party breach.', 11, 2, 'High', 2, 2, 4, '2024-01-25 16:45:00', '2024-01-24 00:00:00', 75000.00, 'Payment Processing System', 'Identity Theft', NULL),
(6, 'CIR-2024-006', 'Trojan Malware on Workstation', 'Workstation infected with trojan malware. Malware was detected by antivirus after attempting to exfiltrate sensitive documents.', 3, 4, 'Medium', 3, 3, 1, '2024-01-28 10:30:00', '2024-01-27 15:20:00', 2000.00, 'Workstation (WS-042)', 'Malicious Download', '10.0.0.42'),
(7, 'CIR-2024-007', 'Unauthorized Access - Privilege Escalation', 'Security logs show unauthorized privilege escalation attempt. An external attacker gained initial access through a compromised user account.', 12, 2, 'Critical', 2, 2, 2, '2024-02-01 13:20:00', '2024-01-31 20:10:00', 100000.00, 'Active Directory Server (AD-01)', 'Compromised Credentials', '203.0.113.78'),
(8, 'CIR-2024-008', 'SMS Phishing (Smishing) Campaign', 'Employees receiving SMS messages claiming to be from the bank requesting account verification. Several employees reported the messages.', 6, 1, 'Low', 3, 3, 5, '2024-02-03 08:00:00', '2024-02-03 07:30:00', 0.00, 'Mobile Devices', 'SMS Phishing', NULL),
(9, 'CIR-2024-009', 'Insider Threat - Data Exfiltration', 'Suspicious activity detected from an internal user account. Large volumes of sensitive data were accessed and potentially copied to external storage.', 13, 2, 'Critical', 2, 2, 1, '2024-02-05 09:45:00', '2024-02-04 14:00:00', 200000.00, 'Document Management System (DMS-01)', 'Insider Threat', '10.0.0.15'),
(10, 'CIR-2024-010', 'SQL Injection - E-commerce Platform', 'SQL injection vulnerability discovered and exploited on the e-commerce platform. Attackers attempted to access customer payment information.', 8, 3, 'High', 3, 2, 3, '2024-02-08 15:30:00', '2024-02-08 12:00:00', 50000.00, 'E-commerce Database (ECOM-DB)', 'SQL Injection', '198.51.100.55'),
(11, 'CIR-2024-011', 'Ransomware - Backup System Compromise', 'Backup system infected with ransomware variant. Fortunately, primary systems were not affected, but backup restoration capability was temporarily disabled.', 2, 4, 'High', 2, 3, 2, '2024-02-10 11:20:00', '2024-02-09 18:30:00', 30000.00, 'Backup Server (BACKUP-01)', 'Network Propagation', '192.168.2.50'),
(12, 'CIR-2024-012', 'Phishing - CEO Impersonation', 'Phishing email impersonating CEO requesting urgent wire transfer. Email was flagged by security system before any financial transaction occurred.', 5, 4, 'Medium', 3, 2, 1, '2024-02-12 14:00:00', '2024-02-12 13:15:00', 0.00, 'Email Server (MAIL-01)', 'Email Phishing', '192.0.2.200'),
(13, 'CIR-2024-013', 'Data Breach - Employee Records', 'Unauthorized access to HR database containing employee personal information. Investigation ongoing to determine scope of data exposure.', 7, 2, 'High', 2, 2, 4, '2024-02-15 10:15:00', '2024-02-14 22:00:00', 100000.00, 'HR Database (HR-DB-01)', 'Unknown', '203.0.113.120'),
(14, 'CIR-2024-014', 'DDoS - API Endpoints', 'Second DDoS attack targeting API endpoints. More sophisticated than previous attack, using multiple attack vectors.', 9, 2, 'High', 3, 2, 3, '2024-02-18 16:45:00', '2024-02-18 15:00:00', 25000.00, 'API Server (API-01, API-02)', 'DDoS', '192.0.2.150'),
(15, 'CIR-2024-015', 'Malware - Cryptocurrency Miner', 'Cryptocurrency mining malware discovered on multiple workstations. Malware was consuming significant CPU resources and slowing down systems.', 1, 4, 'Medium', 2, 3, 1, '2024-02-20 09:30:00', '2024-02-19 20:00:00', 5000.00, 'Workstations (WS-015, WS-023, WS-031)', 'Drive-by Download', '10.0.0.15'),
(16, 'CIR-2024-016', 'Financial Fraud - Wire Transfer Scam', 'Fraudulent wire transfer request processed. Investigation reveals social engineering attack targeting finance department.', 11, 2, 'Critical', 2, 2, 2, '2024-02-22 13:00:00', '2024-02-22 11:30:00', 150000.00, 'Banking System', 'Social Engineering', NULL),
(17, 'CIR-2024-017', 'Unauthorized Access - Remote Desktop', 'Brute force attack on Remote Desktop Protocol (RDP) service. Multiple failed login attempts detected from various IP addresses.', 12, 3, 'Medium', 3, 3, 5, '2024-02-25 08:20:00', '2024-02-24 23:45:00', 10000.00, 'RDP Server (RDP-01)', 'Brute Force', '198.51.100.88'),
(18, 'CIR-2024-018', 'Phishing - Vendor Impersonation', 'Phishing emails impersonating a trusted vendor requesting payment information. Several employees reported receiving the emails.', 5, 1, 'Low', 2, 3, 1, '2024-02-27 10:00:00', '2024-02-27 09:00:00', 0.00, 'Email Server (MAIL-01)', 'Email Phishing', '192.0.2.175'),
(19, 'CIR-2024-019', 'Data Breach - Customer Payment Info', 'Potential breach of customer payment information. Security team investigating suspicious database access patterns.', 7, 2, 'Critical', 2, 2, 3, '2024-03-01 14:30:00', '2024-02-29 19:00:00', 300000.00, 'Payment Database (PAY-DB)', 'Unknown', '203.0.113.200'),
(20, 'CIR-2024-020', 'Ransomware - Network Share', 'Ransomware detected on network file share. Quick response prevented widespread encryption, but several files were affected.', 2, 4, 'High', 3, 2, 2, '2024-03-03 11:45:00', '2024-03-02 16:20:00', 40000.00, 'File Share (FS-02)', 'Network Share', '192.168.1.200');

-- Insert Incident Tags
INSERT INTO `incident_tags` (`incident_id`, `tag_name`) VALUES
(1, 'ransomware'), (1, 'critical'), (1, 'phishing'),
(2, 'data-breach'), (2, 'sql-injection'), (2, 'database'),
(3, 'phishing'), (3, 'email'), (3, 'social-engineering'),
(4, 'ddos'), (4, 'availability'), (4, 'network'),
(5, 'fraud'), (5, 'identity-theft'), (5, 'financial'),
(7, 'unauthorized-access'), (7, 'privilege-escalation'), (7, 'critical'),
(9, 'insider-threat'), (9, 'data-exfiltration'), (9, 'critical'),
(10, 'sql-injection'), (10, 'web-application'), (10, 'database'),
(13, 'data-breach'), (13, 'hr'), (13, 'personal-data'),
(16, 'fraud'), (16, 'wire-transfer'), (16, 'social-engineering'),
(19, 'data-breach'), (19, 'payment'), (19, 'critical');

-- Insert Investigation Notes
INSERT INTO `investigation_notes` (`incident_id`, `user_id`, `note_title`, `note_content`, `is_internal`) VALUES
(1, 2, 'Initial Assessment', 'Ransomware identified as LockBit variant. Encryption started at 22:15 on Jan 14. Backup systems appear unaffected. Contacted cybersecurity firm for decryption options.', 1),
(1, 2, 'Forensic Analysis', 'Malware entry point: Phishing email sent to finance@company.com. Email contained malicious PDF attachment. User opened attachment at 22:10.', 1),
(2, 2, 'Database Investigation', 'SQL injection vulnerability found in customer login page. Attackers used UNION-based injection to extract data. Vulnerability has been patched.', 1),
(2, 4, 'Data Analysis', 'Analysis of access logs shows data extraction occurred over 3-hour period. Approximately 50,000 records accessed. Notified affected customers.', 0),
(4, 2, 'DDoS Mitigation', 'DDoS attack mitigated using cloud-based protection service. Attack traffic reached 50 Gbps at peak. Service restored after 4 hours.', 1),
(7, 2, 'Privilege Escalation Analysis', 'Attacker gained initial access through compromised user account (user: jdoe). Used Windows privilege escalation exploit. Account has been disabled.', 1),
(9, 2, 'Insider Threat Investigation', 'Employee account (user_id: 42) accessed 15,000 documents in 2-hour period. Documents copied to USB drive. Employee has been suspended pending investigation.', 1),
(10, 2, 'SQL Injection Patch', 'Vulnerability patched by implementing parameterized queries. All user inputs now properly sanitized. Security audit scheduled.', 1);

-- Insert Case Timeline Events
INSERT INTO `case_timeline` (`incident_id`, `user_id`, `event_type`, `event_description`, `event_date`) VALUES
(1, 2, 'Incident Reported', 'Incident reported by Detective John Smith', '2024-01-15 08:30:00'),
(1, 2, 'Investigation Started', 'Investigation assigned to Detective John Smith', '2024-01-15 09:00:00'),
(1, 2, 'Evidence Collected', 'Malware sample collected and sent for analysis', '2024-01-15 10:30:00'),
(1, 2, 'Status Updated', 'Status changed to Under Investigation', '2024-01-15 11:00:00'),
(2, 3, 'Incident Reported', 'Incident reported by Officer Sarah Johnson', '2024-01-18 14:20:00'),
(2, 2, 'Investigation Started', 'Investigation assigned to Detective John Smith', '2024-01-18 15:00:00'),
(2, 2, 'Vulnerability Patched', 'SQL injection vulnerability patched', '2024-01-19 10:00:00'),
(4, 2, 'Incident Resolved', 'DDoS attack mitigated and services restored', '2024-01-22 14:30:00'),
(4, 2, 'Status Updated', 'Status changed to Resolved', '2024-01-22 14:35:00'),
(7, 2, 'Incident Reported', 'Unauthorized access detected', '2024-02-01 13:20:00'),
(7, 2, 'Account Disabled', 'Compromised user account disabled', '2024-02-01 14:00:00'),
(9, 2, 'Incident Reported', 'Insider threat detected', '2024-02-05 09:45:00'),
(9, 2, 'Employee Suspended', 'Employee suspended pending investigation', '2024-02-05 11:00:00');

-- Insert System Settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_description`) VALUES
('system_name', 'CIRAS - Cybercrime Incident Reporting & Analysis System', 'System display name'),
('max_file_upload_size', '10485760', 'Maximum file upload size in bytes (10MB)'),
('allowed_file_types', 'pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,txt,log,csv', 'Comma-separated list of allowed file extensions'),
('incident_number_prefix', 'CIR', 'Prefix for auto-generated incident numbers'),
('session_timeout', '3600', 'Session timeout in seconds (1 hour)'),
('enable_notifications', '1', 'Enable system notifications (1=yes, 0=no)'),
('maintenance_mode', '0', 'Maintenance mode (1=yes, 0=no)');

-- Insert Sample Notifications
INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`, `related_incident_id`) VALUES
(2, 'New Incident Assigned', 'You have been assigned to incident CIR-2024-001', 'info', 1),
(2, 'New Incident Assigned', 'You have been assigned to incident CIR-2024-002', 'info', 2),
(3, 'New Incident Assigned', 'You have been assigned to incident CIR-2024-003', 'info', 3),
(2, 'Evidence Uploaded', 'New evidence uploaded for incident CIR-2024-001', 'success', 1),
(2, 'Status Update', 'Incident CIR-2024-004 has been resolved', 'success', 4),
(2, 'Critical Incident', 'New critical incident CIR-2024-007 requires immediate attention', 'warning', 7);

-- ============================================
-- CREATE INDEXES FOR PERFORMANCE
-- ============================================

CREATE INDEX idx_incidents_reported_date ON incidents(reported_date);
CREATE INDEX idx_incidents_status ON incidents(status_id);
CREATE INDEX idx_incidents_priority ON incidents(priority);
CREATE INDEX idx_incidents_category ON incidents(category_id);
CREATE INDEX idx_audit_logs_user_date ON audit_logs(user_id, created_at);
CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read);

-- ============================================
-- END OF SCHEMA
-- ============================================

