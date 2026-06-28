# School Finance Management System

## Requirements
- PHP 8.1+
- MariaDB / MySQL
- Apache with mod_rewrite

## Setup

### 1. Database
```sql
-- Run ONLY the schema file — do not run any seed/data scripts
mysql -u root -p db_admin < sql/schema.sql
```

### 2. Database credentials
Edit `includes/db.php` and set `DB_USER` and `DB_PASS`.

### 3. Upload folder permissions
```bash
chmod 755 uploads/
```

### 4. Apache virtual host
```apache
<VirtualHost *:80>
    DocumentRoot /path/to/school-finance
    DirectoryIndex index.php login.php
    AllowOverride All
</VirtualHost>
```

### 5. Default credentials
| Role      | Username  | Password     |
|-----------|-----------|--------------|
| Treasurer | treasurer | treasurer123 |
| Auditor   | auditor   | auditor123   |

Change these in `includes/auth.php` → `USERS` constant.

## File Structure
```
school-finance/
├── index.php            # Authenticated dashboard
├── login.php            # Login page
├── logout.php
├── public.php           # Public transparency dashboard
├── .htaccess
├── assets/
│   ├── css/app.css
│   └── js/app.js
├── includes/
│   ├── db.php           # PDO connection
│   ├── auth.php         # Auth + helpers
│   └── layout.php       # Sidebar/topbar shell
├── pages/
│   ├── income.php
│   ├── expenses.php
│   ├── events.php
│   └── history.php
├── sql/
│   └── schema.sql       # Tables only, no data
└── uploads/             # Receipt files stored here
    └── .htaccess
```

## Pages
| URL | Description |
|-----|-------------|
| `/` or `/index.php` | Authenticated dashboard with charts |
| `/login.php` | Login |
| `/public.php` | Public read-only transparency view |
| `/pages/income.php` | Income management (table + spreadsheet view) |
| `/pages/expenses.php` | Expense management with receipt uploads |
| `/pages/events.php` | Events/projects management |
| `/pages/history.php` | Edit history log |
