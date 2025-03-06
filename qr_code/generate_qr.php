<?php
include('../includes/db_connect.php'); 
include __DIR__ . "/phpqrcode/phpqrcode.php"; 

//The URL that the QR code will open to

$url = "http://$ip_address/restaurant-management-system/pages/menu.php"; 
QRcode::png($url);

?>
