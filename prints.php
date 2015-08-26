<?php

ob_start();

require_once('../lucid_f.php');

$DEBUG=$_GET['debug'];

include('dbinfo.php');
include('../misc.php');
$Database="emc";

$Link_ID = mssql_connect($dbhost, $dbuser, $dbpass);
mssql_select_db($Database)
  or die("Sorry, the system is currently down. Please try again later.");

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
   break;
}

$mid = intval($_GET['mid']);

if ($mid) {
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
  <td>' . $print['Pretty Title'] . '</td>
  <td align="center">' . $print['format_description'] . '</td>
  <td>' . $print['SHELF_NUMBER'] . '</td>
 </tr>';
  }
  echo '
</table>';
}

f_lucid_render(ob_get_clean());
