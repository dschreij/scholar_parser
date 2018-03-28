<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// The amount of days after which to refresh the json file
define("JSON_EXPIRY_DAYS",3);
// Our workhorse: the ScholarProfileParser class
require_once("scholar_profile_parser.class.php");

$parser = new ScholarProfileParser();

// The profile to parse (mine in this case)
$profile_id = "Pm3O_58AAAAJ&hl";

// Google Scholar doesn't like to be queried by machines and will block you on suspicion of being a bot after some time, 
// so cache the results in a json file and read from there if available

// The path where the cached json file would be stored
$json_file  = "cache/" . $profile_id . ".json";
$refresh_json_file = false;
if(!file_exists($json_file)){
	/* 
	Check if json file exists for this author.
	if not query Google Scholar and create one
	*/
	$refresh_json_file = true;
}else{
	// Check if the json file is older than PUB_EXPIRY_DAYS and if so, renew
	$ftime = new DateTime();
	$ftime->setTimestamp(filemtime($json_file));
	$now = new DateTime();

	$interval = $ftime->diff($now);
	$daysSinceChange = intval($interval->format('%a'));

	if($daysSinceChange >= JSON_EXPIRY_DAYS){
		$refresh_json_file = true;
	}
}

// If the json file needs to be refreshed, retrieve and parse the new data from Google Scholar
if($refresh_json_file == true){
	echo "<p>Reading from online profile page</p>\n";	
	$parser->read_html_from_scholar_profile($profile_id);
	$parser->parse_publications();
	$parser->parse_stats();	
	$parser->save_to_json($json_file);
// Otherwise read the data already stored in the json file
}else{
	echo "<p>Reading from local cache</p>\n";
	$parser->read_json($json_file);
}

// Print the output
$parser->print_parsed_data_raw();
echo $parser->format_publications_in_APA();

?>