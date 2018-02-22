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

$page_title = 'Topics';

$withdrawn = isset($_GET['withdrawn']);

if (isset($_GET['mid']) && preg_match('/^[sS]?[0-9]+$/', $_GET['mid']))
{
  $mid = $_GET['mid'];
}

if (strpos($_SERVER['REQUEST_URI'], '.php'))
{
  header('Location: http://www.cte.uw.edu/emc/topics/' . $mid . ($withdrawn ? '?withdrawn' : ''), true, 301);
  exit;
}

if (isset($mid)) {
  if (strncasecmp($mid, 'S', 1)) {
	$Query_String = '
SELECT [Pretty Title] FROM MID
 WHERE MID=' . $mid;
	if ($DEBUG) echo("<pre>$Query_String</pre>\n");
	$Query_ID = mssql_query($Query_String, $Link_ID);
	$page_title = 'Topics for: ' . mssql_result($Query_ID, 0, 0);

	$where[] = '
TopicID IN (SELECT TopicID FROM [Topic - MID Junction]
             WHERE MID = ' . $mid . ')';
  } else {
	$Query_String = "
SELECT [Pretty Title] FROM SID
 WHERE SID='" . $mid . "'";
	if ($DEBUG) echo("<pre>$Query_String</pre>\n");
	$Query_ID = mssql_query($Query_String, $Link_ID);
	$page_title = 'Topics for: ' . mssql_result($Query_ID, 0, 0);

	$where[] = "
TopicID IN (SELECT TopicID FROM [Topic - MID Junction]
             WHERE MID IN (SELECT MID FROM [SID - MID Junction]
                            WHERE SID = '" . $mid . "'))";
  }
}

if ($withdrawn) {
  $where[] = '
TopicID IN (SELECT TopicID FROM [Topic - MID Junction]
             WHERE MID NOT IN (SELECT MID FROM FID WHERE WITHDRAWN = 0))';
} else {
  $where[] = '
TopicID IN (SELECT TopicID FROM [Topic - MID Junction]
             WHERE MID IN (SELECT MID FROM FID WHERE WITHDRAWN = 0))';
}

$order[] = 'Topic';

$Query_String = 'SELECT * FROM FullTopicNames';

if (is_array($where)) {
  $Query_String .= ' WHERE ' . implode(' AND ', $where);
}

if (is_array($order)) {
  $Query_String .= ' ORDER BY ' . implode(', ', $order);
}

?>
<title><?= $page_title ?></title>
</head>
<body>
<?php

echo "<h1>$page_title</h1>";

?>
<table id="topics" class="row-border">
 <thead>
  <tr><th>TopicID</th><th>Topic</th></tr>
 </thead>
<tbody>
<?php

if ($DEBUG) echo("<pre>$Query_String</pre>\n");

$Main_Query = mssql_query($Query_String, $Link_ID);

while ($row = mssql_fetch_array($Main_Query)) {
  echo '<tr><td>' . $row['TopicID'] . '</td><td><a href="../topic/' . $row['TopicID'] . '">' . $row['Topic'] . "</a></td></tr>\n";
}

?>
</tbody></table>

<script>
$(document).ready(function() {
  $('#topics').DataTable({
    paging: false,
    dom: 'Bfrti',
    buttons: [
      'csv',
      'excel',
    ],
    order: [[1, 'asc']],
  });
});
</script>
</body>
</html>
