<?php
/**
 * Database Connection (PDO)
 */
require_once __DIR__ . '/config.php';

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

/**
 * Get financial summary totals
 */
function getFinancialSummary(): array {
    $db = getDB();

    $income = $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM sf_income WHERE deleted_at IS NULL")->fetch();
    $expenses = $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM sf_expenses WHERE deleted_at IS NULL")->fetch();

    $totalIncome  = (float)$income['total'];
    $totalExpenses = (float)$expenses['total'];

    return [
        'total_income'   => $totalIncome,
        'total_expenses' => $totalExpenses,
        'balance'        => $totalIncome - $totalExpenses,
    ];
}

/**
 * Get per-event financial breakdown
 */
function getEventSummaries(): array {
    $db = getDB();
    $sql = "
        SELECT
            e.id,
            e.name,
            e.status,
            e.color,
            COALESCE(i.total_income, 0) AS total_income,
            COALESCE(x.total_expenses, 0) AS total_expenses,
            COALESCE(i.total_income, 0) - COALESCE(x.total_expenses, 0) AS balance
        FROM sf_events e
        LEFT JOIN (
            SELECT event_id, SUM(amount) AS total_income
            FROM sf_income WHERE deleted_at IS NULL GROUP BY event_id
        ) i ON i.event_id = e.id
        LEFT JOIN (
            SELECT event_id, SUM(amount) AS total_expenses
            FROM sf_expenses WHERE deleted_at IS NULL GROUP BY event_id
        ) x ON x.event_id = e.id
        WHERE e.deleted_at IS NULL AND e.is_archived = 0
        ORDER BY e.created_at DESC
    ";
    return $db->query($sql)->fetchAll();
}

/**
 * Get recent transactions (income + expenses) merged
 */
function getRecentTransactions(int $limit = 10): array {
    $db = getDB();
    $sql = "
        (SELECT 'income' AS type, i.id, e.name AS event_name, e.color,
                i.description, i.amount, i.transaction_date, i.created_at
         FROM sf_income i JOIN sf_events e ON e.id = i.event_id
         WHERE i.deleted_at IS NULL ORDER BY i.transaction_date DESC LIMIT :lim)
        UNION ALL
        (SELECT 'expense' AS type, x.id, e.name AS event_name, e.color,
                x.description, x.amount, x.transaction_date, x.created_at
         FROM sf_expenses x JOIN sf_events e ON e.id = x.event_id
         WHERE x.deleted_at IS NULL ORDER BY x.transaction_date DESC LIMIT :lim)
        ORDER BY transaction_date DESC LIMIT :lim
    ";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Log edit history for income
 */
function logIncomeHistory(int $incomeId, string $field, $oldVal, $newVal, string $changedBy = 'system'): void {
    $db = getDB();
    $db->prepare("INSERT INTO sf_income_history (income_id, changed_by, field_name, old_value, new_value) VALUES (?,?,?,?,?)")
       ->execute([$incomeId, $changedBy, $field, $oldVal, $newVal]);
}

/**
 * Log edit history for expenses
 */
function logExpenseHistory(int $expenseId, string $field, $oldVal, $newVal, string $changedBy = 'system'): void {
    $db = getDB();
    $db->prepare("INSERT INTO sf_expense_history (expense_id, changed_by, field_name, old_value, new_value) VALUES (?,?,?,?,?)")
       ->execute([$expenseId, $changedBy, $field, $oldVal, $newVal]);
}
