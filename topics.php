<?php

require_once('../lucid_f.php');

f_set_parm('name', 'titles');
//f_set_parm('title', 'STF Equipment Request');
f_set_parm('parent', db_getPageID('emc'));
f_set_parm('template', db_getTemplateID('uwit'));
f_set_parm('style', db_getStyleID('blog'));
f_set_parm('markup', 'none');

ob_start();

//$DEBUG=1;

include('dbinfo.php');
$Database="emc";

$Link_ID = mssql_connect($dbhost, $dbuser, $dbpass);
mssql_select_db($Database)
  or die("Sorry, the system is currently down. Please try again later.");

$withdrawn = isset($_GET['withdrawn']);

if (preg_match('/^[sS]?[0-9]+$/', $_GET['mid']))
{
  $mid = $_GET['mid'];
}

if (strpos($_SERVER['REQUEST_URI'], '.php') === false)
{
  $rest = 1;
}
else
{
  header('Location: http://www.css.washington.edu/emc/topics/' . $mid . ($withdrawn ? '?withdrawn' : ''), true, 301);
  exit;
}

$title = 'EMC: Topical Index';

$banner = 'http://www.washington.edu/classroom/emc/topicals.gif';

if (isset($mid)) {
  if (strncasecmp($mid, 'S', 1)) {
	$Query_String = '
SELECT [Pretty Title] FROM MID
 WHERE MID=' . $mid;
	if ($DEBUG) echo("<pre>$Query_String</pre>\n");
	$Query_ID = mssql_query($Query_String, $Link_ID);
	$title = 'EMC: Topics for: ' . mssql_result($Query_ID, 0, 0);

	$where[] = '
TopicID IN (SELECT TopicID FROM [Topic - MID Junction]
             WHERE MID = ' . $mid . ')';
  } else {
	$Query_String = "
SELECT [Pretty Title] FROM SID
 WHERE SID='" . $mid . "'";
	if ($DEBUG) echo("<pre>$Query_String</pre>\n");
	$Query_ID = mssql_query($Query_String, $Link_ID);
	$title = 'EMC: Topics for: ' . mssql_result($Query_ID, 0, 0);

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

f_set_parm('title', $title);

echo "<h1>$title</h1>\n";

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

  if ($rest)
    {
      echo '  <li><a href="../topic/' . $row['TopicID'] . '">' . $row['Topic'] . "</a></li>\n";
    }
  else
    {
      echo '  <li><a href="titles.php?topicid=' . $row['TopicID'] . '">' . $row['Topic'] . "</a></li>\n";
    }

}

?>
 </ul>

<?php

f_lucid_render(ob_get_clean());
