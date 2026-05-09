# 🏋️ Fitness Gym Membership Management System

A web-based system for managing gym memberships, staff, trainers, and members — built with PHP and MySQL.

---

## 📋 Features

### 👤 Member
- View personal dashboard
- Track fitness programs and progress
- View upcoming and past sessions
- Update program completion

### 🧑‍💼 Staff
- Manage member registrations and profiles
- Handle billing and membership renewals
- Track attendance
- Monitor due members
- Manage gym equipment and maintenance
- Record member vitals and progress
- View and manage trainers

### 🏃 Trainer
- View assigned sessions and schedule
- Track session history
- Log exercise completions via modal

### 🔧 Admin
- System configuration
- View admin dashboard
- Monitor system logs

---

## ⚙️ Installation

### Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache / XAMPP / WAMP / Laragon

### Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/YOUR_USERNAME/YOUR_REPO_NAME.git
   ```

2. **Move to your server's root directory**
   ```
   XAMPP: C:/xampp/htdocs/
   Laragon: C:/laragon/www/
   ```

3. **Import the database**
   - Open **phpMyAdmin**
   - Create a new database (e.g., `gym_management`)
   - Import `gym_management.sql`

4. **Configure the database connection**
   - Open `config/database.php`
   - Update with your credentials:
   ```php
   $host = 'localhost';
   $dbname = 'gym_management';
   $username = 'root';
   $password = '';
   ```

5. **Run the system**
   - Open your browser and go to:
   ```
   http://localhost/YOUR_REPO_NAME/
   ```

---

## 🔐 User Roles

| Role    | Access Level                          |
|---------|---------------------------------------|
| Admin   | Full system control                   |
| Staff   | Members, billing, equipment, vitals   |
| Trainer | Sessions, schedules, exercises        |
| Member  | Personal dashboard, programs, sessions|

---

## 🛠️ Built With

- **PHP** — Backend logic
- **MySQL** — Database
- **HTML / CSS / JavaScript** — Frontend
- **XAMPP / Laragon** — Local development server

---

## 📄 License

This project is for educational purposes only.
