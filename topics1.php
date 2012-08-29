<html>
<head>
<link rel="stylesheet" href="http://www.washington.edu/classroom/classroom.css">
<script src="http://www.washington.edu/classroom/emc/banner.js" language="JavaScript"></script>
<?php

$DEBUG=1;

include('../dbinfo.php');
$Database="emc";

$Link_ID = mssql_connect($dbhost, $dbuser, $dbpass);
mssql_select_db($Database);

$title = 'EMC: Topical Index';

$banner = 'http://www.washington.edu/classroom/emc/topicals.gif';

if (isset($HTTP_GET_VARS['mid'])) {
  if (strncasecmp($HTTP_GET_VARS['mid'], 'S', 1)) {
	$Query_String = 'SELECT [Pretty Title] FROM MID
 WHERE MID=' . $HTTP_GET_VARS['mid'];
	if ($DEBUG) echo("<pre>$Query_String</pre>\n");
	$Query_ID = mssql_query($Query_String, $Link_ID);
	$title = 'EMC: Topics for: ' . mssql_result($Query_ID, 0, 0);

	$where[] = 'TopicID IN (SELECT TopicID FROM [Topic - MID Junction]
 WHERE MID = ' . $HTTP_GET_VARS['mid'] . ')';
  } else {
	$Query_String = "SELECT [Pretty Title] FROM SID
 WHERE SID='" . $HTTP_GET_VARS['mid'] . "'";
	if ($DEBUG) echo("<pre>$Query_String</pre>\n");
	$Query_ID = mssql_query($Query_String, $Link_ID);
	$title = 'EMC: Topics for: ' . mssql_result($Query_ID, 0, 0);

	$where[] = "TopicID IN (SELECT TopicID FROM [Topic - MID Junction]
 WHERE MID IN (SELECT MID FROM [SID - MID Junction] WHERE SID = '" . $HTTP_GET_VARS['mid'] . "'))";
  }
}

if (isset($HTTP_GET_VARS['withdrawn'])) {
  $where[] = 'TopicID IN (SELECT TopicID FROM [Topic - MID Junction]
 WHERE MID NOT IN (SELECT MID FROM FID WHERE WITHDRAWN = 0))';
} else {
  $where[] = 'TopicID IN (SELECT TopicID FROM [Topic - MID Junction]
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

echo "<title>$title</title>\n";

?>
<script language="javascript"> MOdefault.src="<?php echo $banner; ?>";</script>
</head>
<body>
<?php

echo "<h1>$title</h1>\n";

include('bannerbar.inc');

?>
<img src="http://www.washington.edu/classroom/blank.gif" width=600 height=12 name="status" alt="">
<blockquote>
 <p>
 <table align=right border=0 cellspacing=0 cellpadding=0 bgcolor="#883355">
   <tr><td><IMG alt="[EMC Logo]" SRC="http://www.washington.edu/classroom/emc/emctrans.gif"></td></tr>
 </table>

<?php include('topics.inc'); ?>

 <ul>
<?php

if ($DEBUG) echo("<pre>$Query_String</pre>\n");

$Main_Query = mssql_query($Query_String, $Link_ID);

while ($row = mssql_fetch_array($Main_Query)) {
  if (isset($lastTopic) && strncasecmp($row['Topic'], $lastTopic, 1)) {
	echo " </ul>\n <ul>\n";
  }
  $lastTopic = $row['Topic'];

  echo '  <li><a href="titles.php?topicid=' . $row['TopicID'] . '">' . $row['Topic'] . "</a></li>\n";

}

?>
 </ul>

 <br clear=all>
</blockquote>

<?php include 'bannerbar.inc'; ?>
<?php include 'footer.inc.php'; ?>

</body>
</html>
