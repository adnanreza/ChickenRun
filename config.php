<?php
ob_start();

try {
	$connect = new PDO("mysql:dbname=chickenrun;host=localhost", "root", "");
	$connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
}
catch(PDOException $e) {
	echo "Connection Failed" . $e->getMessage();
}
?>
