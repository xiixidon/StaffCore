<?php
require_once 'db.php';
$stmt = $pdo->query("SELECT * FROM users");
$users = $stmt->fetchAll();
foreach($users as $u) {
    echo $u['email'] . ' | ' . $u['password'] . ' | ' . $u['role'] . '<br>';
}
?>