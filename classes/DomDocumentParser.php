<?php

class DomDocumentParser {

	private $doc;

	public function __construct($url) {
		$options = array(
			'http'=>array('method'=>'GET', 'header'=>"User-Agent: chickenBot/0.1\n")
						);
		$context = stream_context_create($options);
		$this->doc = new DomDocument();
		@$this->doc->loadHTML(file_get_contents($url, false, $context));
	}

	public function getLinks() {
		return $this->doc->getElementsByTagName("a"); //returns array of links
	}

	public function getTitleTags() {
		return $this->doc->getElementsByTagName("title"); //returns array of links
	}

	public function getMetaTags() {
		return $this->doc->getElementsByTagName("meta"); //returns array of links
	}

	public function getImages() {
		return $this->doc->getElementsByTagName("img"); //returns array of links
	}

}

?>