<?php

class ScholarProfileParser{

	public $dom;

	public function ScholarProfileParser($filename=""){
		if($filename){
			$this->readFile($filename);
		}
	}

	public function readFile($filename){
		// DOM to store html in. This stores the HTML page as a traversable tree, which can be queried by XPath
		$this->dom = new DOMDocument();
		$html = file_get_contents($filename); 
		@$this->dom->loadHTML($html);			
	}

	public function parsePublications($needs_year=true){
		// XPath object to query DOM with
		$xpath = new DOMXPath($this->dom);
		$publications = $xpath->query('//table[@id="gsc_a_t"]/tbody[@id="gsc_a_b"]/tr[@class="gsc_a_tr"]');
		$parsed_publications = array();

		foreach ($publications as $publication){			
			$fields = $publication->childNodes;
			foreach($fields as $cell){
				switch($cell->getAttribute("class")){
					case "gsc_a_t": // classname used for publication data (title, author, journal)
						$title_info = $this->process_title($cell);
						break;
					case "gsc_a_c": // classname used for citation data (no. of citations)
						$cit = $cell->nodeValue;
						if(is_numeric($cit)){
							$citations = array("citations" => $cell->nodeValue);
						}else{
							$citations = array();
						}						
						break;
					case "gsc_a_y": // classname_used for year of publication
						$year = array("year" => $cell->nodeValue);
						break;
				}								
			}

			$total = array_merge($title_info, $citations, $year); 

			if(!$needs_year || $year["year"]){
				$parsed_publications[] = $total;
			}
		}
		return $parsed_publications;
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
						// Remove year notation at end of journal indication
						$processed_info["journal"] = substr($processed_info["journal"], 0, strrpos($processed_info["journal"], ","));					
					}else{
						$processed_info["authors"] = $node->nodeValue;
					}
					break;
			}			
		}
		return $processed_info;
	}
}

$parser = new ScholarProfileParser("Daniel Schreij - Google Scholar Citations.html");

echo "<pre>";
print_r($parser->parsePublications());
echo "</pre>";

?>