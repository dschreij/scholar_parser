<?php
/*!
 * \brief 		Parser of Google Scholar user profile pages
 * \details		This class parses publications and stats from Google Scholar user pages. The retrieved data can be exported to json.
 * \author 		Daniel Schreij
 * \version 	1.0
 * \date 		2015
 * \copyright 	GNU Public License
 */

// Load composer namespaces
require_once __DIR__.'/vendor/autoload.php';

// PHP PhantomJS to parse Googles JS response
use JonnyW\PhantomJs\Client;

class ScholarProfileParser{
/**
 * holds the HTML DOM object
 * @var DOMDocument
 */
	private $dom;

/**
 * The XPath querier for the DOM
 * @var DOMXPath
 */
	private $xpath;

/**
 * Internal structure containing the data returned by Google Scholar
 * @var array
 */
	private $parsed_data = array();		// global var which holds all the data parsed from the DOM

/**
 * Constructor
 * @param string $scholar_user_id The ID string with which to identify the Scholar User Profile (e.g. Pm3O_58AAAAJ)
 * @param string $sort_by         The variable to order the publications by (default='year'; if 'false' they are orderd by citation count (Descending))
 */
	public function ScholarProfileParser($scholar_user_id="", $sort_by="year"){
		if(!in_array($sort_by, array("year","citations"))){
			throw new InvalidArgumentException("sort_by should be 'year' or 'citations'");
		}
		// Create an instance for communicating with PhantomJS
		$this->phantomJsClient = Client::getInstance();
		$this->phantomJsClient->addOption('--config=phantomconfig.json');

		// If scholar user_id has been passed, load the profile from Scholar
		if($scholar_user_id){
			$this->read_html_from_scholar_profile($scholar_user_id, $sort_by);
		}
	}

/**
 * Parses the data of a single publication entry
 * @param  DOMNode $cell The DOMNode instance describing the publication
 * @return array       The data parsed from the DOM node
 */
	private function process_title($cell){
		$raw_info = $cell->childNodes;
		$processed_info = array(); 

		foreach($raw_info as $node){
			switch($node->getAttribute("class")){
				case "gsc_a_at":	// The title is specified as a link
					$processed_info["url"] = "http://scholar.google.com/" . $node->getAttribute("href"); //Get the link
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

/**
 * Replaces the last occurence of the specified text in a string. If the occurence cannot be found, the original string is returned.
 * @param  string $search  The 'needle': the part of the string (occurence) to replace
 * @param  string $replace What to replace the occurence with
 * @param  string $str     The 'haystack': the string to search through
 * @return string          The string in which the occurrence is replaced by the supplied text
 */
	private function str_replace_last( $search , $replace , $str ) {
        if( ( $pos = strrpos( $str , $search ) ) !== false ) {
            $search_length  = strlen( $search );
            $str = substr_replace( $str , $replace , $pos , $search_length );
        }
        return $str;
    }

/**
 * Sets the DOM object of the class to the html directly retrieved from Google Scholar. The function returns the plain HTML. The
 * DOM object can be accessed by $<your_variable>->dom.
 * @param  string $id      The ID string of the Google Scholar userprofile to parse
 * @param  string $sort_by The variable to sort the parsed data by ("year" or false->number of citations)
 * @return string          The retrieved HTML. The Dom object is automatically set by this funcion overwriting its previous contents
 */
	public function read_html_from_scholar_profile($id, $sort_by="year"){		
		$url = "http://scholar.google.nl/citations?hl=en&user=" . $id . "&view_op=list_works";
		
		if($sort_by == "year"){
			$url .= "&sortby=pubdate";
		}

		// Create a PhantomJS request and response object
		$request = $this->phantomJsClient->getMessageFactory()->createRequest($url, 'GET');
		$response = $this->phantomJsClient->getMessageFactory()->createResponse();

		// Send the request
		$this->phantomJsClient->send($request, $response);
		
		if($response->getStatus() === 200) {
		    // Dump the requested page content
		    $html = $response->getContent();
		}else{
			throw new Exception("Invalid response code from Google Scholar. Received " . $response->geStatus());
		}

		// DOM to store html in. This stores the HTML page as a traversable tree, which can be queried by XPath
		$this->dom = new DOMDocument();
		@$this->dom->loadHTML($html);
		// XPath object to query DOM with
		$this->xpath = new DOMXPath($this->dom);
		return $html;
	}

/**
 * Generates a DOM object by reading the html from the specified file. The function returns the plain HTML. The
 * DOM object can be accessed by $<your_variable>->dom.
 * @param  string $filename The file to read
 * @return string         The HTML contained by the file
 */
	public function read_html_from_file($filename){
		// DOM to store html in. This stores the HTML page as a traversable tree, which can be queried by XPath
		$this->dom = new DOMDocument();
		$html = file_get_contents($filename); 
		@$this->dom->loadHTML($html);
		// XPath object to query DOM with
		$this->xpath = new DOMXPath($this->dom);
		return $html;	
	}

/**
 * Saves the current DOM object to a .json file
 * @param  string $filename The destination file path and name to save to.
 * @return void
 */
	public function save_to_json($filename){
		$json = json_encode($this->parsed_data);
		file_put_contents($filename, $json);
	}

/**
 * Read data from a json file and store it in the parsed_data variable
 * @param  string $filename The path to the file
 * @return void
 */
	public function read_json($filename){
		try{
			$json = file_get_contents($filename);
		}catch(Exception $e){
			echo "<p><b>Error:</b> " . $e->getMessage() . "</p>\n";
			return;
		}
		$this->parsed_data = json_decode($json, true);
	}

/**
 * Parses publications from the $this->dom variable. Stored them in the $this->parsed_data variable wit the key 'publications'
 * @param  boolean $needs_year If true, publications not having a date will be ommitted (to prevent contamination of the list by things other than journal articles, books and conference posters)
 * @return array              The publications parsed from the DOM
 */
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

/**
 * Parses the stats of the scholar profile user (Citations, H-Index, and i10 index)
 * @return string The HTML in which the stats data is displayed in a table
 */
	public function parse_stats(){
		// Get the table containing citation indices and stuff
		$stats_table = $this->xpath->query('//div[@class="gsc_rsb_s"]/table[@id="gsc_rsb_st"]');
		$temp_dom = new DOMDocument();
		foreach($stats_table as $n) $temp_dom->appendChild($temp_dom->importNode($n,true));
		// Just save the HTML table (not much to change about it)
		$this->parsed_data["stats"] = $temp_dom->saveHTML();
		return $this->parsed_data["stats"];
	}

/**
 * Prints the data currently in the parsed_data variable (convenience function)
 * @return void
 */
	public function print_parsed_data_raw(){
		echo "<pre>";
		print_r($this->parsed_data);
		echo "</pre>";
	}

/**
 * Formats the contents of $this->parsed_data["publications"] to an HTML table containing publications in APA format.
 * @param  boolean $show_citations if true, then an extra column showing the citations of each article is added
 * @param  boolean $table_header   if true, the table is given a header displaying title (in this case only for citations)
 * @return string                  The HTML code describing the table in which each publicaion is shown in a row
 */
	public function format_publications_in_APA($show_citations=true, $table_header=true){
		// First check if publication data is already present, and exit otherwise.
		if(!isset($this->parsed_data["publications"])){
			echo "<p><b>Error:</b>no publication data present. call parse functions first</p>";
			return;
		}
		$res = "<table class='publications-table'>";

		if($table_header){
			$res .= "<tr><th></th>";
			if($show_citations){
				$res .= "<th>Citations</th>";
			}
			$res .= "</tr>";
		}

		$pubs = $this->parsed_data["publications"];
		foreach($pubs as $pub){
			$pub_authors = $pub["authors"];
			// Replace last occurence of a , with a &
			$pub_authors = $this->str_replace_last(",", " &amp;", $pub_authors);
			$pub_authors = str_replace("Ã´","&ocirc;",$pub_authors);

			// Make the title a link that corresponds to the article on Google Scholar
			$formatted_title = "<a href='" . $pub["url"] . "'>" . $pub["title"] . "</a>";

			$apa_str = $pub_authors . " (" . $pub["year"] . ") " . $formatted_title . ". " . $pub["journal"];
			
			$citation_cell = "";
			if($show_citations){
				if(isset($pub["citations"])){
					$citation_count = "<a href='" . $pub["citations"]["url"] . "'>" . $pub["citations"]["amount"] . "</a>";
				}else{
					$citation_count = "";
				}
				$citation_cell = "<td class='citation-cell'>" . $citation_count . "</td>";
			}
			$res .= "<tr><td class='title-cell'>" . $apa_str . "</td>" . $citation_cell . "</tr>";
		}
		
		$res .= "</table>";
		return $res;
	}

/**
 * Returns the HTML currently in the parsed_data["stats"] variable
 * @return string HTML code displaying the stats in a table.
 */
	public function get_stats(){
		if(isset($this->parsed_data["stats"])){
			return $this->parsed_data["stats"];
		}else{
			echo "<p>Warning: stats not yet parsed</p>";
			return "";
		}
	}
}

?>