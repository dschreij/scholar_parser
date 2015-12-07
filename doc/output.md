||
|Scholar Profile parser  1.0|

Detailed Description
--------------------

Parser of Google Scholar user profile pages.

This class parses publications and stats from Google Scholar user pages. The retrieved data can be exported to json.

Author  
Daniel Schreij

Version  
1.0

Date  
2015

Copyright  
GNU Public License

Member Function Documentation
-----------------------------

||
|format\_publications\_in\_APA|(| |*\$show\_citations* = `true`,|
||| |*\$table\_header* = `true` |
||)|||

Formats the contents of \$this-\>parsed\_data["publications"] to an HTML table containing publications in APA format.

Parameters  
||
|boolean|\$show\_citations|if true, then an extra column showing the citations of each article is added|
|boolean|\$table\_header|if true, the table is given a header displaying title (in this case only for citations)|

Returns  
string The HTML code describing the table in which each publicaion is shown in a row

||
|get\_stats|(||)||

Returns the HTML currently in the parsed\_data["stats"] variable

Returns  
string HTML code displaying the stats in a table.

||
|parse\_publications|(| |*\$needs\_year* = `true`|)||

Parses publications from the \$this-\>dom variable. Stored them in the \$this-\>parsed\_data variable wit the key 'publications'

Parameters  
||
|boolean|\$needs\_year|If true, publications not having a date will be ommitted (to prevent contamination of the list by things other than journal articles, books and conference posters)|

Returns  
array The publications parsed from the DOM

||
|parse\_stats|(||)||

Parses the stats of the scholar profile user (Citations, H-Index, and i10 index)

Returns  
string The HTML in which the stats data is displayed in a table

||
|print\_parsed\_data\_raw|(||)||

Prints the data currently in the parsed\_data variable (convenience function)

Returns  
void

||
|read\_html\_from\_file|(| |*\$filename*|)||

Generates a DOM object by reading the html from the specified file. The function returns the plain HTML. The DOM object can be accessed by \$\<your\_variable\>-\>dom.

Parameters  
||
|string|\$filename|The file to read|

Returns  
string The HTML contained by the file

||
|read\_html\_from\_scholar\_profile|(| |*\$id*,|
||| |*\$sort\_by* = `"year"` |
||)|||

Sets the DOM object of the class to the html directly retrieved from Google Scholar. The function returns the plain HTML. The DOM object can be accessed by \$\<your\_variable\>-\>dom.

Parameters  
||
|string|\$id|The ID string of the Google Scholar userprofile to parse|
|string|\$sort\_by|The variable to sort the parsed data by ("year" or false-\>number of citations)|

Returns  
string The retrieved HTML. The Dom object is automatically set by this funcion overwriting its previous contents

||
|read\_json|(| |*\$filename*|)||

Read data from a json file and store it in the parsed\_data variable

Parameters  
||
|string|\$filename|The path to the file|

Returns  
void

||
|save\_to\_json|(| |*\$filename*|)||

Saves the current DOM object to a .json file

Parameters  
||
|string|\$filename|The destination file path and name to save to.|

Returns  
void

||
|[ScholarProfileParser](classScholarProfileParser.html)|(| |*\$scholar\_user\_id* = `""`,|
||| |*\$sort\_by* = `"year"` |
||)|||

Constructor

Parameters  
||
|string|\$scholar\_user\_id|The ID string with which to identify the Scholar User Profile (e.g. Pm3O\_58AAAAJ)|
|string|\$sort\_by|The variable to order the publications by (default='year'; if 'false' they are orderd by citation count (Descending))|

* * * * *

The documentation for this class was generated from the following file:

-   scholar\_profile\_parser.class.php

* * * * *

Generated on Fri May 29 2015 15:23:16 for Scholar Profile parser by  [![doxygen](doxygen.png)](http://www.doxygen.org/index.html) 1.8.6
