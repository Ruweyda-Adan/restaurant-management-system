<?php
$conn = new mysqli("localhost", "root", "", "restaurant_db");

$id = $_POST["id"];
$status = $_POST["status"];

$conn->query("UPDATE orders SET status='$status' WHERE id=$id");
?>
