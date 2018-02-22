<html>
<head>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.16/css/jquery.dataTables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.5.1/css/buttons.dataTables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/colreorder/1.4.1/css/colReorder.dataTables.min.css"/>
 
<script type="text/javascript" src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
<script type="text/javascript" src="https://code.jquery.com/jquery-1.12.3.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.5.1/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.5.1/js/buttons.flash.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.5.1/js/buttons.html5.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/colreorder/1.4.1/js/dataTables.colReorder.min.js"></script>
<?php

include('dbinfo.php');
include('misc.php');
$Database="emc";

$Link_ID = mssql_connect($dbhost, $dbuser, $dbpass);
mssql_select_db($Database)
  or die("Sorry, the system is currently down. Please try again later.");

$page_title = 'Prints';

if (strpos($_SERVER['REQUEST_URI'], '.php') === false)
{
  $rest = true;
}

if (isset($_GET['format']))
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

$mid = isset($_GET['mid']) ? intval($_GET['mid']) : false;

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
  MID.[Alphabetic Title],
  MID.[Pretty Title],
  Formats.Description AS format_description,
  SHELF_LIST.SHELF_NUMBER
 FROM FID
  LEFT OUTER JOIN MID ON MID.MID = FID.MID
  LEFT OUTER JOIN Formats ON Formats.FORMAT = FID.FORMAT
  INNER JOIN SHELF_LIST ON SHELF_LIST.FID = FID.FID
  LEFT OUTER JOIN MEDIA ON MEDIA.MEDIUM = FID.MEDIUM
  LEFT OUTER JOIN [REELS-Denormalized] ON [REELS-Denormalized].FID = FID.FID";

if (isset($where)) {
  $query .= "
 WHERE " . implode(' AND ', $where);
}

$query .= "
 ORDER BY MID.[Alphabetic Title], MEDIA.MediumSortable, [REELS-Denormalized].REEL1";

if ($DEBUG) echo("<pre>$query</pre>\n");

?>
<title><?= $page_title ?></title>
</head>
<body>
<?php
echo "<h1>$page_title</h1>";

$result = mssql_query($query, $Link_ID);

if (is_resource($result))
{
  echo '
<table id="prints" class="row-border">
 <thead><tr><th>FID</th><th>Alpha Title</th><th>Title</th><th>Format</th><th>Shelf #</th></tr></thead><tbody>';
  while ($print = mssql_fetch_array($result)) {
    echo '
 <tr>
  <td><!-- a href="../print/' . $print['FID'] . '" -->' . $print['FID'] . '<!-- /a --></td>
  <td>' . $print['Alphabetic Title'] . '</a></td>
  <td><a href="../title/' . $print['MID'] . '">' . $print['Pretty Title'] . '</a></td>
  <td align="center">' . $print['format_description'] . '</td>
  <td>' . rtrim($print['SHELF_NUMBER'], '/') . '</td>
 </tr>';
  }
  echo '
</tbody></table>';
}

?>
<script>
$(document).ready(function() {
  $('#prints').DataTable({
    paging: false,
    dom: 'Bfrti',
    buttons: [
      'csv',
      'excel',
    ],
  });
});
</script>
</body>
</html>
