<?php

//$DEBUG=1;

include('dbinfo.php');
$Database="emc";

$Link_ID = mssql_connect($dbhost, $dbuser, $dbpass);
mssql_select_db($Database)
  or die("Sorry, the system is currently down. Please try again later.");

$page_title = 'Topics';

$withdrawn = isset($_GET['withdrawn']);

if (preg_match('/^[sS]?[0-9]+$/', $_GET['mid']))
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

echo "<h1>$page_title</h1>";

?>
 <ul>
<?php

if ($DEBUG) echo("<pre>$Query_String</pre>\n");

$Main_Query = mssql_query($Query_String, $Link_ID);

while ($row = mssql_fetch_array($Main_Query)) {
  if (isset($lastTopic) && strncasecmp($row['Topic'], $lastTopic, 1)) {
	echo " </ul>\n <ul>\n";
  }
  $lastTopic = $row['Topic'];

  echo '  <li><a href="../topic/' . $row['TopicID'] . '">' . $row['Topic'] . "</a></li>\n";
}

?>
 </ul>
