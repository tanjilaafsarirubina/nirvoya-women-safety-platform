-- --------------------------------------------------------
-- Project Nirvoya: Database Setup Script
-- Safe to re-run: tables are only created if missing.
-- --------------------------------------------------------

-- 1. Create the Database
CREATE DATABASE IF NOT EXISTS nirvoya_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE nirvoya_db;

-- --------------------------------------------------------
-- Table Definitions
-- --------------------------------------------------------

-- 2. USER (Mother Table - Shared Attributes)
CREATE TABLE IF NOT EXISTS Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL, -- Secure password storage
    phone_number VARCHAR(20),
    full_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_of_birth DATE,
    blood_group VARCHAR(5)
);

-- 3. MEMBER (Child Table - The Women/Primary Users)
CREATE TABLE IF NOT EXISTS Members (
    member_id INT PRIMARY KEY,
    FOREIGN KEY (member_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

-- 4. ADMIN (Child Table - System Moderators)
CREATE TABLE IF NOT EXISTS Admins (
    employee_id INT PRIMARY KEY,
    department VARCHAR(50),
    FOREIGN KEY (employee_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

-- 5. TRUSTED_CONTACT (1 Member manages N Contacts)
CREATE TABLE IF NOT EXISTS Trusted_Contacts (
    contact_id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    relationship VARCHAR(50),
    FOREIGN KEY (member_id) REFERENCES Members(member_id) ON DELETE CASCADE
);

-- 6. JOURNEY (1 Member takes N Journeys - For Safe Tracking)
CREATE TABLE IF NOT EXISTS Journeys (
    journey_id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    share_token CHAR(32) NOT NULL UNIQUE, -- Random, unguessable ID used in the public tracking link
    start_loc VARCHAR(255) NOT NULL, -- Coords or Address string
    end_loc VARCHAR(255) NOT NULL,
    start_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    end_time DATETIME,
    status ENUM('Active', 'Completed', 'Cancelled') DEFAULT 'Active',
    current_lat DECIMAL(10, 8), -- Updates continuously during tracking
    current_lng DECIMAL(11, 8), -- Updates continuously during tracking
    FOREIGN KEY (member_id) REFERENCES Members(member_id) ON DELETE CASCADE
);

-- 7. SOS_ALERT (Ternary Relationship: Member + Journey -> Alert)
CREATE TABLE IF NOT EXISTS SOS_Alerts (
    alert_id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    journey_id INT, -- Can be NULL if alert is instant (panic button)
    alert_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    gps_lat DECIMAL(10, 8),
    gps_lng DECIMAL(11, 8),
    status ENUM('Pending', 'Resolved') DEFAULT 'Pending',
    FOREIGN KEY (member_id) REFERENCES Members(member_id),
    FOREIGN KEY (journey_id) REFERENCES Journeys(journey_id)
);

-- 8. INCIDENT (1 Member reports, 1 Admin verifies)
CREATE TABLE IF NOT EXISTS Incidents (
    incident_id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT, -- Nullable for anonymous reporting
    employee_id INT, -- Nullable until verified
    description TEXT,
    incident_time DATETIME NOT NULL,
    gps_lat DECIMAL(10, 8),
    gps_lng DECIMAL(11, 8),
    end_lat DECIMAL(10, 8), -- Optional: where the incident ended (e.g. stalking route)
    end_lng DECIMAL(11, 8),
    status ENUM('Unverified', 'Verified', 'False Report') DEFAULT 'Unverified',
    FOREIGN KEY (member_id) REFERENCES Members(member_id) ON DELETE SET NULL,
    FOREIGN KEY (employee_id) REFERENCES Admins(employee_id)
);

-- 9. INCIDENT_TYPE (Lookup Table for Categories)
CREATE TABLE IF NOT EXISTS Incident_Types (
    type_id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(50) NOT NULL UNIQUE,
    severity INT CHECK (severity BETWEEN 1 AND 10)
);

-- 10. INCIDENT_CATEGORY (Junction Table for M:N Relationship)
CREATE TABLE IF NOT EXISTS Incident_Categories (
    incident_id INT,
    type_id INT,
    PRIMARY KEY (incident_id, type_id),
    FOREIGN KEY (incident_id) REFERENCES Incidents(incident_id) ON DELETE CASCADE,
    FOREIGN KEY (type_id) REFERENCES Incident_Types(type_id) ON DELETE CASCADE
);

-- --------------------------------------------------------
-- Initial Seed Data
-- --------------------------------------------------------

INSERT IGNORE INTO Incident_Types (type_name, severity) VALUES
('Verbal Harassment', 3),
('Stalking', 7),
('Physical Assault', 10),
('Poor Lighting', 2),
('Cyberbullying', 4);
