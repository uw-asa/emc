<?php

$DEBUG=$_GET['debug'];

include('../dbinfo.php');
include('../misc.php');
$Database="emc";

$Link_ID = mssql_connect($dbhost, $dbuser, $dbpass);
mssql_select_db($Database)
  or die("Sorry, the system is currently down. Please try again later.");

if (strpos($_SERVER['REQUEST_URI'], '.php') === false)
{
  $rest = true;
}

$fid = intval($_GET['fid']);


?>
<html>
<head>
<title>Prints</title>
</head>
<body>
<?php

$query = "
SELECT FID.*,
  MID.[Pretty Title],
  Formats.Description AS format_description
 FROM FID
  LEFT OUTER JOIN MID ON MID.MID = FID.MID
  LEFT OUTER JOIN Formats ON Formats.FORMAT = FID.FORMAT
 WHERE FID.FID = $fid";

$result = mssql_query($query, $Link_ID);

if (is_resource($result))
{
  echo '
<table>
 <tr><th>FID</th><th>Title</th>';
  while ($print = mssql_fetch_array($result)) {
    echo '
 <tr>
  <td><a href="print/' . $print['FID'] . '">' . $print['FID'] . '</a></td>
  <td>' . $print['Pretty Title'] . '</td>
  <td>' . $print['format_description'] . '</td>
 </tr>';
  }
  echo '
</table>';
}

?>
</body>
</html>
