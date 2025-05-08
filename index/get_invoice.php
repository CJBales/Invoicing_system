<?php
require 'dbConfig.php';
$pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);

$invoice_id = $_GET['invoice_id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM invoices WHERE invoice_id = ?");
$stmt->execute([$invoice_id]);
$invoice = $stmt->fetch();

echo "<h3>Invoice #".htmlspecialchars($invoice['invoice_id'])."</h3>";
echo "<p><strong>Description:</strong> ".htmlspecialchars($invoice['description'])."</p>";
echo "<p><strong>Quantity:</strong> ".htmlspecialchars($invoice['quantity'])."</p>";
echo "<p><strong>Unit Price:</strong> $".number_format($invoice['unit_price'], 2)."</p>";
echo "<p><strong>Total Amount:</strong> $".number_format($invoice['total_amount'], 2)."</p>";
echo "<p><strong>Created:</strong> ".htmlspecialchars($invoice['created_at'])."</p>";
?>