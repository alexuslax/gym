<?php
require 'config/database.php';

echo "Membership Plans table:\n";
try {
    $stmt = $pdo->query('SELECT * FROM membership_plans');
    while($row = $stmt->fetch()) {
        echo 'ID: ' . $row['plan_id'] . ' | Name: ' . $row['plan_name'] . ' | Price: ' . $row['price'] . "\n";
    }
} catch(Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
