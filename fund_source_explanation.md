# Why fund_source Exists in Expenses and Income Tables

## Current Situation

The `fund_source` column exists in both the `expenses` and `income` database tables, but it's **NOT being used** in the current implementation.

---

## Evidence

### 1. Database Schema
Both tables have the column defined:
```sql
CREATE TABLE expenses (
    ...
    fund_source VARCHAR(50),  -- ✅ Column exists
    ...
)

CREATE TABLE income (
    ...
    fund_source VARCHAR(50),  -- ✅ Column exists
    ...
)
```

### 2. INSERT Statements (NOT using fund_source)
**`api/add_expense.php` (Line 66):**
```php
$sql = "INSERT INTO expenses (expense_id, user_id, category, amount, note, date) 
        VALUES (...)";
// ❌ fund_source is NOT included in the INSERT
```

**`api/add_income.php` (Line 75):**
```php
$sql = "INSERT INTO income (income_id, user_id, category, amount, note, date) 
        VALUES (...)";
// ❌ fund_source is NOT included in the INSERT
```

### 3. HTML Forms (No fund_source field)
- **DashboardExpenses.html**: No fund_source input field
- **DashboardIncome.html**: No fund_source input field

---

## Why It's There (Possible Reasons)

### 1. **Planned Feature (Not Implemented)**
The column was likely added during initial database design with the intention to track where money came from/went to, but the feature was never fully implemented in the UI.

### 2. **Future-Proofing**
The column might have been added to allow for future enhancements without requiring database schema changes.

### 3. **Legacy/Incomplete Implementation**
It's possible that:
- An earlier version of the app used fund_source
- The feature was removed from the UI but the column was left in the database
- The implementation was started but never completed

### 4. **Design Consistency**
The database designer may have wanted consistency across all financial transaction tables, even if not all features use it.

---

## Current Behavior

- **Expenses**: `fund_source` column exists but is always `NULL` (not populated)
- **Income**: `fund_source` column exists but is always `NULL` (not populated)
- **Savings Transactions**: `fund_source` IS used (but stored differently - in activity_log, not transaction_log)

---

## What You Can Do

### Option 1: Remove the Column (If Not Needed)
If you don't plan to use fund_source for expenses/income:
```sql
ALTER TABLE expenses DROP COLUMN fund_source;
ALTER TABLE income DROP COLUMN fund_source;
```

### Option 2: Implement the Feature
If you want to use fund_source for expenses/income:
1. Add fund_source dropdown to expense/income forms
2. Update the INSERT statements to include fund_source
3. Use the same options as savings (GCash, Local Bank, Remaining Budget)

### Option 3: Leave It (For Future Use)
Keep the column for potential future features without any changes.

---

## Recommendation

Since the column exists but isn't being used, you have two practical options:

1. **Remove it** - Clean up unused columns to avoid confusion
2. **Implement it** - Add fund_source tracking to expenses/income for better financial tracking

The choice depends on whether you want to track the source of funds for expenses and income in the future.

