<?php
include("../config.php");

if(isset($_POST["imageUrl"])) {
	$query = $connect->prepare("UPDATE images SET clicks = clicks + 1
								WHERE imageUrl = :imageUrl");
	$query->bindParam(":imageUrl", $_POST["imageUrl"]);
	$query->execute();
}
else {
	echo "No image url passed to page";
}
?>