<?php

$DEBUG=$_GET['debug'];

include('dbinfo.php');
$Database="emc";

$Link_ID = mssql_connect($dbhost, $dbuser, $dbpass);
mssql_select_db($Database)
  or die("Sorry, the system is currently down. Please try again later.");

$logo_color = '#339933';
$title = 'Educational Media Collection';

echo "<h1>$title</h1>\n";

$Query_String = 'SELECT CONVERT(varchar, MAX(DATE_OF_CHANGE), 106) AS updated FROM MID';
$Query_ID = mssql_query($Query_String, $Link_ID);
$row = mssql_fetch_array($Query_ID);
$updated = $row['updated'];

echo "Updated: $updated\n";

echo "<p>\n";

?>
<ul>
 <li><a href="titles/?index">Index of Titles</a></li>
 <li><a href="prints/">Index of Prints</a></li>
 <li><a href="titles/?new">New Titles (Added within the past year)</a></li>
 <li><a href="titles/">Abstracts of Titles</a><ul>
   <li><a href="titles/?search">Search the Abstracts</a></li>
 </ul></li>
 <li><a href="topics/">Topical Index</a></li>
 <li><a href="titles/?withdrawn">Titles Withdrawn (since 1991)</a></li>
</ul>
