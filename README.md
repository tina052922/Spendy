# 💰 Spendy - Personal Budget Tracker & Savings Manager

**Take control of your finances with intelligent budgeting and savings planning.**

Spendy is a comprehensive web-based personal finance management application that helps users track income, manage expenses, create savings goals, and gain insights into their spending habits. Built with modern web technologies, Spendy provides an intuitive interface for managing your financial life.

---

## 📋 Table of Contents

- [Project Overview](#-project-overview)
- [Key Features](#-key-features)
- [Technology Stack](#-technology-stack)
- [Installation & Setup](#-installation--setup)
- [How to Use](#-how-to-use-the-application)
- [Project Structure](#-project-structure)
- [Database Schema](#-database-schema)
- [Troubleshooting](#-troubleshooting)
- [Contributing](#-contributing)

---

## 🎯 Project Overview

### What is Spendy?

Spendy is a full-featured personal finance management application designed to help users:
- **Track Income & Expenses** - Record and categorize all financial transactions
- **Manage Savings Goals** - Create and monitor multiple savings plans with progress tracking
- **Analyze Spending** - View monthly summaries and spending patterns
- **Budget Planning** - Set budgets and track remaining funds
- **Auto-Save Features** - Automatically allocate portions of income to savings goals

### Application Tagline
*"Smart Budgeting, Smarter Savings"*

---

## ✨ Key Features

### 💵 Financial Tracking
- **Income Management** - Add and categorize income sources with date tracking
- **Expense Tracking** - Record expenses by category with notes and fund sources
- **Monthly Summaries** - View income and expense breakdowns by month
- **Remaining Budget Calculator** - Automatically calculates available funds after expenses and savings

### 🎯 Savings Management
- **Multiple Savings Plans** - Create unlimited savings goals with custom targets
- **Progress Tracking** - Visual progress indicators showing goal completion percentage
- **Plan Locking** - Lock savings plans to prevent withdrawals
- **Auto-Save on Income** - Automatically suggests saving 5% when income exceeds ₱20,000
- **Deposit & Withdrawal** - Flexible money management with transaction history

### 📊 Dashboard & Analytics
- **Real-time Dashboard** - Overview of financial status at a glance
- **Transaction History** - Complete log of all financial activities
- **Category Analysis** - Spending breakdown by category
- **Monthly Reports** - Income vs. expenses comparison

### 👤 User Management
- **Secure Authentication** - Email/password login with password reset functionality
- **Google OAuth** - Sign in with Google account
- **Profile Management** - Update personal information and profile photo
- **Settings** - Configure recovery emails, phone numbers, and preferences
- **Activity Logging** - Automatic tracking of all user actions

### 🎨 User Experience
- **Smooth Page Transitions** - Elegant navigation with fade effects
- **Responsive Design** - Works seamlessly on desktop and mobile devices
- **Modern UI** - Clean, intuitive interface with consistent color palette
- **Real-time Updates** - Instant reflection of changes across the application

---

## 🛠 Technology Stack

### Frontend
- **HTML5** - Semantic markup and structure
- **CSS3** - Modern styling with CSS variables and animations
- **JavaScript (ES6+)** - Client-side interactivity and API communication
- **Font Awesome** - Icon library for UI elements
- **Google Fonts (Poppins, Inter)** - Typography

### Backend
- **PHP 7.4+** - Server-side logic and API endpoints
- **MySQLi** - Database connectivity and queries
- **MySQL/MariaDB** - Relational database management

### Development Environment
- **XAMPP** - Local development server (Apache + MySQL)
- **phpMyAdmin** - Database management interface

### Additional Technologies
- **Google OAuth 2.0** - Third-party authentication
- **bcrypt** - Secure password hashing
- **JSON** - Data exchange format
- **Session Management** - User authentication state

---

## 📦 Installation & Setup

### Prerequisites

Before installing Spendy, ensure you have the following installed:

1. **XAMPP** (or similar local server environment)
   - Download from: [https://www.apachefriends.org/](https://www.apachefriends.org/)
   - Includes Apache web server and MySQL database
   - Version: XAMPP 7.4+ or 8.0+

2. **Web Browser**
   - Chrome, Firefox, Edge, or Safari (latest versions recommended)

3. **Text Editor** (optional)
   - For editing configuration files if needed

---

### Step 1: Database Setup

#### 1.1 Start XAMPP Services

1. Open **XAMPP Control Panel**
2. Start **Apache** service (click "Start" button)
3. Start **MySQL** service (click "Start" button)
4. Ensure both services show "Running" status (green)

#### 1.2 Access phpMyAdmin

1. Open your web browser
2. Navigate to: `http://localhost/phpmyadmin`
3. You should see the phpMyAdmin interface

#### 1.3 Create the Database

**Option A: Using SQL Tab (Recommended)**

1. In phpMyAdmin, click on the **"SQL"** tab at the top
2. Copy the **entire contents** of `spendy_final_database.sql` file
3. Paste the SQL code into the SQL text area
4. Click the **"Go"** button to execute
5. You should see a success message: "X queries executed successfully"

**Option B: Using Import Tab**

1. In phpMyAdmin, click on the **"Import"** tab
2. Click **"Choose File"** button
3. Select `spendy_final_database.sql` from the project root folder
4. Click **"Go"** at the bottom
5. Wait for the import to complete

#### 1.4 Verify Database Installation

1. In phpMyAdmin, click on **"spendy_db"** in the left sidebar
2. You should see **8 tables**:
   - `users`
   - `password_reset_tokens`
   - `settings`
   - `expenses`
   - `income`
   - `savings`
   - `transaction_log`
   - `activity_log`
3. Click on the **"users"** table to verify sample data exists

#### 1.5 (Optional) Install Views and Triggers

For enhanced functionality and automatic activity tracking:

1. In phpMyAdmin, select the **"spendy_db"** database
2. Go to the **"SQL"** tab
3. Copy the **entire contents** of `spendy_views_and_triggers_fixed.sql`
4. Paste and click **"Go"**
5. This will create database views and automatic activity tracking triggers

**Note:** The fixed version corrects references to match the actual database schema. If you encounter errors with the original views file, use the `_fixed` version instead.

---

### Step 2: File Setup

#### 2.1 Place Project in htdocs

1. Navigate to your XAMPP installation directory:
   - **Windows**: `C:\xampp\htdocs\`
   - **Mac**: `/Applications/XAMPP/htdocs/`
   - **Linux**: `/opt/lampp/htdocs/`

2. Copy the entire **Spendy** folder to the `htdocs` directory
3. The final path should be:
   - **Windows**: `C:\xampp\htdocs\Spendy\`
   - **Mac/Linux**: `htdocs/Spendy/`

#### 2.2 Configure Database Connection

The database connection is already configured for local development. The default settings in `includes/db.php` are:

```php
$host = "localhost";
$user = "root";
$pass = "";  // Empty password (default XAMPP MySQL)
$dbname = "spendy_db";
```

**If your MySQL has a password:**
1. Open `includes/db.php`
2. Update the `$pass` variable with your MySQL root password:
   ```php
   $pass = "your_mysql_password";
   ```

**For Production Deployment:**
- Update `includes/db.php` with production database credentials
- Ensure database name matches your production database

#### 2.3 Verify File Structure

Ensure your project structure looks like this:

```
Spendy/
├── api/              # PHP API endpoints
├── views/            # HTML view files
├── includes/         # PHP includes (db.php, config.php, etc.)
├── images/           # Image assets
├── js/               # JavaScript files
├── index.html        # Main entry point
├── spendy_final_database.sql
└── spendy_views_and_triggers.sql
```

---

### Step 3: Starting the Application

#### 3.1 Access the Application

1. Ensure **Apache** and **MySQL** are running in XAMPP Control Panel
2. Open your web browser
3. Navigate to: `http://localhost/Spendy/`
4. You should see the Spendy landing page

#### 3.2 Test Default Credentials

The database includes sample users for testing. However, **passwords are hashed** and you'll need to:

**Option A: Create a New Account**
1. Click **"Sign up"** on the landing page
2. Fill in the registration form
3. Create your account

**Option B: Reset Sample User Password**
1. Go to the login page
2. Click **"Forgot password?"**
3. Use one of the sample emails:
   - `christina@example.com`
   - `vilia@example.com`
   - `carla@example.com`
4. Follow the password reset process

---

## 🚀 How to Use the Application

### Getting Started

#### 1. Create an Account

1. Navigate to the landing page (`http://localhost/Spendy/`)
2. Click **"Sign up"** or **"Get Started"**
3. Fill in your information:
   - First Name
   - Last Name
   - Email Address
   - Password (minimum 8 characters)
   - Birthday, Phone, Gender, Nationality, Address (optional)
4. Accept the Terms & Conditions
5. Click **"Sign Up"**
6. You'll be redirected to the login page

#### 2. Sign In

1. Enter your **email** and **password**
2. Click **"Sign In"**
3. You'll be redirected to the **Dashboard (Expenses)** page

**Alternative: Sign in with Google**
- Click the **"Sign in with Google"** button
- Authorize the application with your Google account
- After successful authentication, you'll be redirected to the **Dashboard (Expenses)** page
- **Note:** Make sure Google OAuth credentials are configured in `includes/google_oauth.php`

---

### Core Features Guide

#### 💰 Managing Income

**Adding Income:**
1. Navigate to **Dashboard Income** (click "Income" button or sidebar)
2. Click the **"+"** button or "Add Income" button
3. Fill in the form:
   - **Amount** - Enter the income amount
   - **Category** - Select from: Salary, Allowance, Business, Other Income, etc.
   - **Date** - Select the date from the calendar
   - **Note** (optional) - Add any additional information
4. Click **"Save Transaction"**

**Auto-Save Feature:**
- When you add income of **₱20,000 or more**, a popup will appear
- The system automatically calculates **5%** of the income
- Choose to allocate to an **Active Plan** or **Ended Plan** (which will be reactivated)
- Select the specific savings plan
- Confirm to save the amount automatically

**Viewing Income:**
- **Dashboard Income** - View income for a specific date
- **Monthly Income** - View all income for a selected month with category breakdown

---

#### 💸 Managing Expenses

**Adding Expenses:**
1. From the **Dashboard Expenses** page
2. Click the **"+"** button
3. Fill in the form:
   - **Amount** - Enter the expense amount
   - **Category** - Select from: Food, Transportation, Groceries, Entertainment, etc.
   - **Date** - Select the date
   - **Note** (optional) - Add description
   - **Fund Source** - Select where the money came from
4. Click **"Save Transaction"**

**Viewing Expenses:**
- **Dashboard Expenses** - View expenses for a specific date with remaining budget
- **Monthly Expenses** - View all expenses for a month with category analysis

---

#### 🎯 Savings Plans

**Creating a Savings Plan:**
1. Navigate to **Savings** page (sidebar icon)
2. Click **"Add Plan"** or the **"+"** button
3. Fill in the form:
   - **Plan Name** - Give your savings goal a name (e.g., "Emergency Fund")
   - **Goal Amount** - Enter your target amount
   - **Start Date** - When you want to start saving
   - **End Date** - Target completion date
   - **Duration** - Select: 3, 6, 12, or 24 months
   - **Monthly Budget** (optional) - Planned monthly contribution
4. Click **"Create Plan"**

**Managing Savings:**
- **View Plans** - See all your savings plans with progress bars
- **Add Money** - Click on a plan, then "Save Money" to deposit funds
- **Withdraw Money** - Click "Withdraw" to take money out (only if plan is unlocked)
- **Edit Plan** - Modify plan details (name, dates, amounts)
- **Lock/Unlock** - Lock a plan to prevent withdrawals

**Depositing Money:**
1. Click on a savings plan card
2. Click **"Save Money"** button
3. Enter the amount
4. Select **Fund Source**:
   - Remaining Budget
   - Main Wallet Account
   - Non-Enrolled Account (requires bank details)
5. Click **"Save"**

**Withdrawing Money:**
1. Click on a savings plan (must be unlocked)
2. Click **"Withdraw"** button
3. Enter the amount
4. Select **Destination Account**:
   - Main Wallet Account (increases remaining budget)
   - Non-Enrolled Account
5. Click **"Withdraw"**

---

#### 📊 Dashboard Overview

The **Dashboard Expenses** page provides:
- **Balance Snapshot** - Total income minus expenses
- **Remaining Budget** - Available funds after expenses and savings
- **Transaction History** - Recent expenses for the selected date
- **Quick Actions** - Add expense, view monthly reports

The **Dashboard Income** page provides:
- **Income Summary** - Total income for the selected date
- **Transaction History** - Recent income entries
- **Quick Actions** - Add income, view monthly reports

---

#### 👤 Profile & Settings

**Updating Profile:**
1. Click your **profile icon** in the header
2. Navigate to **Profile** page
3. Update any information:
   - Personal details (name, birthday, phone, etc.)
   - Profile photo (click to upload)
4. Click **"Save Changes"**

**Account Settings:**
1. Click the **Settings** icon in the sidebar
2. Configure:
   - **Recovery Emails** - Add backup email addresses
   - **Recovery Phones** - Add backup phone numbers
   - **Email Verification** - Verify your email address
   - **Change Password** - Update your account password
3. Changes are saved automatically

---

#### 📈 Monthly Reports

**View Monthly Income:**
1. Navigate to **Monthly Income** page
2. Select a month from the dropdown
3. View:
   - Total income for the month
   - Income by category
   - Individual transactions

**View Monthly Expenses:**
1. Navigate to **Monthly Expenses** page
2. Select a month from the dropdown
3. View:
   - Total expenses for the month
   - Expenses by category
   - Individual transactions

---

### Advanced Features

#### 🔄 Auto-Save on Income

When you add income of **₱20,000 or more**:
1. A popup automatically appears
2. Shows **5% of your income** as suggested savings
3. Choose plan type:
   - **Active Plan** - Add to existing active savings plan
   - **Ended Plan** - Reactivate a completed plan
4. Select the specific plan
5. Confirm to automatically save

#### 💾 Remaining Budget Tracking

- When you save money using **"Remaining Budget"** as the source:
  - The remaining budget automatically decreases
  - This ensures accurate budget calculations
- When you withdraw to **"Main Wallet Account"**:
  - The remaining budget automatically increases
  - Money returns to your available funds

#### 📝 Activity Logging

All user actions are automatically logged:
- User registrations
- Profile updates
- Income/expense additions
- Savings plan creation and updates
- Transactions (deposits/withdrawals)
- Settings changes

View activity logs through the API or database.

---

## 📁 Project Structure

```
Spendy/
├── api/                          # API Endpoints (PHP)
│   ├── login.php                 # User authentication
│   ├── signup.php                # User registration
│   ├── add_income.php            # Add income transaction
│   ├── add_expense.php           # Add expense transaction
│   ├── get_dashboard_stats.php   # Dashboard statistics
│   ├── draw_save_money.php       # Savings deposits/withdrawals
│   ├── auto_save_income.php      # Auto-save functionality
│   └── ...                       # Other API endpoints
│
├── views/                        # HTML View Files
│   ├── login.html                # Login page
│   ├── SignUp.html               # Registration page
│   ├── savings.html              # Savings plans page
│   ├── DashboardExpenses.html    # Expenses dashboard
│   ├── DashboardIncome.html      # Income dashboard
│   ├── profile.html              # User profile
│   ├── settings.html             # Account settings
│   └── ...                       # Other view files
│
├── includes/                     # PHP Includes
│   ├── db.php                    # Database connection
│   ├── activity_logger.php       # Activity logging functions
│   ├── config.php                # Application configuration
│   └── google_oauth.php          # Google OAuth configuration
│
├── images/                       # Image Assets
│   ├── logo.png                  # Application logo
│   ├── blankprofile.png          # Default profile picture
│   └── ...                       # Other images
│
├── js/                           # JavaScript Files
│   ├── shared-navigation.js      # Navigation system
│   ├── smooth-transitions.js     # Page transition effects
│   └── notification-system.js    # Notification handling
│
├── index.html                    # Landing page (entry point)
├── spendy_final_database.sql          # Database schema and sample data
├── spendy_views_and_triggers_fixed.sql # Database views and triggers (fixed)
└── README.md                          # This file
```

---

## 🗄 Database Schema

### Tables

1. **users** - User accounts and profile information
2. **password_reset_tokens** - Password reset token management
3. **settings** - User settings and recovery information
4. **income** - Income transactions
5. **expenses** - Expense transactions
6. **savings** - Savings plans and goals
7. **transaction_log** - Savings plan deposits and withdrawals
8. **activity_log** - User activity tracking

### Database Views (Optional)

Install `spendy_views_and_triggers_fixed.sql` for:
- User profile summaries (combines users with settings)
- Financial summaries (aggregated income, expenses, savings)
- Monthly income/expense aggregations
- Savings plan details with transaction counts
- Transaction history with user information
- Activity timelines
- Category spending/income summaries
- Active and ended savings plans views

**Important:** The fixed version (`spendy_views_and_triggers_fixed.sql`) corrects column references to match the actual database schema. Use this version to avoid "invalid table/column" errors.

### Automatic Triggers

The triggers file also creates automatic activity logging for:
- User registrations
- Profile updates
- Income/expense changes (create, update, delete)
- Savings plan modifications
- Transaction logging (deposits/withdrawals)
- Settings updates
- Password reset requests and completions

---

## 🔧 Troubleshooting

### Common Issues

#### Database Connection Error

**Problem:** "Connection failed" error when accessing the application

**Solutions:**
1. Ensure MySQL is running in XAMPP Control Panel
2. Check `includes/db.php` has correct credentials:
   ```php
   $host = "localhost";
   $user = "root";
   $pass = "";  // Your MySQL password if set
   $dbname = "spendy_db";
   ```
3. Verify database `spendy_db` exists in phpMyAdmin
4. Restart MySQL service in XAMPP

#### 404 Errors on Pages

**Problem:** Pages not loading or showing 404 errors

**Solutions:**
1. Verify project is in `htdocs/Spendy/` folder
2. Check URL is correct: `http://localhost/Spendy/`
3. Ensure Apache is running in XAMPP
4. Check file paths in browser console (F12)

#### Images Not Loading

**Problem:** Images appear broken or missing (e.g., hideicon.png, unhide.png)

**Solutions:**
1. Verify `images/` folder exists in project root
2. Check image paths in HTML files:
   - From `views/` folder: Use `../images/` (e.g., `../images/hideicon.png`)
   - From root folder: Use `images/` (e.g., `images/logo.png`)
3. Clear browser cache (Ctrl+Shift+R or Cmd+Shift+R)
4. Check browser console (F12) for 404 errors and verify file paths

#### API Calls Failing

**Problem:** Features not working, errors in browser console

**Solutions:**
1. Open browser Developer Tools (F12)
2. Check Console tab for JavaScript errors
3. Check Network tab for failed API requests
4. Verify API files are in `api/` folder
5. Ensure database connection is working

#### Google OAuth Issues

**Problem:** "Error 400: redirect_uri_mismatch" when signing in with Google

**Solutions:**
1. The redirect URI is now dynamically generated based on your server configuration
2. To get the correct redirect URI, access: `http://localhost/Spendy/api/debug_redirect_uri.php`
3. Copy the displayed redirect URI
4. Add it to your Google Cloud Console OAuth 2.0 credentials:
   - Go to [Google Cloud Console](https://console.cloud.google.com/)
   - Navigate to APIs & Services > Credentials
   - Edit your OAuth 2.0 Client ID
   - Add the redirect URI to "Authorized redirect URIs"
5. Ensure `includes/google_oauth.php` has your correct Client ID and Client Secret

#### Session Issues

**Problem:** Getting logged out frequently or can't stay logged in

**Solutions:**
1. Clear browser cookies for localhost
2. Check PHP session configuration in `php.ini`
3. Ensure `session_start()` is called in API files
4. Verify file permissions allow session storage

---

## 📝 Default Test Data

The database includes sample data for testing:

**Sample Users:**
- Email: `christina@example.com`
- Email: `vilia@example.com`
- Email: `carla@example.com`

**Note:** Passwords are hashed. To use these accounts:
1. Use "Forgot Password" feature
2. Or create new accounts through registration

**Sample Data Includes:**
- 3 user accounts
- Sample income and expense records
- 6 savings plans (active, locked, and ended)
- Transaction history
- Activity logs

---

## 🤝 Contributing

This is a personal project, but suggestions and improvements are welcome!

### Development Guidelines

1. Follow the existing folder structure
2. Use consistent naming conventions
3. Update paths when moving files
4. Test all changes thoroughly
5. Update documentation for new features

---

## 📄 License

This project is for educational and personal use.

---

## 👥 Credits

**Spendy Development Team**

Built with ❤️ for better financial management.

---

## 📞 Support

For issues or questions:
1. Check the Troubleshooting section above
2. Review the `PROJECT_STRUCTURE.md` file
3. Check browser console for error messages
4. Verify database and server configurations

---

**Last Updated:** December 2025  
**Version:** 1.1

### Recent Updates (v1.1)

- ✅ Fixed Google OAuth redirect URI mismatch (dynamic URI generation)
- ✅ Updated Google sign-in redirect to Dashboard Expenses page
- ✅ Fixed image path issues for password visibility icons
- ✅ Corrected database views to match actual schema
- ✅ Fixed stat sub-labels visibility in dashboard pages
- ✅ Improved responsive design for login and signup pages

---

*Happy Budgeting! 💰*

