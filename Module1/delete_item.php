<?php
// db.php
// Adjust these values to match your local environment
$host = 'localhost';
$db= 'coffee';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
}
?>
<script>
alert("Deleted successfully!");
window.location.href = 'index.php';
</script>