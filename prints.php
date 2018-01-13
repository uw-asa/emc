<?php

$DEBUG=$_GET['debug'];

include('dbinfo.php');
include('../misc.php');
$Database="emc";

$Link_ID = mssql_connect($dbhost, $dbuser, $dbpass);
mssql_select_db($Database)
  or die("Sorry, the system is currently down. Please try again later.");

$page_title = 'Prints';

if (strpos($_SERVER['REQUEST_URI'], '.php') === false)
{
  $rest = true;
}

switch ($_GET['format']) {
 case '16mm':
 case '3/4"':
 case 'beta':
 case 'dvd':
 case 'mm':
 case 'laserdisc':
 case 'vhs':
   $where[] = "Formats.Description = '" . $_GET['format'] . "'";
   $page_title = $_GET['format'] . ' ' . $page_title;
   break;
}

$mid = intval($_GET['mid']);

if ($mid) {
  $Query_String = '
SELECT [Pretty Title] FROM MID
 WHERE MID=' . $mid;
  if ($DEBUG) echo("<pre>$Query_String</pre>\n");
  $Query_ID = mssql_query($Query_String, $Link_ID);
  $page_title = $page_title . ' for: ' . mssql_result($Query_ID, 0, 0);

  $where[] = "FID.MID = $mid";
}

$query = "
SELECT FID.*,
  MID.[Pretty Title],
  Formats.Description AS format_description,
  SHELF_LIST.SHELF_NUMBER
 FROM FID
  LEFT OUTER JOIN MID ON MID.MID = FID.MID
  LEFT OUTER JOIN Formats ON Formats.FORMAT = FID.FORMAT
  LEFT OUTER JOIN SHELF_LIST ON SHELF_LIST.FID = FID.FID
  LEFT OUTER JOIN MEDIA ON MEDIA.MEDIUM = FID.MEDIUM
  LEFT OUTER JOIN [REELS-Denormalized] ON [REELS-Denormalized].FID = FID.FID";

if ($where) {
  $query .= "
 WHERE " . implode(' AND ', $where);
}

$query .= "
 ORDER BY MID.[Alphabetic Title], MEDIA.MediumSortable, [REELS-Denormalized].REEL1";

if ($DEBUG) echo("<pre>$query</pre>\n");

echo "<h1>$page_title</h1>";

$result = mssql_query($query, $Link_ID);

if (is_resource($result))
{
  echo '
<table>
 <tr><th>FID</th><th>Title</th><th>Format</th><th>Shelf #</th>';
  while ($print = mssql_fetch_array($result)) {
    echo '
 <tr>
  <td><!-- a href="../print/' . $print['FID'] . '" -->' . $print['FID'] . '<!-- /a --></td>
  <td><a href="../title/' . $print['MID'] . '">' . $print['Pretty Title'] . '</a></td>
  <td align="center">' . $print['format_description'] . '</td>
  <td>' . rtrim($print['SHELF_NUMBER'], '/') . '</td>
 </tr>';
  }
  echo '
</table>';
}
