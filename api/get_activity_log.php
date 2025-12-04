<?php
/**
 * get_activity_log.php - Fetch activity logs
 * 
 * GET Parameters:
 *   - user_id (required): User ID to fetch logs for
 *   - related_table (optional): Filter by related table (e.g., 'savings', 'expenses')
 *   - related_record_id (optional): Filter by specific record ID
 *   - action_type (optional): Filter by action type (e.g., 'login', 'deposit')
 *   - limit (optional): Number of records to return (default: 100, max: 1000)
 *   - offset (optional): Offset for pagination (default: 0)
 *   - start_date (optional): Start date filter (YYYY-MM-DD)
 *   - end_date (optional): End date filter (YYYY-MM-DD)
 *   - include_user_info (optional): Include user details in response (true/false, default: false)
 * 
 * Example:
 *   GET /get_activity_log.php?user_id=user001&limit=50
 *   GET /get_activity_log.php?user_id=user001&related_table=savings&related_record_id=sav001
 */

require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

// Get parameters
$user_id = isset($_GET['user_id']) ? trim($_GET['user_id']) : '';
$related_table = isset($_GET['related_table']) ? trim($_GET['related_table']) : null;
$related_record_id = isset($_GET['related_record_id']) ? trim($_GET['related_record_id']) : null;
$action_type = isset($_GET['action_type']) ? trim($_GET['action_type']) : null;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : null;
$end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : null;
$include_user_info = isset($_GET['include_user_info']) && $_GET['include_user_info'] === 'true';

// Validate user_id
if (empty($user_id)) {
    echo json_encode(['error' => 'user_id is required']);
    exit;
}

// Validate and limit limit
$limit = max(1, min(1000, $limit));
$offset = max(0, $offset);

// Build query
$conditions = ["al.user_id = ?"];
$params = [$user_id];
$param_types = "s";

// Add filters
if (!empty($related_table)) {
    $conditions[] = "al.related_table = ?";
    $params[] = $related_table;
    $param_types .= "s";
}

if (!empty($related_record_id)) {
    $conditions[] = "al.related_record_id = ?";
    $params[] = $related_record_id;
    $param_types .= "s";
}

if (!empty($action_type)) {
    $conditions[] = "al.action_type = ?";
    $params[] = $action_type;
    $param_types .= "s";
}

if (!empty($start_date)) {
    $conditions[] = "DATE(al.created_at) >= ?";
    $params[] = $start_date;
    $param_types .= "s";
}

if (!empty($end_date)) {
    $conditions[] = "DATE(al.created_at) <= ?";
    $params[] = $end_date;
    $param_types .= "s";
}

$where_clause = implode(' AND ', $conditions);

// Build SELECT clause
if ($include_user_info) {
    $select_clause = "al.*, u.first_name, u.last_name, u.email";
    $from_clause = "activity_log al LEFT JOIN users u ON al.user_id = u.user_id";
} else {
    $select_clause = "al.*";
    $from_clause = "activity_log al";
}

$query = "SELECT {$select_clause} 
          FROM {$from_clause}
          WHERE {$where_clause}
          ORDER BY al.created_at DESC
          LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$param_types .= "ii";

// Execute query
$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    echo json_encode(['error' => 'Database query preparation failed: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($stmt, $param_types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$activities = [];

while ($row = mysqli_fetch_assoc($result)) {
    $activity = [
        'activity_id' => $row['activity_id'],
        'user_id' => $row['user_id'],
        'related_table' => $row['related_table'],
        'related_record_id' => $row['related_record_id'],
        'action_type' => $row['action_type'],
        'action_description' => $row['action_description'],
        'ip_address' => $row['ip_address'],
        'user_agent' => $row['user_agent'],
        'created_at' => $row['created_at']
    ];
    
    // Add user info if requested
    if ($include_user_info) {
        $activity['user'] = [
            'first_name' => $row['first_name'] ?? null,
            'last_name' => $row['last_name'] ?? null,
            'email' => $row['email'] ?? null
        ];
    }
    
    $activities[] = $activity;
}

mysqli_stmt_close($stmt);

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM activity_log al WHERE {$where_clause}";
$count_stmt = mysqli_prepare($conn, $count_query);

if ($count_stmt) {
    // Remove limit and offset from params for count
    $count_params = array_slice($params, 0, -2);
    $count_types = str_replace('ii', '', $param_types);
    
    if (!empty($count_params)) {
        mysqli_stmt_bind_param($count_stmt, $count_types, ...$count_params);
    }
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $count_row = mysqli_fetch_assoc($count_result);
    $total = intval($count_row['total']);
    mysqli_stmt_close($count_stmt);
} else {
    $total = count($activities);
}

echo json_encode([
    'success' => true,
    'total' => $total,
    'limit' => $limit,
    'offset' => $offset,
    'activities' => $activities
]);

?>

