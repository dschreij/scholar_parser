<?php
require_once("scholar_profile_parser.class.php");

$parser = new ScholarProfileParser();

# Example Google Scholar ID. Change this to your own
$profile_id = "Pm3O_58AAAAJ&hl";
if(file_exists("cache/" . $profile_id . ".json")){
	echo "<p>Reading from local cache</p>\n";
	$parser->read_json("cache/" . $profile_id . ".json");
}else{
	echo "<p>Reading from online profile page</p>\n";
	$parser->read_html_from_scholar_profile($profile_id);
	$parser->parse_publications();
	$parser->parse_stats();	
	if(!is_dir("./cache")){
		if(!mkdir("./cache")){
			echo "<b>ERROR</b> Failed to create cache folder";
		}
	}
	$parser->save_to_json("cache/" . $profile_id . ".json");
}	

$parser->print_parsed_data_raw();
echo $parser->format_publications_in_APA();



?>