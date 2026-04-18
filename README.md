# Library Management System
## Online Library Management System
## Complete Setup Instructions

---

## 📁 Folder Structure

```
library/
├── index.php                    ← Student login/home
├── signup.php                   ← Student registration
├── dashboard.php                ← Student dashboard
├── listed-books.php             ← Browse all books
├── book-detail.php              ← Individual book detail
├── issued-books.php             ← Student issued books + fine
├── my-profile.php               ← Profile management
├── change-password.php          ← Password change
├── user-forgot-password.php     ← Password reset (token)
├── logout.php                   ← Student logout
├── library.sql                  ← Complete database schema
│
├── includes/
│   ├── config.php               ← DB + global functions (CSRF, flash, etc.)
│   ├── header.php               ← User-side navbar
│   └── footer.php               ← User-side footer
│
├── assets/
│   ├── css/style.css            ← User-side stylesheet
│   └── img/
│       └── default.png          ← Default profile picture
│
└── admin/
    ├── index.php                ← Admin login
    ├── dashboard.php            ← Admin dashboard with charts
    ├── logout.php               ← Admin logout
    │
    ├── manage-books.php         ← List/search/delete books
    ├── add-book.php             ← Add new book
    ├── edit-book.php            ← Edit book details
    ├── change-bookimg.php       ← Change book cover image
    │
    ├── manage-authors.php       ← Author CRUD
    ├── manage-categories.php    ← Category CRUD
    │
    ├── issue-book.php           ← Issue book to student
    ├── manage-issued-books.php  ← View all issues + return modal
    ├── student-history.php      ← Per-student history
    ├── fine-config.php          ← Fine/day & lending period config
    │
    ├── manage-students.php      ← View/block students
    ├── reports.php              ← Reports + CSV export
    ├── audit-log.php            ← Admin activity log
    ├── admin-profile.php        ← Admin profile & password
    │
    ├── get_student.php          ← AJAX: student lookup
    │
    ├── bookimg/                 ← Book cover images (auto-created)
    │
    ├── includes/
    │   ├── config.php           ← Admin config (calls global config)
    │   ├── header.php           ← Sidebar + topbar
    │   └── footer.php           ← JS includes + closing tags
    │
    └── assets/
        └── css/admin.css        ← Admin panel stylesheet
```

---

## ⚙️ Step-by-Step Setup

### Step 1 — Requirements

- **PHP** 8.0 or higher
- **MySQL** 5.7 / MariaDB 10.4 or higher
- **Apache** (with mod_rewrite) or **Nginx**
- Recommended: XAMPP / WAMP / Laragon on Windows, or LAMP on Linux

---

### Step 2 — Place Files

Copy the `library/` folder into your web server root:
- XAMPP: `C:/xampp/htdocs/library/`
- WAMP:  `C:/wamp64/www/library/`
- Linux: `/var/www/html/library/`

---

### Step 3 — Create Database

1. Open **phpMyAdmin** (http://localhost/phpmyadmin)
2. Click **New** → create a database named `library_db` (UTF8MB4 collation)
3. Select `library_db`, click **Import**
4. Choose `library.sql` from the project root and click **Go**

Or run via command line:
```bash
mysql -u root -p < library.sql
```

---

### Step 4 — Configure Database Connection

Open `library/includes/config.php` and update:

```php
define('DB_HOST', 'localhost');    // usually localhost
define('DB_USER', 'root');         // your MySQL username
define('DB_PASS', '');             // your MySQL password
define('DB_NAME', 'library_db');   // database name
```

Also update `APP_URL` to match your local setup:
```php
define('APP_URL', 'http://localhost/library');
```

---

### Step 5 — Folder Permissions

Make sure the following folders are **writable**:
```bash
chmod 755 library/assets/img/
chmod 755 library/admin/bookimg/
```
On Windows: right-click → Properties → Security → Full control for web server user.

---

### Step 6 — Default Credentials

| Role    | Username / Email   | Password  |
|---------|--------------------|-----------|
| Admin   | `admin`            | `Admin@123` |
| Student | `anujk@gmail.com`  | `Admin@123` |

> **Important:** Change these immediately after first login!

---

### Step 7 — Access the System

| URL                                   | Page              |
|---------------------------------------|-------------------|
| `http://localhost/library/`           | Student Portal    |
| `http://localhost/library/admin/`     | Admin Panel       |
| `http://localhost/library/signup.php` | Student Register  |

---

## 🔐 Security Features Implemented

| Feature              | Implementation                               |
|----------------------|----------------------------------------------|
| Password Hashing     | `password_hash()` with BCrypt (cost=12)      |
| SQL Injection Guard  | PDO prepared statements throughout           |
| CSRF Protection      | Token per session, verified on every POST    |
| Session Security     | HttpOnly cookies, SameSite=Strict            |
| Input Sanitization   | `htmlspecialchars()` on all output           |
| File Upload Security | MIME-type check (not extension only)         |
| Audit Logging        | All admin actions logged with IP             |
| Role-Based Access    | Separate session guards for admin/student    |

---

## 📊 Features Summary

### Student Panel
- Register / Login / Logout
- Browse books with search (title, author, category, ISBN)
- Advanced filter + sort
- View book details
- Dashboard: currently issued, pending fine, total borrowed
- Issued books with due dates and live fine calculation
- Return history
- Profile management + profile picture upload
- Change password
- Forgot password (token-based reset)

### Admin Panel
- Secure login with audit trail
- Dashboard with analytics charts (Chart.js)
- Manage Books: Add / Edit / Delete / Change Cover
- Manage Authors: Add / Edit / Delete
- Manage Categories: Add / Edit / Toggle status
- Issue Book: AJAX student lookup, book selection, auto due date
- Manage Issued Books: Return modal with fine input
- Fine Configuration: fine/day + lending period
- Student Management: view, block/unblock, history
- Student History: complete per-student issue log
- Reports: daily/monthly, date-range filter, CSV export
- Audit Log: track all admin actions

---

## 🔧 Optional Enhancements (Production)

1. **Email Notifications** — Install PHPMailer:
   ```bash
   composer require phpmailer/phpmailer
   ```
   Then update `user-forgot-password.php` to send real emails.

2. **PDF Reports** — Install TCPDF or DomPDF:
   ```bash
   composer require tecnickcom/tcpdf
   ```

3. **QR Code** — Install endroid/qr-code:
   ```bash
   composer require endroid/qr-code
   ```

4. **HTTPS** — Enable SSL in Apache/Nginx and set `secure=>true` in session cookie params in `config.php`

5. **Pagination** — Already implemented throughout (15–20 records per page)

---

## 🐛 Troubleshooting

| Issue                    | Fix                                                  |
|--------------------------|------------------------------------------------------|
| Blank page               | Enable PHP error display: `ini_set('display_errors',1)` |
| DB connection error      | Check DB_USER, DB_PASS, DB_NAME in config.php        |
| Images not showing       | Check bookimg/ folder exists and is writable         |
| Login not working        | Clear browser cookies and try again                  |
| CSRF error               | Ensure sessions work — check PHP session path        |
| "Admin@123" not working  | DB may not have imported — re-import library.sql     |

---

## 📝 Database Tables

| Table                    | Purpose                          |
|--------------------------|----------------------------------|
| `admin`                  | Admin users (username + bcrypt)  |
| `tblstudents`            | Registered students              |
| `tblbooks`               | Book catalogue                   |
| `tblauthors`             | Author details                   |
| `tblcategory`            | Book categories                  |
| `tblissuedbookdetails`   | Issue/return transaction log     |
| `tbl_fine_config`        | Fine per day + lending days      |
| `tbl_notifications`      | User notifications               |
| `tbl_audit_log`          | Admin activity audit trail       |
