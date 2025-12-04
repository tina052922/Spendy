<?php
/**
 * get_notifications.php - Smart Savings Notifications
 * Provides intelligent notifications to help users save money:
 * - Alerts when expenses exceed income
 * - Alerts users to save money
 * - Reminds about regular deposits
 * - Warns about low savings progress
 * - Suggests saving amounts
 */

// Start output buffering first
ob_start();

// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

// Get user_id from session
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

if (empty($user_id)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'User not authenticated']);
    exit;
}

$notifications = [];
$now = new DateTime();

// ============================================
// 1. EXPENSES EXCEEDING INCOME ALERT
// ============================================
// Check if current month expenses exceed income
$currentMonth = date('Y-m');

// Get total expenses for current month
$expensesQuery = "SELECT COALESCE(SUM(amount), 0) as total_expenses
                  FROM expenses 
                  WHERE user_id = ? 
                  AND DATE_FORMAT(date, '%Y-%m') = ?";

$stmt = mysqli_prepare($conn, $expensesQuery);
$totalExpenses = 0;
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ss", $user_id, $currentMonth);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $totalExpenses = floatval($row['total_expenses']);
    }
    mysqli_stmt_close($stmt);
}

// Get total income for current month
$incomeQuery = "SELECT COALESCE(SUM(amount), 0) as total_income
                FROM income 
                WHERE user_id = ? 
                AND DATE_FORMAT(date, '%Y-%m') = ?";

$stmt = mysqli_prepare($conn, $incomeQuery);
$totalIncome = 0;
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ss", $user_id, $currentMonth);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $totalIncome = floatval($row['total_income']);
    }
    mysqli_stmt_close($stmt);
}

// Only show alert if there's actual data and expenses exceed income
if ($totalExpenses > 0 && $totalIncome > 0 && $totalExpenses > $totalIncome) {
    $excess = $totalExpenses - $totalIncome;
    $excessFormatted = number_format($excess, 2);
    $expensesFormatted = number_format($totalExpenses, 2);
    $incomeFormatted = number_format($totalIncome, 2);
    
    // Calculate percentage over
    $overPercent = round(($excess / $totalIncome) * 100);
    
    $message = "🚨 Your expenses (₱{$expensesFormatted}) exceed your income (₱{$incomeFormatted}) by ₱{$excessFormatted}. Please reduce your spending!";
    
    if ($overPercent > 50) {
        $message = "🚨 CRITICAL: Your expenses are {$overPercent}% higher than your income! You're spending ₱{$excessFormatted} more than you earn. Reduce expenses immediately!";
    } elseif ($overPercent > 25) {
        $message = "⚠️ WARNING: Your expenses exceed income by {$overPercent}% (₱{$excessFormatted} over). Please cut back on spending!";
    }
    
    $notifications[] = [
        'id' => 'expenses_exceed_income',
        'icon' => '🚨',
        'message' => $message,
        'time' => 'Just now',
        'timestamp' => $now->format('Y-m-d H:i:s'),
        'type' => 'expense_alert',
        'priority' => 0.5, // Very high priority - financial warning
        'expenses' => $totalExpenses,
        'income' => $totalIncome
    ];
}

// ============================================
// 2. PLANS NEEDING REGULAR DEPOSITS
// ============================================
// Check plans with monthly_budget that haven't been deposited to in 30+ days
$regularDepositQuery = "SELECT s.savings_id, s.plan_name, s.monthly_budget, s.saved_amount, s.goal_amount,
                               MAX(t.date) as last_deposit_date
                        FROM savings s
                        LEFT JOIN transaction_log t ON s.savings_id = t.savings_id 
                            AND t.transaction_type = 'deposit'
                            AND t.date IS NOT NULL
                        WHERE s.user_id = ? 
                        AND s.status = 'Active'
                        AND s.monthly_budget IS NOT NULL
                        AND s.monthly_budget > 0
                        GROUP BY s.savings_id, s.plan_name, s.monthly_budget, s.saved_amount, s.goal_amount
                        HAVING last_deposit_date IS NULL 
                            OR last_deposit_date < DATE_SUB(CURDATE(), INTERVAL 30 DAY)";

$stmt = mysqli_prepare($conn, $regularDepositQuery);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

    while ($plan = mysqli_fetch_assoc($result)) {
        $lastDeposit = $plan['last_deposit_date'];
        $daysSinceDeposit = 0;
        
        if ($lastDeposit) {
            $lastDepositDate = new DateTime($lastDeposit);
            $daysSinceDeposit = $now->diff($lastDepositDate)->days;
        } else {
            // Never deposited - check how old the plan is
            $planCreatedQuery = "SELECT created_at FROM savings WHERE savings_id = ?";
            $planStmt = mysqli_prepare($conn, $planCreatedQuery);
            if ($planStmt) {
                mysqli_stmt_bind_param($planStmt, "s", $plan['savings_id']);
                mysqli_stmt_execute($planStmt);
                $planResult = mysqli_stmt_get_result($planStmt);
                if ($planRow = mysqli_fetch_assoc($planResult)) {
                    $createdDate = new DateTime($planRow['created_at']);
                    $daysSinceDeposit = $now->diff($createdDate)->days;
                }
                mysqli_stmt_close($planStmt);
            }
        }
        
        if ($daysSinceDeposit >= 30) {
            $monthlyBudget = number_format($plan['monthly_budget'], 2);
            $message = "Time to save! '{$plan['plan_name']}' needs your monthly deposit of ₱{$monthlyBudget}";
            
            if ($daysSinceDeposit >= 60) {
                $message = "⚠️ You haven't saved to '{$plan['plan_name']}' in " . floor($daysSinceDeposit / 30) . " months. Deposit ₱{$monthlyBudget} now!";
            }
            
            $notifications[] = [
                'id' => 'regular_deposit_' . $plan['savings_id'],
                'icon' => '💰',
                'message' => $message,
                'time' => 'Just now',
                'timestamp' => $now->format('Y-m-d H:i:s'),
                'type' => 'regular_deposit',
                'priority' => 1, // High priority
                'savings_id' => $plan['savings_id']
            ];
        }
    }
    mysqli_stmt_close($stmt);
}

// ============================================
// 3. PLANS BEHIND SCHEDULE (Low Progress)
// ============================================
// Check plans that are significantly behind their expected progress
$lowProgressQuery = "SELECT savings_id, plan_name, goal_amount, saved_amount, start_date, end_date, monthly_budget
                     FROM savings 
                     WHERE user_id = ? 
                     AND status = 'Active'
                     AND start_date IS NOT NULL
                     AND end_date IS NOT NULL
                     AND saved_amount < goal_amount";

$stmt = mysqli_prepare($conn, $lowProgressQuery);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($plan = mysqli_fetch_assoc($result)) {
        $startDate = new DateTime($plan['start_date']);
        $endDate = new DateTime($plan['end_date']);
        $goalAmount = floatval($plan['goal_amount']);
        $savedAmount = floatval($plan['saved_amount']);
        
        // Calculate expected progress
        $totalDays = $startDate->diff($endDate)->days;
        $daysElapsed = $now->diff($startDate)->days;
        
        if ($totalDays > 0 && $daysElapsed > 0) {
            $expectedProgress = ($daysElapsed / $totalDays) * 100;
            $expectedAmount = ($daysElapsed / $totalDays) * $goalAmount;
            $actualProgress = ($savedAmount / $goalAmount) * 100;
            
            // If behind by more than 20% of goal or 30% of expected progress
            $behindByAmount = $expectedAmount - $savedAmount;
            $behindByPercent = $expectedProgress - $actualProgress;
            
            if ($behindByPercent > 30 || $behindByAmount > ($goalAmount * 0.2)) {
                $behindAmount = number_format($behindByAmount, 2);
                $message = "⚠️ '{$plan['plan_name']}' is behind schedule. You need to save ₱{$behindAmount} more to catch up!";
                
                if ($behindByPercent > 50) {
                    $message = "🚨 '{$plan['plan_name']}' is way behind! You're " . round($behindByPercent) . "% behind schedule. Save ₱{$behindAmount} to catch up!";
                }
                
                $notifications[] = [
                    'id' => 'low_progress_' . $plan['savings_id'],
                    'icon' => '📉',
                    'message' => $message,
                    'time' => 'Just now',
                    'timestamp' => $now->format('Y-m-d H:i:s'),
                    'type' => 'low_progress',
                    'priority' => 2, // High priority
                    'savings_id' => $plan['savings_id']
                ];
            }
        }
    }
    mysqli_stmt_close($stmt);
}

// ============================================
// 4. SUGGEST SAVING AMOUNTS
// ============================================
// Suggest how much to save based on remaining goal and time left
$suggestionQuery = "SELECT savings_id, plan_name, goal_amount, saved_amount, start_date, end_date, monthly_budget
                    FROM savings 
                    WHERE user_id = ? 
                    AND status = 'Active'
                    AND saved_amount < goal_amount
                    AND end_date IS NOT NULL
                    AND end_date > CURDATE()";

$stmt = mysqli_prepare($conn, $suggestionQuery);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($plan = mysqli_fetch_assoc($result)) {
        $endDate = new DateTime($plan['end_date']);
        $goalAmount = floatval($plan['goal_amount']);
        $savedAmount = floatval($plan['saved_amount']);
        $remaining = $goalAmount - $savedAmount;
        
        if ($remaining > 0) {
            $daysRemaining = $now->diff($endDate)->days;
            
            if ($daysRemaining > 0 && $daysRemaining <= 90) {
                // Calculate suggested monthly and weekly amounts
                $monthsRemaining = max(1, ceil($daysRemaining / 30));
                $weeksRemaining = max(1, ceil($daysRemaining / 7));
                
                $suggestedMonthly = $remaining / $monthsRemaining;
                $suggestedWeekly = $remaining / $weeksRemaining;
                
                // Format amounts
                $suggestedMonthlyFormatted = number_format($suggestedMonthly, 2);
                $suggestedWeeklyFormatted = number_format($suggestedWeekly, 2);
                $remainingFormatted = number_format($remaining, 2);
                
                // Choose the most appropriate suggestion
                if ($daysRemaining <= 30) {
                    $message = "💡 To reach your goal for '{$plan['plan_name']}', save ₱{$suggestedWeeklyFormatted} per week (₱{$remainingFormatted} remaining)";
    } else {
                    $message = "💡 To reach your goal for '{$plan['plan_name']}', save ₱{$suggestedMonthlyFormatted} per month (₱{$remainingFormatted} remaining)";
    }
    
    $notifications[] = [
                    'id' => 'suggestion_' . $plan['savings_id'],
                    'icon' => '💡',
                    'message' => $message,
                    'time' => 'Just now',
                    'timestamp' => $now->format('Y-m-d H:i:s'),
                    'type' => 'saving_suggestion',
                    'priority' => 3, // Medium priority
                    'savings_id' => $plan['savings_id']
                ];
            }
        }
    }
    mysqli_stmt_close($stmt);
}

// ============================================
// 5. PLANS EXPIRING SOON (Keep this)
// ============================================
$planExpirationQuery = "SELECT savings_id, plan_name, end_date, status
                        FROM savings 
                        WHERE user_id = ? 
                        AND status = 'Active'
                        AND end_date IS NOT NULL
                        AND end_date >= CURDATE()
                        AND end_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                        ORDER BY end_date ASC";
$planStmt = mysqli_prepare($conn, $planExpirationQuery);

if ($planStmt) {
    mysqli_stmt_bind_param($planStmt, "s", $user_id);
    mysqli_stmt_execute($planStmt);
    $planResult = mysqli_stmt_get_result($planStmt);
    
    while ($plan = mysqli_fetch_assoc($planResult)) {
        $endDate = new DateTime($plan['end_date']);
        $endDate->setTime(0, 0, 0);
        $nowCopy = clone $now;
        $nowCopy->setTime(0, 0, 0);
        
        $interval = $nowCopy->diff($endDate);
        $daysRemaining = (int)$interval->format('%a');
        
        if ($interval->invert) {
            $daysRemaining = -$daysRemaining;
        }
        
        if ($daysRemaining >= 0) {
            $timeMessage = '';
            if ($daysRemaining == 0) {
                $timeMessage = 'expires today';
            } elseif ($daysRemaining == 1) {
                $timeMessage = 'expires tomorrow';
            } else {
                $timeMessage = "expires in {$daysRemaining} days";
            }
            
            $notifications[] = [
                'id' => 'plan_exp_' . $plan['savings_id'],
                'icon' => '⏰',
                'message' => "Plan '{$plan['plan_name']}' {$timeMessage}",
                'time' => 'Just now',
                'timestamp' => $now->format('Y-m-d H:i:s'),
                'type' => 'plan_expiration',
                'priority' => 0, // Highest priority
                'savings_id' => $plan['savings_id']
            ];
        }
    }
    mysqli_stmt_close($planStmt);
}

// ============================================
// 6. PLANS WITH NO RECENT ACTIVITY
// ============================================
// Alert users about plans they haven't touched in a while
$inactiveQuery = "SELECT s.savings_id, s.plan_name, s.saved_amount, s.goal_amount,
                         MAX(COALESCE(t.date, s.created_at)) as last_activity
                  FROM savings s
                  LEFT JOIN transaction_log t ON s.savings_id = t.savings_id
                  WHERE s.user_id = ? 
                  AND s.status = 'Active'
                  AND s.saved_amount < s.goal_amount
                  GROUP BY s.savings_id, s.plan_name, s.saved_amount, s.goal_amount
                  HAVING last_activity < DATE_SUB(CURDATE(), INTERVAL 60 DAY)";

$stmt = mysqli_prepare($conn, $inactiveQuery);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($plan = mysqli_fetch_assoc($result)) {
        $lastActivity = new DateTime($plan['last_activity']);
        $daysInactive = $now->diff($lastActivity)->days;
        $remaining = floatval($plan['goal_amount']) - floatval($plan['saved_amount']);
        $remainingFormatted = number_format($remaining, 2);
        
        $message = "💤 '{$plan['plan_name']}' hasn't been updated in " . floor($daysInactive / 30) . " months. Still need ₱{$remainingFormatted} to reach your goal!";
        
        $notifications[] = [
            'id' => 'inactive_' . $plan['savings_id'],
            'icon' => '💤',
            'message' => $message,
            'time' => 'Just now',
            'timestamp' => $now->format('Y-m-d H:i:s'),
            'type' => 'inactive_plan',
            'priority' => 4, // Lower priority
            'savings_id' => $plan['savings_id']
        ];
    }
    mysqli_stmt_close($stmt);
}

// Sort notifications by priority (lower number = higher priority)
usort($notifications, function($a, $b) {
        return $a['priority'] <=> $b['priority'];
});

// Limit to 10 most important notifications
$notifications = array_slice($notifications, 0, 10);

// Count notifications for badge
$notificationCount = count($notifications);

ob_clean();
echo json_encode([
    'success' => true,
    'notifications' => $notifications,
    'count' => $notificationCount,
    'total' => count($notifications)
]);
ob_end_flush();
?>
