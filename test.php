<<<<<<< HEAD
<?php
require_once 'db.php';
$stmt = $pdo->query("SELECT * FROM users");
$users = $stmt->fetchAll();
foreach($users as $u) {
    echo $u['email'] . ' | ' . $u['password'] . ' | ' . $u['role'] . '<br>';
}
=======
<?php
require_once 'db.php';
$stmt = $pdo->query("SELECT * FROM users");
$users = $stmt->fetchAll();
foreach($users as $u) {
    echo $u['email'] . ' | ' . $u['password'] . ' | ' . $u['role'] . '<br>';
}
>>>>>>> 98e1e05841e4235727ebf4e70697bce65fb3fe24
?>