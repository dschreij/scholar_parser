<?php

class ScholarProfileParser{

	var $dom;

	public function ScholarProfileParser($filename=""){
		if($filename){
			$this->readFile($filename);
		}
	}

	public function readFile($filename){
		// DOM to store html in. This stores the HTML page as a traversable tree, which can be queried by XPath
		$this->dom = new DOMDocument();
		$this->dom->loadHTML($filename);			
	}

	public function parsePublications(){
		// XPath object to query DOM with
		$xpath = new DOMXPath($this->dom);
		$publications = $xpath->query('//table[@id="gsc_a_t"]'); //tbody[@id="gsc_a_b"]/tr[@class="gsc_a_tr"]');

		foreach ($publications as $publication){
			echo $publication->nodeValue;
			$fields = $publication->childNodes;
			foreach($fields as $cell){
				switch($cell->getAttribute("class")){
					case "gsc_a_t": // classname used for publication data (title, author, journal)
						$title_info = process_title($cell);
						break;
					case "gsc_a_c": // classname used for citation data (no. of citations)
						$citation_info = process_citations($cell);
						break;
					case "gsc_a_y": // classname_used for year of publication
						$year_of_pub = process_year($cell);
						break;
				}								
			}
			echo "<pre>";
			print_r($title_info);
			echo "</pre>";
		}
	}

	private function process_title($cell){
		$raw_info = $cell->childNodes;
		$processed_info = array(); 

		foreach($raw_info as $node){
			switch($node->getAttribute("class")){
				case "gsc_a_at":	// The title is specified as a link
					$processed_info["url"] = $node->getAttribute("href"); //Get the link
					$processed_info["title"] = $node->nodeValue;
					break;
				case "gs_gray":
					if(isset($processed_info["authors"])){
						$processed_info["journal"] = $node->nodeValue;						
					}else{
						$processed_info["authors"] = $node->nodeValue;
					}
					break;
			}			
		}
		return $processed_info;
	}

	private function process_citations($cell){

	}

	private function process_year($cell){

	}		
}

$parser = new ScholarProfileParser("Daniel Schreij - Google Scholar Citations.html");
$parser->parsePublications();


?>