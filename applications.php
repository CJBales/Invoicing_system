<?php
// Database connection
try {
    $host = 'localhost';
    $dbname = 'invoicing_system';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("<div class='error'>Database connection failed: " . htmlspecialchars($e->getMessage()) . "</div>");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Viewer</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        .search-form {
            margin-bottom: 20px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
            position: sticky;
            top: 0;
        }
        .sortable:hover {
            background-color: #e9e9e9;
            cursor: pointer;
        }
        .sort-arrow {
            font-size: 0.8em;
            margin-left: 5px;
        }
        .filters {
            background: #f5f5f5;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .filters label {
            margin-right: 15px;
        }
        
        .stats-summary {
            background: #e8f5e9;
            padding: 10px;
            margin: 15px 0;
            border-radius: 5px;
            display: flex;
            gap: 20px;
        }
        .error {
            color: red;
            padding: 10px;
            background-color: #ffebee;
            margin: 10px 0;
        }
        .pagination {
            margin: 20px 0;
            display: flex;
            gap: 5px;
        }
        .pagination a {
            padding: 5px 10px;
            border: 1px solid #ddd;
        }
        .pagination a.active {
            background: #4CAF50;
            color: white;
        }
        @media (max-width: 768px) {
            table {
                display: block;
                overflow-x: auto;
            }
            .filters > div {
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <h1>Invoice Viewer</h1>
    <p>Search and view invoices below.</p>

    <!-- Search Form -->
    <form class="search-form" method="GET" action="">
        <div>
            <input type="text" name="search" placeholder="Search by description..." 
                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            <button type="submit">Search</button>
            <button type="button" onclick="location.href='?'">Clear</button>
        </div>
        
        <div class="filters">
            <h3>Filters</h3>
            <div>
                <label>Date From:
                    <input type="date" name="date_from" value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
                </label>
                <label>Date To:
                    <input type="date" name="date_to" value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
                </label>
            </div>
            <div>
                <label>Amount From:
                    $<input type="number" name="min_amount" step="0.01" min="0" 
                           value="<?= htmlspecialchars($_GET['min_amount'] ?? '') ?>">
                </label>
                <label>Amount To:
                    $<input type="number" name="max_amount" step="0.01" min="0" 
                           value="<?= htmlspecialchars($_GET['max_amount'] ?? '') ?>">
                </label>
            </div>
        </div>
    </form>

    <?php
    try {
        // Handle search and filters
        $search = $_GET['search'] ?? '';
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';
        $minAmount = $_GET['min_amount'] ?? '';
        $maxAmount = $_GET['max_amount'] ?? '';
        
        // Sorting
        $sort = $_GET['sort'] ?? 'created_at';
        $order = $_GET['order'] ?? 'DESC';
        $validSorts = ['invoice_id', 'description', 'quantity', 'unit_price', 'total_amount', 'created_at'];
        $sort = in_array($sort, $validSorts) ? $sort : 'created_at';
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        
        // Build query
        $where = [];
        $params = [];
        
        if (!empty($search)) {
            $where[] = "description LIKE :search";
            $params['search'] = "%$search%";
        }
        
        if (!empty($dateFrom)) {
            $where[] = "created_at >= :date_from";
            $params['date_from'] = $dateFrom;
        }
        
        if (!empty($dateTo)) {
            $where[] = "created_at <= :date_to";
            $params['date_to'] = $dateTo . ' 23:59:59';
        }
        
        if (!empty($minAmount)) {
            $where[] = "total_amount >= :min_amount";
            $params['min_amount'] = $minAmount;
        }
        
        if (!empty($maxAmount)) {
            $where[] = "total_amount <= :max_amount";
            $params['max_amount'] = $maxAmount;
        }
        
        $query = "SELECT * FROM invoices";
        if (!empty($where)) {
            $query .= " WHERE " . implode(" AND ", $where);
        }
        $query .= " ORDER BY $sort $order";
        
        // Get stats before pagination
        $statsQuery = "SELECT COUNT(*) as count, SUM(total_amount) as sum, AVG(total_amount) as avg " . 
                     substr($query, strpos($query, 'FROM'));
        $statsStmt = $pdo->prepare($statsQuery);
        $statsStmt->execute($params);
        $stats = $statsStmt->fetch();
        
        // Pagination
        $perPage = 10;
        $page = max(1, intval($_GET['page'] ?? 1));
        $offset = ($page - 1) * $perPage;
        
        $query .= " LIMIT $offset, $perPage";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $invoices = $stmt->fetchAll();
        
        // Display stats
        echo "<div class='stats-summary'>
                <span><strong>" . $stats['count'] . "</strong> invoices</span>
                <span><strong>$" . number_format($stats['sum'], 2) . "</strong> total</span>
                <span>Avg: <strong>$" . number_format($stats['avg'], 2) . "</strong></span>
              </div>";
        
        if ($invoices) {
            echo "<table>
                    <thead>
                        <tr>
                            <th class='sortable' onclick=\"sortTable('invoice_id')\"># 
                                " . ($sort === 'invoice_id' ? "<span class='sort-arrow'>" . ($order === 'ASC' ? '↑' : '↓') . "</span>" : "") . "
                            </th>
                            <th class='sortable' onclick=\"sortTable('description')\">Item Description
                                " . ($sort === 'description' ? "<span class='sort-arrow'>" . ($order === 'ASC' ? '↑' : '↓') . "</span>" : "") . "
                            </th>
                            <th class='sortable' onclick=\"sortTable('quantity')\">Quantity
                                " . ($sort === 'quantity' ? "<span class='sort-arrow'>" . ($order === 'ASC' ? '↑' : '↓') . "</span>" : "") . "
                            </th>
                            <th class='sortable' onclick=\"sortTable('unit_price')\">Unit Price
                                " . ($sort === 'unit_price' ? "<span class='sort-arrow'>" . ($order === 'ASC' ? '↑' : '↓') . "</span>" : "") . "
                            </th>
                            <th class='sortable' onclick=\"sortTable('total_amount')\">Total Amount
                                " . ($sort === 'total_amount' ? "<span class='sort-arrow'>" . ($order === 'ASC' ? '↑' : '↓') . "</span>" : "") . "
                            </th>
                            <th class='sortable' onclick=\"sortTable('created_at')\">Date
                                " . ($sort === 'created_at' ? "<span class='sort-arrow'>" . ($order === 'ASC' ? '↑' : '↓') . "</span>" : "") . "
                            </th>
                        </tr>
                    </thead>
                    <tbody>";
            
            foreach ($invoices as $invoice) {
                echo "<tr>
                        <td>{$invoice['invoice_id']}</td>
                        <td>" . htmlspecialchars($invoice['description']) . "</td>
                        <td>{$invoice['quantity']}</td>
                        <td>$" . number_format($invoice['unit_price'], 2) . "</td>
                        <td>$" . number_format($invoice['total_amount'], 2) . "</td>
                        <td>{$invoice['created_at']}</td>
                      </tr>";
            }
            
            echo "</tbody></table>";
            
            // Pagination
            $totalPages = ceil($stats['count'] / $perPage);
            if ($totalPages > 1) {
                echo "<div class='pagination'>";
                for ($i = 1; $i <= $totalPages; $i++) {
                    $active = $i == $page ? 'active' : '';
                    $queryParams = $_GET;
                    $queryParams['page'] = $i;
                    echo "<a class='$active' href='?" . http_build_query($queryParams) . "'>$i</a> ";
                }
                echo "</div>";
            }
        } else {
            echo "<p>No invoices found" . (!empty($_GET) ? " matching your criteria" : "") . ".</p>";
        }
    } catch (PDOException $e) {
        echo "<div class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    ?>

    <script>
    // Sorting function
    function sortTable(column) {
        const url = new URL(window.location.href);
        if (url.searchParams.get('sort') === column) {
            url.searchParams.set('order', url.searchParams.get('order') === 'ASC' ? 'DESC' : 'ASC');
        } else {
            url.searchParams.set('sort', column);
            url.searchParams.set('order', 'ASC');
        }
        window.location.href = url.toString();
    }
    </script>
</body>
</html>