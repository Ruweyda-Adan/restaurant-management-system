<?php
include('../includes/db_connect.php'); 
include __DIR__ . "/phpqrcode/phpqrcode.php"; 

//The URL that the QR code will open to
$ip_address = '172.20.10.2'; 
$url = "http://$ip_address/restaurant-management-system/pages/menu.php"; 
QRcode::png($url);

?>
