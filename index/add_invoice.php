<?php
// Headers for CORS and JSON response
header("Access-Control-Allow-Headers: Authorization, Content-Type");
header("Access-Control-Allow-Origin: *");
header('content-type: application/json; charset=utf-8');

// Database connection function
function get_pdo() {
    require "dbConfig.php";
    $dsn = "mysql:dbname=" . DB_NAME . ";host=" . DB_HOST;
    $user = DB_USER;
    $pass = DB_PASS;
    return new PDO($dsn, $user, $pass);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = get_pdo();
        
        // Get form data
        $description = $_POST['description'] ?? '';
        $quantity = $_POST['quantity'] ?? 0;
        $unit_price = $_POST['unit_price'] ?? 0;
        $total = $quantity * $unit_price;
        
        // Insert into database
        $stmt = $pdo->prepare("INSERT INTO invoices (description, quantity, unit_price, total_amount) VALUES (?, ?, ?, ?)");
        $stmt->execute([$description, $quantity, $unit_price, $total]);
        
        // Return success response
        echo json_encode(["success" => true, "message" => "Invoice added successfully"]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Invoice</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="text"], input[type="number"] {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }
        button {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <h1>Add New Invoice</h1>
    
    <form id="invoiceForm" method="POST">
        <div class="form-group">
            <label for="description">Item Description:</label>
            <input type="text" id="description" name="description" required>
        </div>
        
        <div class="form-group">
            <label for="quantity">Quantity:</label>
            <input type="number" id="quantity" name="quantity" min="1" required>
        </div>
        
        <div class="form-group">
            <label for="unit_price">Unit Price ($):</label>
            <input type="number" id="unit_price" name="unit_price" min="0" step="0.01" required>
        </div>
        
        <button type="submit">Add Invoice</button>
    </form>
    
    <p><a href="index.php">Back to Invoices</a></p>
    
    <script>
        // Handle form submission with AJAX
        document.getElementById('invoiceForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            fetch('add_invoice.php', {
                method: 'POST',
                body: new FormData(this)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.href = 'index.php';
                } else {
                    alert(data.error || 'Error adding invoice');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
            });
        });
    </script>
</body>
</html>