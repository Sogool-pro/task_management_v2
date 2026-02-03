<?php
include "DB_connection.php";
$q = $pdo->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'subtasks'");
$result = $q->fetchAll(PDO::FETCH_ASSOC);
print_r($result);
?>
