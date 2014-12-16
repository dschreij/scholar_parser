<?php

class ScholarProfileParser{

	var $dom;						// will hold the HTML DOM
	var $xpath;						// will contain the xpath querier for the DOM
	var $parsed_data = array();		// global var which holds all the data parsed from the DOM

	/**
	* Constructor
	**/

	public function ScholarProfileParser($scholar_user_id="", $sort_by="year"){
		if(!in_array($sort_by, array("year","citations"))){
			throw new InvalidArgumentException("sort_by should be 'year' or 'citations'");
		}

		if($scholar_user_id){
			$this->read_html_from_scholar_profile($scholar_user_id, $sort_by);
		}
	}

	/**
	* Private functions
	**/

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

	private function str_replace_last( $search , $replace , $str ) {
        if( ( $pos = strrpos( $str , $search ) ) !== false ) {
            $search_length  = strlen( $search );
            $str = substr_replace( $str , $replace , $pos , $search_length );
        }
        return $str;
    }

	/**
	* Public functions
	**/
	public function read_html_from_scholar_profile($id, $sort_by="year"){		
		$url = "http://scholar.google.nl/citations?hl=en&user=" . $id . "&view_op=list_works";	
		if($sort_by == "year"){
			$url .= "&sortby=pubdate";
		}

		// DOM to store html in. This stores the HTML page as a traversable tree, which can be queried by XPath
		$this->dom = new DOMDocument();
		$html = file_get_contents($url); 
		@$this->dom->loadHTML($html);
		// XPath object to query DOM with
		$this->xpath = new DOMXPath($this->dom);
		return $html;
	}

	public function read_html_from_file($filename){
		// DOM to store html in. This stores the HTML page as a traversable tree, which can be queried by XPath
		$this->dom = new DOMDocument();
		$html = file_get_contents($filename); 
		@$this->dom->loadHTML($html);
		// XPath object to query DOM with
		$this->xpath = new DOMXPath($this->dom);
		return $html;	
	}

	public function save_to_json($filename){
		$json = json_encode($this->parsed_data);
		file_put_contents($filename, $json);
	}

	public function read_json($filename){
		try{
			$json = file_get_contents($filename);
		}catch(Exception $e){
			echo "<p><b>Error:</b> " . $e->getMessage() . "</p>\n";
			return;
		}
		$this->parsed_data = json_decode($json, true);
	}

	public function parse_publications($needs_year=true){
		
		// Fingers crossed that Google will not keep changing this...
		$publications = $this->xpath->query('//table[@id="gsc_a_t"]/tbody[@id="gsc_a_b"]/tr[@class="gsc_a_tr"]');
		$parsed_publications = array();

		foreach ($publications as $publication){			
			$fields = $publication->childNodes;
			foreach($fields as $cell){
				switch($cell->getAttribute("class")){
					case "gsc_a_t": // classname used for publication data (title, author, journal)
						$title_info = $this->process_title($cell);
						break;
					case "gsc_a_c": // classname used for citation data (no. of citations)
						// Check if the value is a number (and not a funny character which is used if there are no citations)
						if(is_numeric($cell->nodeValue)){
							// First link stored in this table cell
							$link = $cell->childNodes->item(0);

							$cit = array("amount" => $link->nodeValue);
							// Get link to citing articles (if it exists)
							$url = $link->getAttribute("href");
							if($url){
								$cit["url"] = $url;
							}							
							$citations = array("citations" => $cit);
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
		$this->parsed_data["publications"] = $parsed_publications;
		return $parsed_publications;
	}

	public function parse_stats(){
		// Get the table containing citation indices and stuff
		$stats_table = $this->xpath->query('//div[@class="gsc_rsb_s"]/table[@id="gsc_rsb_st"]');
		$temp_dom = new DOMDocument();
		foreach($stats_table as $n) $temp_dom->appendChild($temp_dom->importNode($n,true));
		// Just save the HTML table (not much to change about it)
		$this->parsed_data["stats"] = $temp_dom->saveHTML();
		return $this->parsed_data["stats"];
	}

	public function print_parsed_data_raw(){
		echo "<pre>";
		print_r($this->parsed_data);
		echo "</pre>";
	}

	public function format_publications_in_APA(){
		// First check if publication data is already present, and exit otherwise.
		if(!isset($this->parsed_data["publications"])){
			echo "<p><b>Error:</b>no publication data present. call parse functions first</p>";
			return;
		}
		$res = "<table><tr><th></th><th>Citations</th></tr>";

		$pubs = $this->parsed_data["publications"];
		foreach($pubs as $pub){
			$pub_authors = $pub["authors"];
			// Replace last occurence of a , with a &
			$pub_authors = $this->str_replace_last(",", " &amp;", $pub_authors);
			$pub_authors = str_replace("Ã´","&ocirc;",$pub_authors);

			// Make the title a link that corresponds to the article on Google Scholar
			$formatted_title = "<a href='" . $pub["url"] . "'>" . $pub["title"] . "</a>";

			$apa_str = $pub_authors . " (" . $pub["year"] . ") " . $formatted_title . ". " . $pub["journal"];
			if(isset($pub["citations"])){
				$citation_count = "<a href='" . $pub["citations"]["url"] . "'>" . $pub["citations"]["amount"] . "</a>";
			}else{
				$citation_count = "";
			}
			$res .= "<tr><td style='width:95%;padding-right:40px'>" . $apa_str . "</td><td style='text-align:center'>" . $citation_count . "</td></tr>";
		}
		
		$res .= "</table>";
		return $res;
	}
}


$parser = new ScholarProfileParser();
//$parser->read_html_from_file("Daniel Schreij - Google Scholar Citations.html");
$profile_id = "Pm3O_58AAAAJ&hl";
if(file_exists("cache/" . $profile_id . ".json")){
	echo "<p>Reading from local cache</p>\n";
	$parser->read_json("cache/" . $profile_id . ".json");
}else{
	echo "<p>Reading from online profile page</p>\n";
	$parser->read_html_from_scholar_profile($profile_id);
	$parser->parse_publications();
	$parser->parse_stats();	
	$parser->save_to_json("cache/" . $profile_id . ".json");
}	

$parser->print_parsed_data_raw();
echo $parser->format_publications_in_APA();

?>