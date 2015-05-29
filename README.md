#Scholar parser

(highly experimental)

This class parses a profile page from Google Scholar for publication data and scientist stats. The page can be read directly from Google Scholar by supplying the user's profile ID, or by passing a HTML file saved from Scholar to the class.

##Example usage
Below is a very basic example. For a more elaborate one see the example.php file which uses a basic caching mechanism to not query Scholar with each page view request

```php
// Create a new instance of the parser class
require_once("scholar_profile_parser.class.php");
$parser = new ScholarProfileParser();

// The profile to parse (mine in this case)
$profile_id = "Pm3O_58AAAAJ&hl";

// Read the html from Scholar into a DOM object
$parser->read_html_from_scholar_profile($scholar_id);
// Parse publication data from the DOM
$parser->parse_publications();
// Parse stats from the DOM (H-Index, citation count, i10 index)
$parser->parse_stats(); 

// Print the output
$parser->print_parsed_data_raw();   //Basic output as stored in JSON
echo $parser->format_publications_in_APA();  //Formatted as HTML table
```

##API Documentation

Soon to follow once I figure out how to output nicely formatted markdown documentation with doxygen. For now, the documentation can be found in the doc/html folder inside the repository.