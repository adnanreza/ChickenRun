<?php
include("config.php");
include("classes/DomDocumentParser.php");

$alreadyCrawled = array();
$crawling = array();
$alreadyFoundImages = array();

function insertLink($url, $title, $description, $keywords) {
	global $connect;

	$query = $connect->prepare("INSERT INTO sites(url, title, description, keywords)
							VALUES(:url, :title, :description, :keywords)");
	$query->bindParam(":url", $url);
	$query->bindParam(":title", $title);
	$query->bindParam(":description", $description);
	$query->bindParam(":keywords", $keywords);

	return $query->execute();
}

function insertImage($url, $src, $alt, $title) {
	global $connect;

	$query = $connect->prepare("INSERT INTO images(siteUrl, imageUrl, alt, title)
							VALUES(:siteUrl, :imageUrl, :alt, :title)");
	$query->bindParam(":siteUrl", $url);
	$query->bindParam(":imageUrl", $src);
	$query->bindParam(":alt", $alt);
	$query->bindParam(":title", $title);

	return $query->execute();
}

function linkExists($url) {
	global $connect;

	$query = $connect->prepare("SELECT * FROM sites WHERE url = :url");
	$query->bindParam(":url", $url);
	$query->execute();

	return $query->rowCount() != 0;
}

function createLink($src, $url) {
	
	$scheme = parse_url($url)["scheme"]; //http
	$host = parse_url($url)["host"]; //www.adnanreza.com

	if(substr($src, 0, 2) == "//") {
		$src = $scheme . ":" . $src;
	}
	else if(substr($src, 0, 1) == "/") {
		$src = $scheme . "://" . $host . $src;
	}
	else if(substr($src, 0, 2) == "./") {
		$src = $scheme . "://" . $host . dirnam(parse_url($url)["path"]) . substr($src, 1);
	}
	else if(substr($src, 0, 3) == "../") {
		$src = $scheme . "://" . $host . "/" . $src;
	}
	else if(substr($src, 0, 5) != "https" && substr($src, 0, 4) != "http") {
		$src = $scheme . "://" . $host . "/" . $src;
	}

	return $src;
}

function getDetails($url) {

	global $alreadyFoundImages;

	$parser = new DomDocumentParser($url);

	$titleArray = $parser->getTitleTags(); 
	//use array to cover the case where a page has more than one title tag

	if(sizeof($titleArray) == 0 || $titleArray->item(0) == NULL ) {
		return;
	}

	$title = $titleArray->item(0)->nodeValue;
	$title = str_replace("\n", "", $title);

	if($title == "") {
		return;
	}

	$description = "";
	$keywords = "";

	$metasArray = $parser->getMetaTags();

	foreach ($metasArray as $meta) {
		if($meta->getAttribute("name") == "description") {
			$description = $meta->getAttribute("content");
		}
		if($meta->getAttribute("name") == "keywords") {
			$keywords = $meta->getAttribute("content");
		}
	}
	$description = str_replace("\n", "", $description);
	$keywords = str_replace("\n", "", $keywords);

	if(linkExists($url)) {
		echo "url already exists<br>";
	}
	else if(insertLink($url, $title, $description, $keywords)) {
		echo "Successfully inserted: $url<br>";
	}
	else {
		echo "Error: Failed to insert $url<br>";
	}

	$imageArray = $parser->getImages();
	foreach ($imageArray as $image) {
		$src = $image->getAttribute("src");
		$alt = $image->getAttribute("alt");
		$title = $image->getAttribute("title");
		if(!$title && !$alt) {
			continue;
		}

		$src = createLink($src, $url); //converts relative link to absolute link
		if(!in_array($src, $alreadyFoundImages)) {
			$alreadyFoundImages[] = $src;
			echo "INSERT: " . insertImage($url, $src, $alt, $title);
		}
	}
	
}


function followLinks($url) {

	global $alreadyCrawled;
	global $crawling;

	$parser = new DomDocumentParser($url);

	$linkList = $parser->getLinks();

	foreach ($linkList as $link) {
		$href = $link->getAttribute("href");
		
		if(strpos($href, '#') !== false) {
			continue; // skip the #s
		}
		else if(substr($href, 0, 11) == "javascript:") {
			continue;
		}

		$href = createLink($href, $url);

		if(!in_array($href, $alreadyCrawled)) {
			$alreadyCrawled[] = $href; //put in next avail slot
			$crawling[] = $href;

			//insert $href
			getDetails($href);
		}

	}
	array_shift($crawling); //remove from array

	foreach ($crawling as $site) {
		followLinks($site);
	}
}

$startUrl = "https://www.adnanreza.com";
followLinks($startUrl);

?>