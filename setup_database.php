                                                                                                                                                                                <?php
/**
 * UEP Fitness Gym Management System - Database Setup Script
 * This script will create all necessary database tables and initial data
 */

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'gym_management';

try {
    // Connect to MySQL server (without database)
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>UEP Fitness Gym - Database Setup</h2>";
    echo "<p>Setting up database and tables...</p>";
    
    // Read the SQL file
    $sql = file_get_contents('database_setup.sql');
    
    if ($sql === false) {
        throw new Exception("Could not read database_setup.sql file");
    }
    
    // Split the SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            try {
                $pdo->exec($statement);
                $success_count++;
                
                // Show progress for major operations
                if (preg_match('/CREATE TABLE/i', $statement)) {
                    preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches);
                    if (isset($matches[1])) {
                        echo "<p style='color: green;'>✓ Created table: " . $matches[1] . "</p>";
                    }
                } elseif (preg_match('/CREATE.*?VIEW/i', $statement)) {
                    preg_match('/CREATE.*?VIEW.*?`?(\w+)`?/i', $statement, $matches);
                    if (isset($matches[1])) {
                        echo "<p style='color: blue;'>✓ Created view: " . $matches[1] . "</p>";
                    }
                } elseif (preg_match('/CREATE.*?PROCEDURE/i', $statement)) {
                    preg_match('/CREATE.*?PROCEDURE.*?`?(\w+)`?/i', $statement, $matches);
                    if (isset($matches[1])) {
                        echo "<p style='color: purple;'>✓ Created procedure: " . $matches[1] . "</p>";
                    }
                } elseif (preg_match('/CREATE.*?TRIGGER/i', $statement)) {
                    preg_match('/CREATE.*?TRIGGER.*?`?(\w+)`?/i', $statement, $matches);
                    if (isset($matches[1])) {
                        echo "<p style='color: orange;'>✓ Created trigger: " . $matches[1] . "</p>";
                    }
                } elseif (preg_match('/INSERT INTO/i', $statement)) {
                    preg_match('/INSERT INTO.*?`?(\w+)`?/i', $statement, $matches);
                    if (isset($matches[1])) {
                        echo "<p style='color: teal;'>✓ Inserted data into: " . $matches[1] . "</p>";
                    }
                }
                
            } catch (PDOException $e) {
                $error_count++;
                echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
                echo "<p style='color: gray; font-size: 12px;'>Statement: " . substr($statement, 0, 100) . "...</p>";
            }
        }
    }
    
    echo "<hr>";
    echo "<h3>Setup Summary:</h3>";
    echo "<p><strong>Successful operations:</strong> $success_count</p>";
    echo "<p><strong>Errors:</strong> $error_count</p>";
    
    if ($error_count == 0) {
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3 style='margin-top: 0;'>🎉 Database Setup Completed Successfully!</h3>";
        echo "<p>Your gym management system database is now ready. You can:</p>";
        echo "<ul>";
        echo "<li>Start using the members.php system</li>";
        echo "<li>Create the remaining PHP modules (attendance, billing, equipment, etc.)</li>";
        echo "<li>Set up the authentication system</li>";
        echo "</ul>";
        echo "</div>";
        
        // Test database connection
        echo "<h3>Testing Database Connection:</h3>";
        $test_pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
        $test_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Show table count
        $stmt = $test_pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>✓ Database connection successful</p>";
        echo "<p>✓ Found " . count($tables) . " tables in database</p>";
        
        // Show member count
        $stmt = $test_pdo->query("SELECT COUNT(*) FROM members");
        $member_count = $stmt->fetchColumn();
        echo "<p>✓ Members table ready (currently has $member_count members)</p>";
        
        // Show default admin user
        $stmt = $test_pdo->query("SELECT username, full_name, role FROM users WHERE role = 'admin'");
        $admin = $stmt->fetch();
        if ($admin) {
            echo "<p>✓ Default admin user created: <strong>" . $admin['username'] . "</strong> (" . $admin['full_name'] . ")</p>";
            echo "<p style='color: red; font-weight: bold;'>⚠️ IMPORTANT: Change the default admin password before going live!</p>";
        }
        
    } else {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3 style='margin-top: 0;'>⚠️ Setup Completed with Errors</h3>";
        echo "<p>Some operations failed. Please check the errors above and run the setup again if needed.</p>";
        echo "</div>";
    }
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>Database Connection Error</h3>";
    echo "<p>Could not connect to MySQL server: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration in the script.</p>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>Setup Error</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Test your members.php system to ensure it works with the new database</li>";
echo "<li>Create the authentication system (login.php, logout.php)</li>";
echo "<li>Implement the remaining modules (attendance, billing, equipment, etc.)</li>";
echo "<li>Set up proper user permissions and security</li>";
echo "</ol>";

echo "<p style='margin-top: 30px; font-size: 12px; color: #666;'>";
echo "Database setup completed at: " . date('Y-m-d H:i:s');
echo "</p>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f8f9fa;
}
h2, h3 {
    color: #333;
}
hr {
    border: none;
    border-top: 2px solid #dee2e6;
    margin: 20px 0;
}
</style>
