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

### User Navigation Flow

When accessing the web application, the user is automatically directed to `index.html`, which serves as the homepage.

From there, the user can explore other pages such as:
- **About** - Learn more about Spendy and our mission
- **Features** - Discover key features of the application
- **Sign Up** - Create a new account (if they do not have an account)
- **Sign In** - Access their existing account (if they already have an account)

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

**Google Sign-In Note:**
- **"Sign in with Google"** only works for local testers
- If you wish to use this feature, you may request the developers of Spendy to add your Gmail account as one of the local testers

---

### Core Features Guide

#### 💰 Managing Income

**Understanding Income Management:**
Income transactions are the foundation of your financial tracking. Every income entry increases your total balance and affects your remaining budget calculations. The system tracks income by date and category, allowing you to see patterns in your earnings over time.

**Adding Income - Step by Step:**
1. Navigate to **Dashboard Income** page
   - Click the **"Income"** button in the toggle group on Dashboard Expenses, OR
   - Click the **"Income"** icon in the sidebar navigation
2. Locate the **"Add Transaction"** panel on the right side of the page
3. Fill in the income form:
   - **Amount** - Enter the income amount in Philippine Peso (₱)
     - *Example: 25000 for ₱25,000*
   - **Category** - Select from available categories:
     - Salary (regular employment income)
     - Allowance (personal allowance or stipend)
     - Business (business-related income)
     - Other Income (any other income source)
   - **Date** - Click on the calendar to select the date of the income
     - The calendar shows the current month by default
     - Click any day to select it (highlighted in cyan)
   - **Note** (optional) - Add any additional information about this income
     - *Example: "Monthly salary from Company XYZ"*
4. Click **"Save Transaction"** button
5. The income will be saved and immediately reflected in your dashboard

**How Income Affects Your Balance:**
- When you add income, it is added to your **Total Balance**
- **Total Balance** = Sum of all income - Sum of all expenses
- The income also increases your **Remaining Budget** for that month
- **Remaining Budget** = Monthly Income - Monthly Expenses - Savings from Remaining Budget

**Auto-Save Feature - Logic and Calculation:**
The system includes an intelligent auto-save feature that encourages savings when you receive significant income.

**How It Works:**
1. **Trigger Condition:** When you add income of **₱20,000 or more**, the system automatically detects this threshold
2. **Calculation:** The system calculates **5% of the income amount**
   - *Example: If income is ₱25,000, suggested savings = ₱25,000 × 0.05 = ₱1,250*
3. **Modal Popup:** A popup window appears automatically with:
   - The calculated savings amount (5% of income)
   - Two options for plan type:
     - **Active Plan** - Allocate to an existing active savings plan
     - **Ended Plan** - Reactivate a completed plan and add to it
   - A dropdown to select the specific savings plan
4. **User Decision:** You can:
   - Accept the suggestion and allocate the 5% to a savings plan
   - Decline and skip the auto-save (income is still saved)
5. **Automatic Allocation:** If you confirm:
   - The calculated amount is automatically deposited to the selected savings plan
   - The savings transaction is recorded
   - Your remaining budget is adjusted accordingly

**Viewing Income:**
- **Dashboard Income** - View all income transactions for a specific date
  - Transactions are displayed in chronological order
  - Shows category, amount, and time of entry
- **Monthly Income** - View comprehensive income data for a selected month
  - Total income for the month
  - Income breakdown by category (pie chart or list)
  - Individual transaction details with dates

---

#### 💸 Managing Expenses

**Understanding Expense Management:**
Expenses represent money spent from your available funds. Each expense reduces your total balance and remaining budget. The system tracks expenses by date, category, and fund source, providing detailed insights into your spending patterns.

**Adding Expenses - Step by Step:**
1. Navigate to **Dashboard Expenses** page (default landing page after login)
2. Locate the **"Add Transaction"** panel on the right side of the page
3. Fill in the expense form:
   - **Amount** - Enter the expense amount in Philippine Peso (₱)
     - *Example: 500 for ₱500*
     - Must be a positive number
   - **Category** - Select from available expense categories:
     - Food (meals, restaurants, snacks)
     - Traffic/Transportation (commute, gas, parking)
     - Shopping (clothing, accessories, personal items)
     - Groceries (household items, food shopping)
     - Rent (housing costs)
     - Home (utilities, maintenance, home supplies)
     - Gifts (presents, donations)
     - Recurring (subscriptions, bills)
     - Other (any other expense)
   - **Date** - Click on the calendar to select the date of the expense
     - The calendar shows the current month by default
     - Click any day to select it (highlighted in cyan)
     - Today's date is highlighted with a light cyan background
   - **Note** (optional) - Add a description or details about the expense
     - *Example: "Lunch at restaurant" or "Monthly internet bill"*
   - **Fund Source** - Select where the money came from:
     - This helps track which account or source was used
     - Options may include: Main Wallet, Remaining Budget, etc.
4. Click **"Save Transaction"** button
5. The expense will be saved and immediately reflected in your dashboard

**How Expenses Affect Your Balance - Calculation Logic:**
Understanding how expenses impact your financial status is crucial for budget management.

**Balance Calculation:**
- **Total Balance** = Sum of all income - Sum of all expenses
  - *Example: If you have ₱30,000 income and ₱10,000 expenses, Total Balance = ₱20,000*
- When you add an expense:
  - Your **Total Balance** decreases by the expense amount
  - Your **Remaining Budget** for the month decreases
  - The expense is categorized and stored for reporting

**Remaining Budget Calculation:**
The remaining budget is calculated dynamically based on your monthly financial activity:
- **Remaining Budget** = Monthly Income - Monthly Expenses - Savings from Remaining Budget
- This calculation updates in real-time as you add expenses
- The remaining budget is displayed in the "Budget Left" pill on the dashboard
- If remaining budget becomes negative, it indicates overspending for the month

**Example Scenario:**
1. Monthly Income: ₱25,000
2. Monthly Expenses: ₱15,000
3. Savings from Remaining Budget: ₱2,000
4. **Remaining Budget** = ₱25,000 - ₱15,000 - ₱2,000 = **₱8,000**

**Viewing Expenses:**
- **Dashboard Expenses** - View all expense transactions for a specific date
  - Transactions are displayed in chronological order (newest first)
  - Shows category icon, category name, note, amount, and time
  - Displays remaining budget for the selected date
  - Date can be changed using the calendar button
- **Monthly Expenses** - View comprehensive expense data for a selected month
  - Total expenses for the month
  - Expense breakdown by category (visual charts)
  - Individual transaction details with dates and amounts
  - Category-wise spending analysis

---

#### 🎯 Savings Plans

**Understanding Savings Plans:**
Savings plans help you set and achieve financial goals. Each plan has a target amount, and you can track your progress toward that goal. The system automatically calculates progress percentages and adjusts your remaining budget based on deposits and withdrawals.

**Creating a Savings Plan - Step by Step:**
1. Navigate to **Savings** page
   - Click the **Savings** icon in the sidebar (second icon from top)
2. Click **"Add Plan"** button or the **"+"** button
3. Fill in the savings plan form:
   - **Plan Name** - Give your savings goal a descriptive name
     - *Example: "Emergency Fund", "Vacation Fund", "New Laptop"*
   - **Goal Amount** - Enter your target savings amount in Philippine Peso (₱)
     - *Example: 50000 for ₱50,000*
     - This is the total amount you want to save
   - **Start Date** - Select when you want to start saving
     - Use the date picker to select a start date
   - **End Date** - Select your target completion date
     - This is when you plan to reach your goal
   - **Duration** - Select a preset duration (optional helper):
     - 3 months
     - 6 months
     - 12 months
     - 24 months
     - *Note: Selecting a duration automatically calculates the end date*
   - **Monthly Budget** (optional) - Enter your planned monthly contribution
     - *Example: 5000 for ₱5,000 per month*
     - This helps you plan your savings strategy
4. Click **"Create Plan"** button
5. The plan will be created and appear in your savings plans list

**How Savings Plans Adjust Based on Input:**
The system dynamically adjusts savings plan calculations based on your inputs and transactions.

**Progress Calculation:**
- **Current Amount** = Sum of all deposits - Sum of all withdrawals
- **Progress Percentage** = (Current Amount ÷ Goal Amount) × 100
- **Remaining Amount** = Goal Amount - Current Amount
- *Example: If Goal is ₱50,000 and Current is ₱15,000, Progress = 30%, Remaining = ₱35,000*

**Plan Status Logic:**
- **Active Plan:** Current Amount < Goal Amount and End Date hasn't passed
- **Completed Plan:** Current Amount ≥ Goal Amount (regardless of date)
- **Ended Plan:** End Date has passed but Current Amount < Goal Amount
- Plans can be reactivated if you add more money to them

**Managing Savings - Detailed Operations:**

**Viewing Plans:**
- All savings plans are displayed as cards on the Savings page
- Each card shows:
  - Plan name
  - Current amount saved
  - Goal amount
  - Progress bar (visual indicator)
  - Progress percentage
  - Status (Active, Completed, Ended)
  - Lock status (locked/unlocked icon)

**Depositing Money - Step by Step:**
1. Click on a savings plan card to open its details
2. Click the **"Save Money"** button
3. Enter the deposit amount in Philippine Peso (₱)
   - *Example: 2000 for ₱2,000*
   - The amount must be positive
4. Select **Fund Source** from the dropdown:
   - **Remaining Budget** - Deducts from your available remaining budget
     - *Effect: Decreases your remaining budget by the deposit amount*
   - **Main Wallet Account** - Uses funds from your main wallet
     - *Effect: No impact on remaining budget calculation*
   - **Non-Enrolled Account** - External account (requires bank details)
     - *Effect: No impact on remaining budget (external source)*
5. Click **"Save"** button
6. The deposit is processed and:
   - Current amount increases
   - Progress percentage recalculates automatically
   - Progress bar updates visually
   - Remaining budget adjusts (if using Remaining Budget source)
   - Transaction is logged in the activity log

**How Deposits Affect Your Budget:**
- **Using "Remaining Budget" as source:**
  - Remaining Budget decreases by the deposit amount
  - *Example: If Remaining Budget is ₱10,000 and you deposit ₱2,000, new Remaining Budget = ₱8,000*
  - This ensures your budget calculations remain accurate
- **Using "Main Wallet Account" or "Non-Enrolled Account":**
  - No impact on remaining budget
  - These sources are separate from your monthly budget calculations

**Withdrawing Money - Step by Step:**
1. Click on a savings plan card (plan must be **unlocked**)
   - *Note: Locked plans cannot have money withdrawn*
2. Click the **"Withdraw"** button
3. Enter the withdrawal amount in Philippine Peso (₱)
   - *Example: 1000 for ₱1,000*
   - Amount cannot exceed the current amount in the plan
4. Select **Destination Account** from the dropdown:
   - **Main Wallet Account** - Returns money to your main wallet
     - *Effect: Increases your remaining budget by the withdrawal amount*
   - **Non-Enrolled Account** - External account (requires bank details)
     - *Effect: No impact on remaining budget (external destination)*
5. Click **"Withdraw"** button
6. The withdrawal is processed and:
   - Current amount decreases
   - Progress percentage recalculates automatically
   - Progress bar updates visually
   - Remaining budget increases (if withdrawing to Main Wallet)
   - Transaction is logged in the activity log

**How Withdrawals Affect Your Budget:**
- **Withdrawing to "Main Wallet Account":**
  - Remaining Budget increases by the withdrawal amount
  - *Example: If Remaining Budget is ₱8,000 and you withdraw ₱1,000, new Remaining Budget = ₱9,000*
  - Money returns to your available funds
- **Withdrawing to "Non-Enrolled Account":**
  - No impact on remaining budget
  - Money is transferred externally

**Locking and Unlocking Plans:**
- **Lock a Plan:** Prevents any withdrawals from the plan
  - Useful for protecting savings goals
  - Locked plans show a lock icon
- **Unlock a Plan:** Allows withdrawals again
  - You can toggle lock status as needed
- Lock status does not affect deposits (you can always add money)

**Editing Plans:**
- Click on a plan card to view details
- Click **"Edit Plan"** button
- Modify any plan details:
  - Plan name
  - Goal amount
  - Start date
  - End date
  - Monthly budget
- Changes update the plan calculations automatically

---

#### 📊 Dashboard Overview

**Understanding the Dashboard:**
The dashboard provides a real-time overview of your financial status. All calculations update automatically as you add income, expenses, or savings transactions. The dashboard is divided into two main views: Expenses Dashboard and Income Dashboard.

**Dashboard Expenses Page - Detailed Breakdown:**

**Monthly Budget Snapshot Card:**
- **Total Balance** - Calculated as: Sum of all income - Sum of all expenses
  - Displays in large, prominent text
  - Updates in real-time when transactions are added
- **Budget Left** - Shows remaining available funds
  - Calculated as: Monthly Income - Monthly Expenses - Savings from Remaining Budget
  - Displayed in a pill/badge format
  - Color-coded for quick visual reference
- **Month Selector** - Button showing current month
  - Click to view different months
  - Displays month name with arrow icon

**Financial Overview Card:**
Displays four key financial metrics in a grid layout:
1. **Total Balance**
   - Shows remaining funds after all transactions
   - Includes percentage change indicator (green for positive, red for negative)
2. **Expenses**
   - Total expenses for the selected month
   - Shows percentage change from previous period
3. **Income**
   - Total income for the selected month
   - Shows percentage change from previous period
4. **Savings**
   - Total savings added during the month
   - Shows percentage change from previous period

Each metric card includes:
- Label (e.g., "Total balance")
- Value (formatted with ₱ symbol and commas)
- Change indicator (arrow icon + percentage)
- Sub-label (e.g., "Remaining funds")
- Visual wave pattern at the bottom

**Transactions Section:**
- **Date Header** - Shows selected date in format: "Day, DD Month"
  - Includes date mode indicator (24h)
  - Calendar button to change date
- **Transaction List** - Displays all expenses for the selected date
  - Each transaction shows:
    - Category icon (visual identifier)
    - Category name
    - Note/description
    - Amount (in red, with ₱ symbol)
    - Time of transaction
  - Transactions are sorted by time (newest first)
  - Empty state message if no transactions for the date

**Dashboard Income Page - Detailed Breakdown:**

**Income Summary:**
- **Total Income** - Sum of all income for the selected date
  - Displayed prominently
  - Updates automatically when income is added
- **Date Display** - Shows the selected date
  - Can be changed using the calendar

**Transaction History:**
- Lists all income transactions for the selected date
- Each transaction shows:
  - Category icon
  - Category name
  - Note/description
  - Amount (in green, with ₱ symbol)
  - Time of transaction
- Transactions sorted chronologically

**Quick Actions Available:**
- **Add Transaction** - Opens the add transaction panel
- **View Monthly Reports** - Navigate to monthly income/expense reports
- **Calendar Navigation** - Switch between dates quickly

---

#### 🔄 Data Updating and Real-Time Synchronization

**Understanding Data Updates:**
The Spendy application uses real-time data synchronization to ensure all calculations and displays are always current. When you make any changes (add income, add expenses, modify savings), the system automatically updates all related displays and calculations.

**How Data Updates Work:**

**1. Adding Income:**
- When you save an income transaction:
  - The income is immediately saved to the database
  - Dashboard statistics are recalculated automatically
  - Total balance increases
  - Remaining budget increases
  - Financial overview cards update
  - Transaction list refreshes to show the new entry
  - If income is ₱20,000+, auto-save modal appears

**2. Adding Expenses:**
- When you save an expense transaction:
  - The expense is immediately saved to the database
  - Dashboard statistics are recalculated automatically
  - Total balance decreases
  - Remaining budget decreases
  - Financial overview cards update
  - Transaction list refreshes to show the new entry
  - Budget left pill updates in real-time

**3. Savings Plan Operations:**
- When you deposit money to a savings plan:
  - Savings plan current amount increases
  - Progress percentage recalculates
  - Progress bar updates visually
  - If using "Remaining Budget" source, budget decreases
  - Dashboard statistics update
  - Transaction log is updated
- When you withdraw money from a savings plan:
  - Savings plan current amount decreases
  - Progress percentage recalculates
  - Progress bar updates visually
  - If withdrawing to "Main Wallet", budget increases
  - Dashboard statistics update
  - Transaction log is updated

**4. Profile and Settings Updates:**
- Profile changes save immediately
- Settings changes are applied instantly
- No page refresh required for most updates

**Automatic Calculations:**
The system performs these calculations automatically whenever relevant data changes:
- **Total Balance** = Income - Expenses
- **Remaining Budget** = Monthly Income - Monthly Expenses - Savings from Remaining Budget
- **Progress Percentage** = (Current Savings ÷ Goal Amount) × 100
- **Category Totals** = Sum of all transactions in that category
- **Monthly Totals** = Sum of all transactions in that month

**Page Refresh Behavior:**
- Most updates happen without page refresh (AJAX)
- Some operations may trigger a page reload for consistency
- Data persists in the database immediately upon saving
- Browser refresh will always show the latest data

**Testing Data Updates:**
To test the real-time update functionality:
1. Open the Dashboard Expenses page
2. Note the current "Total Balance" and "Budget Left" values
3. Add a new expense transaction
4. Observe that both values update immediately without page refresh
5. Add income and verify the balance increases
6. Make a savings deposit and verify budget decreases (if using Remaining Budget source)

---

#### 👤 Profile & Settings

**Understanding Profile Management:**
Your profile contains personal information and account settings. Keeping your profile updated ensures accurate data and helps with account recovery if needed.

**Updating Profile - Step by Step:**
1. **Access Profile Page:**
   - Click your **profile icon** in the top-right corner of the header
   - The icon shows your profile photo (or default blank profile image)
   - You'll be redirected to the Profile page

2. **Update Personal Information:**
   - **First Name** - Your given name
   - **Last Name** - Your family name
   - **Email** - Your primary email address (used for login)
   - **Birthday** - Your date of birth (select from date picker)
   - **Phone Number** - Your contact number
   - **Gender** - Select from dropdown (Male, Female, Other, Prefer not to say)
   - **Nationality** - Your country of origin
   - **Address** - Your residential address

3. **Update Profile Photo:**
   - Click on your current profile photo (or blank profile placeholder)
   - A file picker will open
   - Select an image file from your device
   - Supported formats: JPG, PNG, GIF
   - Recommended size: Square images work best (e.g., 200x200px)
   - The image will be uploaded and displayed immediately

4. **Save Changes:**
   - Click the **"Save Changes"** button at the bottom of the form
   - A success message will confirm the update
   - Changes are saved to the database immediately
   - Your profile icon in the header will update with the new photo

**Account Settings - Step by Step:**
1. **Access Settings Page:**
   - Click the **Settings** icon in the sidebar (third icon from top)
   - You'll be redirected to the Settings page

2. **Configure Recovery Emails:**
   - **Purpose:** Backup email addresses for account recovery
   - Click **"Add Recovery Email"** button
   - Enter a valid email address
   - Click **"Add"** to save
   - You can add multiple recovery emails
   - Each email is listed separately
   - Click **"Remove"** next to any email to delete it

3. **Configure Recovery Phones:**
   - **Purpose:** Backup phone numbers for account recovery
   - Click **"Add Recovery Phone"** button
   - Enter a valid phone number
   - Click **"Add"** to save
   - You can add multiple recovery phone numbers
   - Each phone is listed separately
   - Click **"Remove"** next to any phone to delete it

4. **Email Verification:**
   - If your email is not verified, you'll see a verification option
   - Click **"Verify Email"** button
   - A verification email will be sent to your registered email
   - Follow the instructions in the email to verify
   - Once verified, the status will update

5. **Change Password:**
   - Click **"Change Password"** section
   - Enter your **current password**
   - Enter your **new password** (minimum 8 characters)
   - **Confirm new password** (must match new password)
   - Click **"Update Password"** button
   - Password is updated immediately
   - You'll need to use the new password for future logins

**Settings Auto-Save:**
- Most settings changes are saved automatically as you make them
- No "Save" button needed for recovery emails/phones
- Password changes require explicit confirmation
- All changes are logged in the activity log

---

#### 📈 Monthly Reports

**Understanding Monthly Reports:**
Monthly reports provide comprehensive analysis of your financial activity for any given month. These reports help you understand spending patterns, income sources, and identify areas for improvement in your budgeting strategy.

**View Monthly Income - Step by Step:**
1. **Navigate to Monthly Income Page:**
   - From Dashboard Income, click the calendar icon in the date header, OR
   - Use the navigation menu to access Monthly Income page
   - The page loads with the current month selected by default

2. **Select a Month:**
   - Use the month dropdown selector at the top of the page
   - Select any month from the available options
   - The report automatically updates to show data for the selected month

3. **View Income Summary:**
   - **Total Income** - Sum of all income transactions for the selected month
     - Displayed prominently at the top
     - Formatted with ₱ symbol and comma separators
   - **Income Breakdown by Category:**
     - Visual chart (pie chart or bar chart) showing category distribution
     - Each category shows:
       - Category name
       - Amount for that category
       - Percentage of total income
     - Categories may include: Salary, Allowance, Business, Other Income

4. **View Individual Transactions:**
   - Scroll down to see the transaction list
   - Each transaction entry shows:
     - Date of the transaction
     - Category name and icon
     - Amount (formatted with ₱ symbol)
     - Note/description (if provided)
   - Transactions are sorted by date (newest or oldest first)
   - You can see the complete history of income for that month

5. **Export or Print (if available):**
   - Some versions may include export functionality
   - Use browser print function (Ctrl+P / Cmd+P) to print the report

**View Monthly Expenses - Step by Step:**
1. **Navigate to Monthly Expenses Page:**
   - From Dashboard Expenses, click the calendar icon in the date header, OR
   - Use the navigation menu to access Monthly Expenses page
   - The page loads with the current month selected by default

2. **Select a Month:**
   - Use the month dropdown selector at the top of the page
   - Select any month from the available options
   - The report automatically updates to show data for the selected month

3. **View Expense Summary:**
   - **Total Expenses** - Sum of all expense transactions for the selected month
     - Displayed prominently at the top
     - Formatted with ₱ symbol and comma separators
   - **Expense Breakdown by Category:**
     - Visual chart showing category distribution
     - Each category shows:
       - Category name and icon
       - Amount spent in that category
       - Percentage of total expenses
     - Categories may include: Food, Transportation, Shopping, Groceries, Rent, Home, Gifts, Recurring, Other
   - **Category Analysis:**
     - See which categories consume the most of your budget
     - Identify spending patterns and trends
     - Compare category spending percentages

4. **View Individual Transactions:**
   - Scroll down to see the detailed transaction list
   - Each transaction entry shows:
     - Date of the transaction
     - Category name and icon
     - Amount (formatted with ₱ symbol, shown in red)
     - Note/description (if provided)
     - Time of transaction (if available)
   - Transactions are sorted by date
   - You can see the complete history of expenses for that month

5. **Analyze Spending Patterns:**
   - Compare total expenses to total income for the month
   - Identify which categories have the highest spending
   - Look for opportunities to reduce expenses in specific categories
   - Track progress toward budget goals

**Using Reports for Budget Planning:**
- Review past months to understand your spending habits
- Identify categories where you consistently overspend
- Set realistic budget goals based on historical data
- Track improvements in your financial management over time
- Use category breakdowns to prioritize savings goals

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

