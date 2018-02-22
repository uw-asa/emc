<?php

include('dbinfo.php');
include('misc.php');
$Database="emc";

$Link_ID = mssql_connect($dbhost, $dbuser, $dbpass);
mssql_select_db($Database)
  or die("Sorry, the system is currently down. Please try again later.");

$logo_color = '#339933';
$title = 'Educational Media Collection';

echo "<h1>$title</h1>\n";

echo "<p>\n";

?>
<ul>
 <li><a href="titles/?index">Index of Titles</a></li>
 <li><a href="prints/">Index of Prints</a> - 
<?php

$Query_String = 'SELECT DISTINCT Description FROM Formats';
$result = mssql_query($Query_String, $Link_ID);
while ($format = mssql_fetch_array($result)) {
    echo '<a href="prints/?format=' . htmlspecialchars($format['Description']) . '">' . $format['Description'] . '</a> ';
}

?>
 </li>
 <li><a href="titles/">Abstracts of Titles</a> - 
    <?php foreach (range('A', 'Z') as $letter): ?>
     <a href="titles/<?= $letter ?>"><?= $letter?></a>
    <?php endforeach; ?>
  <ul>
   <li><a href="titles/?search">Search the Abstracts</a></li>
  </ul>
 </li>
 <li><a href="topics/">Topical Index</a></li>
</ul>
