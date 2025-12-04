# Fund Source Data Source Documentation

## Overview
The `fund_source` field is stored in the database tables (`expenses`, `income`, and used in transaction logs), but the **source of the data** (the dropdown options) comes from different places depending on the feature.

---

## 📍 Where Fund Source Data Comes From

### 1. **Savings Transactions (Deposit/Withdraw)**
**Location:** `views/drawsavemoney.html`

**Source:** **Hardcoded in HTML** (Lines 727-736)

**Options:**
- `GCash`
- `Local Bank`
- `Remaining Budget`

**Code:**
```html
<select class="input custom-select-hidden" id="fundingSource">
    <option value="">Funding Source</option>
    <option value="GCash">GCash</option>
    <option value="Local Bank">Local Bank</option>
    <option value="Remaining Budget">Remaining Budget</option>
</select>
```

**For Withdrawals:**
- Uses destination accounts instead:
  - `Main Wallet Account`
  - `LandBank`
  - `Non-enrolled Account`
  - Custom bank accounts (e.g., `"Bank Name - Account (Receiver)"`)

---

### 2. **Income Transactions**
**Location:** `views/DashboardIncome.html`

**Current Status:** ❌ **No fund_source field in the form**

**Auto-Save Feature:**
- When auto-saving income to savings plans, uses: `"Auto-Save from Income"`
- Set in: `api/auto_save_income.php` (Line 36)

**Note:** The `income` table has a `fund_source` column, but it's not currently being used in the UI form.

---

### 3. **Expense Transactions**
**Location:** `views/DashboardExpenses.html`

**Current Status:** ❌ **No fund_source field in the form**

**Note:** The `expenses` table has a `fund_source` column, but it's not currently being used in the UI form or API.

---

## 🔧 How to Add/Modify Fund Source Options

### For Savings Transactions:
Edit `views/drawsavemoney.html` around **lines 727-736**:

```html
<div class="custom-dropdown-options">
    <div class="custom-dropdown-option" onclick="selectDropdownOption('fundingSource', 'GCash', 'fundingSourceDropdown')">GCash</div>
    <div class="custom-dropdown-option" onclick="selectDropdownOption('fundingSource', 'Local Bank', 'fundingSourceDropdown')">Local Bank</div>
    <div class="custom-dropdown-option" onclick="selectDropdownOption('fundingSource', 'Remaining Budget', 'fundingSourceDropdown')">Remaining Budget</div>
    <!-- Add more options here -->
</div>
<select class="input custom-select-hidden" id="fundingSource">
    <option value="">Funding Source</option>
    <option value="GCash">GCash</option>
    <option value="Local Bank">Local Bank</option>
    <option value="Remaining Budget">Remaining Budget</option>
    <!-- Add matching options here -->
</select>
```

---

## 💡 Recommendations

### Option 1: Keep Hardcoded (Current Approach)
- ✅ Simple and fast
- ❌ Requires code changes to add/modify options
- ❌ Not dynamic

### Option 2: Create a Database Table (Recommended for Scalability)
Create a `fund_sources` table:

```sql
CREATE TABLE fund_sources (
    fund_source_id INT AUTO_INCREMENT PRIMARY KEY,
    source_name VARCHAR(50) NOT NULL UNIQUE,
    source_type ENUM('deposit', 'withdrawal', 'both') DEFAULT 'both',
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default values
INSERT INTO fund_sources (source_name, source_type, display_order) VALUES
('GCash', 'both', 1),
('Local Bank', 'both', 2),
('Remaining Budget', 'deposit', 3),
('Main Wallet Account', 'withdrawal', 4),
('LandBank', 'withdrawal', 5);
```

Then create an API endpoint to fetch these options dynamically.

### Option 3: Configuration File
Store fund source options in a JSON or PHP config file and load them dynamically.

---

## 📊 Current Database Usage

The `fund_source` column exists in:
- ✅ `expenses` table - **Not currently used**
- ✅ `income` table - **Not currently used** (except auto-save)
- ✅ `transaction_log` - **Not stored** (fund_source is tracked separately for budget calculations)

---

## 🔍 Summary

**Fund Source Data Currently Comes From:**
1. **Savings Deposits/Withdrawals:** Hardcoded HTML dropdown in `drawsavemoney.html`
2. **Auto-Save from Income:** Hardcoded string `"Auto-Save from Income"` in PHP
3. **Expenses:** Not implemented (column exists but not used)
4. **Income:** Not implemented (column exists but not used)

**To modify fund source options:**
- Edit the HTML dropdown in `views/drawsavemoney.html`
- Or implement a database-driven solution for better scalability

