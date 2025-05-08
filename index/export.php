<?php
require 'dbConfig.php';
$pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="invoices_'.date('Y-m-d').'.csv"');

$output = fopen('php://output', 'w');

// Build query based on filters
$where = [];
$params = [];

if (!empty($_GET['search'])) {
    $where[] = "description LIKE ?";
    $params[] = "%".$_GET['search']."%";
}

// Add other filters similar to main page...

$query = "SELECT * FROM invoices";
if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);

// Write headers
fputcsv($output, ['ID', 'Description', 'Quantity', 'Unit Price', 'Total', 'Created']);

// Write data
while ($row = $stmt->fetch()) {
    fputcsv($output, [
        $row['invoice_id'],
        $row['description'],
        $row['quantity'],
        $row['unit_price'],
        $row['total_amount'],
        $row['created_at']
    ]);
}

fclose($output);
?>