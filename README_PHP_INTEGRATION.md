# E-Blood Bank PHP Integration

This document outlines the PHP and database integration added to the E-Blood Bank project.

## Overview

The project has been enhanced with PHP backend functionality to handle user registration, login, and dynamic dashboard display. User data is now stored in a MySQL database.

## Files Added/Modified

### New Files

#### `config.php`

- Database configuration file
- Contains PDO connection to MySQL database
- Change credentials as needed:
  ```php
  $host = 'localhost';
  $dbname = 'e_blood_bank';
  $username = 'root'; // Change if needed
  $password = ''; // Change if needed
  ```

#### `database.sql`

- SQL script to create the database and tables
- Run this in phpMyAdmin or MySQL command line:
  ```bash
  mysql -u root -p < database.sql
  ```
- Creates `e_blood_bank` database with `users` table
- Includes sample user data

#### `signup.php`

- Handles donor registration form submission
- Validates input data
- Hashes passwords securely using `password_hash()`
- Inserts user data into database
- Redirects to login page on success

#### `login.php`

- Handles login form submission
- Verifies email and password
- Uses `password_verify()` for secure password checking
- Starts PHP session on successful login
- Stores user data in session variables
- Redirects to dashboard on success

#### `logout.php`

- Destroys user session
- Redirects to login page

#### `dashboard.php`

- Dynamic dashboard page (replaces `dashboard.html`)
- Checks for active session, redirects to login if not authenticated
- Displays personalized user information:
  - Name in greeting and sidebar
  - Blood group in profile
  - User role (Donor/Requester)
  - Avatar with user initials

### Modified Files

#### `register-donor.html`

- Changed form action from empty to `signup.php`
- Form now submits via POST method

#### `login.html`

- Changed form action from empty to `login.php`
- Form now submits via POST method

## Database Schema

### `users` Table

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('donor', 'requester') NOT NULL,
    blood_group VARCHAR(5),
    phone VARCHAR(15),
    city VARCHAR(50),
    dob DATE,
    gender VARCHAR(20),
    weight INT,
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## Setup Instructions

1. **Install PHP and MySQL**:
   - Download XAMPP: https://www.apachefriends.org/
   - Start Apache and MySQL services

2. **Create Database**:
   - Open phpMyAdmin: http://localhost/phpmyadmin
   - Import `database.sql`

3. **Start PHP Server**:

   ```bash
   cd E-Blood_Bank
   php -S localhost:8000
   ```

4. **Access Application**:
   - Register: http://localhost:8000/register-donor.html
   - Login: http://localhost:8000/login.html
   - Dashboard: http://localhost:8000/dashboard.php

## Security Features

- Passwords are hashed using `password_hash()` with default algorithm
- SQL injection prevention using prepared statements
- Session-based authentication
- Input validation and sanitization

## Future Enhancements

- Add requester registration functionality
- Implement password reset feature
- Add user profile editing
- Create admin panel for managing blood requests
- Add email verification for registration

## Notes

- The project currently focuses on donor registration
- Requester functionality can be added by extending the existing code
- All user data is stored securely in the database
- Sessions maintain user login state across pages</content>
  <parameter name="filePath">c:\Users\AnanT_Chauhan\Documents\GitHub\E-Blood_Bank_PHP\E-Blood_Bank\README_PHP_INTEGRATION.md
