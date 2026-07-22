-- E-Blood Bank Database Schema
-- Compatible with phpMyAdmin and MySQL CLI

CREATE DATABASE IF NOT EXISTS e_blood_bank CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE e_blood_bank;

-- Disable foreign key checks for clean structure re-creation
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS blood_requests;
DROP TABLE IF EXISTS donor_details;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. USERS TABLE
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('donor', 'requester', 'admin') NOT NULL DEFAULT 'donor',
    phone VARCHAR(20) DEFAULT NULL,
    city VARCHAR(50) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. DONOR DETAILS TABLE
CREATE TABLE donor_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    blood_group VARCHAR(5) NOT NULL,
    dob DATE DEFAULT NULL,
    gender VARCHAR(20) DEFAULT NULL,
    weight INT DEFAULT NULL,
    last_donation_date DATE DEFAULT NULL,
    total_donations INT DEFAULT 0,
    is_available TINYINT(1) DEFAULT 1,
    notify_sms TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. BLOOD REQUESTS TABLE
CREATE TABLE blood_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    patient_name VARCHAR(100) NOT NULL,
    patient_age INT NOT NULL,
    relation VARCHAR(50) DEFAULT 'Self',
    blood_group VARCHAR(5) NOT NULL,
    units_required INT NOT NULL DEFAULT 1,
    needed_by_date DATE DEFAULT NULL,
    hospital_name VARCHAR(150) NOT NULL,
    ward VARCHAR(50) DEFAULT NULL,
    city VARCHAR(50) NOT NULL,
    hospital_contact VARCHAR(20) NOT NULL,
    medical_condition TEXT DEFAULT NULL,
    urgency ENUM('critical', 'urgent', 'scheduled') DEFAULT 'urgent',
    status ENUM('pending', 'searching', 'fulfilled', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. CONTACT MESSAGES TABLE
CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    subject VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('unread', 'read', 'replied') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users (id, first_name, last_name, email, password, role, phone, city, address) VALUES
(1, 'John', 'Doe', 'john@example.com', '$2y$10$qj8dKcEDP1I9Q985kEM.xuxPvqXFVicu84Iiacq5ET1cXcKl6sy66', 'donor', '+91 98765 43210', 'New Delhi', 'Sector 18, Dwarka, Delhi'),
(2, 'Priya', 'Sharma', 'priya@example.com', '$2y$10$qj8dKcEDP1I9Q985kEM.xuxPvqXFVicu84Iiacq5ET1cXcKl6sy66', 'requester', '+91 98123 45678', 'Delhi', 'Civil Lines, Delhi'),
(3, 'Rahul', 'Verma', 'rahul@example.com', '$2y$10$qj8dKcEDP1I9Q985kEM.xuxPvqXFVicu84Iiacq5ET1cXcKl6sy66', 'donor', '+91 97654 32109', 'Noida', 'Sector 62, Noida'),
(4, 'Admin', 'User', 'admin@ebloodbank.in', '$2y$10$qj8dKcEDP1I9Q985kEM.xuxPvqXFVicu84Iiacq5ET1cXcKl6sy66', 'admin', '+91 90000 00000', 'New Delhi', 'Main Office, Healthcare Ave');

INSERT INTO donor_details (user_id, blood_group, dob, gender, weight, last_donation_date, total_donations, is_available, notify_sms) VALUES
(1, 'A+', '1995-04-12', 'male', 70, '2025-02-20', 12, 1, 1),
(3, 'O+', '1990-08-25', 'male', 75, '2025-01-15', 5, 1, 1);

INSERT INTO blood_requests (id, user_id, patient_name, patient_age, relation, blood_group, units_required, needed_by_date, hospital_name, ward, city, hospital_contact, medical_condition, urgency, status) VALUES
(1041, 2, 'Priya Sharma', 32, 'Self', 'AB−', 2, '2025-04-10', 'City Hospital', 'Ward 3', 'Delhi', '+91 98123 45678', 'Emergency surgery required', 'critical', 'pending'),
(1039, 2, 'Anjali Mehta', 45, 'Family', 'B+', 3, '2025-04-12', 'Fortis', 'ICU 2', 'Gurgaon', '+91 98765 11111', 'Thalassemia treatment', 'urgent', 'searching'),
(1031, 1, 'Rahul Verma', 28, 'Friend', 'O+', 1, '2025-04-05', 'AIIMS', 'Ward 12', 'New Delhi', '+91 97654 32109', 'Scheduled blood transfusion', 'scheduled', 'fulfilled'),
(1018, 2, 'Deepak Kumar', 60, 'Family', 'A−', 2, '2025-03-30', 'Max Hospital', 'Room 204', 'Noida', '+91 98123 99999', 'Post surgery recovery', 'urgent', 'cancelled');

INSERT INTO contact_messages (id, user_id, name, email, phone, subject, message, status) VALUES
(1, 1, 'John Doe', 'john@example.com', '+91 98765 43210', 'Donation Help', 'How long should I wait between donations?', 'read'),
(2, 2, 'Priya Sharma', 'priya@example.com', '+91 98123 45678', 'Blood Request Help', 'Urgent need for AB- blood type, please expedite match.', 'unread');