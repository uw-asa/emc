<html>
<head>
<link rel="stylesheet" href="http://www.washington.edu/classroom/classroom.css">
<?php

$DEBUG=$_GET['debug'];

include('../dbinfo.php');
include('../misc.php');
$Database="emc";

$Link_ID = mssql_connect($dbhost, $dbuser, $dbpass);
mssql_select_db($Database);

$titles_query = "
SELECT DISTINCT CONVERT(varchar, MID.MID) AS MID, MID.Title, MID.[Alphabetic Title],
 MID.[Pretty Title], COALESCE(MID.[Date Added], '1970-01-01') AS [Date Added],
 MID.Year, MID.Color, MID.[Running Time],
 MID.[Rental Rate], MID.Abstract, MID.Language, MID.Subtitles,
 MID.RestrictionID, [MIDFormats-Combined].Formats,
 COALESCE(FID.WITHDRAWN, 1) AS WITHDRAWN
 FROM MID
  LEFT OUTER JOIN [MIDFormats-Combined] ON MID.MID = [MIDFormats-Combined].MID
  LEFT OUTER JOIN FID ON MID.MID = FID.MID AND FID.WITHDRAWN = 0";

$series_query .= "
SELECT SID AS MID, Title, [Alphabetic Title],
 [Pretty Title], COALESCE([Date Added], '1970-01-01') AS [Date Added],
 NULL AS Year, NULL AS Color, NULL AS [Running Time],
 NULL AS [Rental Rate], NULL as Abstract, NULL AS Language, NULL AS Subtitles,
 NULL AS RestrictionID, NULL AS Formats,
 NULL AS WITHDRAWN
 FROM SID";

$logo_color = '#339933';

$banner = 'titles';

if ($_GET['abstracts'])
{
  $title = 'EMC: Abstracts';
  $logo_color = '#993333';
  $banner = 'abstracts';

  $Query_String = 'SELECT * FROM Restrictions';
  if ($DEBUG) echo("<pre>$Query_String</pre>\n");
  $Query_ID = mssql_query($Query_String, $Link_ID);
  while ($row = mssql_fetch_array($Query_ID))
  {
	$Restrictions[$row['RestrictionID']] = $row['Restriction'];
  }

  $Query_String = 'SELECT * FROM Languages';
  if ($DEBUG) echo("<pre>$Query_String</pre>\n");
  $Query_ID = mssql_query($Query_String, $Link_ID);
  while ($row = mssql_fetch_array($Query_ID))
  {
	$Languages[$row['Language ID']] = $row['Language'];
  }

}
else
{
	$title = 'EMC: Titles';
	$logo_color = '#885533';
	$banner = 'titles';
}

if ($_GET['index'])
{
	$title .= ': Index';
}

if (isset($_GET['topicid']))
{
  $Query_String = 'SELECT Topic FROM FullTopicNames
  WHERE TopicID=' . $_GET['topicid'];
  if ($DEBUG) echo("<pre>$Query_String</pre>\n");
  $Query_ID = mssql_query($Query_String, $Link_ID);
  $title = 'EMC: Topical Listings: ' . mssql_result($Query_ID, 0, 0);
  $where[] = 'MID.MID IN (SELECT MID FROM [Topic - MID Junction]
  WHERE TopicID=' . $_GET['topicid'] . ')';
  $swhere[] = 'SID IS NULL'; // don't show series
}

if ($_GET['withdrawn'])
{
  $title .= ': Withdrawn';
  $logo_color = '#553377';
  $banner = 'withdrawn';
  $where[] = 'MID.MID NOT IN (SELECT MID FROM FID WHERE WITHDRAWN = 0)';
  $swhere[] = 'SID IN (SELECT SID FROM [SID - MID Junction]
  WHERE MID NOT IN (SELECT MID FROM FID WHERE WITHDRAWN = 0))';
  $tlwhere[] = 'MID.MID NOT IN (SELECT MID FROM FID WHERE WITHDRAWN = 0)';
}
else
{
  $where[] = 'MID.MID IN (SELECT MID FROM FID WHERE WITHDRAWN = 0)';
  $swhere[] = 'SID IN (SELECT SID FROM [SID - MID Junction]
  WHERE MID IN (SELECT MID FROM FID WHERE WITHDRAWN = 0))';
  $tlwhere[] = 'MID.MID IN (SELECT MID FROM FID WHERE WITHDRAWN = 0)';
}

if (isset($_GET['added']))
{
  $where[] = "MID.[Date Added] > '" . $_GET['added'] . "'";
  $swhere[] = "SID IN (SELECT SID FROM [SID - MID Junction]
  WHERE MID IN (SELECT MID FROM MID
   WHERE MID.[Date Added] > '" . $_GET['added'] . "'))";
  $tlwhere[] = "MID.[Date Added] > '" . $_GET['added'] . "'";
}

if ($_GET['new'])
{
  $title .= ': New';
  $logo_color = '#557733';
  $banner = 'newtitles';
  $where[] = "MID.[Date Added] > '" . date('Y-m-d', strtotime('-1 year')) . "'";
  $swhere[] = "SID IN (SELECT SID FROM [SID - MID Junction]
  WHERE MID IN (SELECT MID FROM MID
   WHERE MID.[Date Added] > '" . date('Y-m-d', strtotime('-1 year')) . "'))";
  $tlwhere[] = "MID.[Date Added] > '" . date('Y-m-d', strtotime('-1 year')) . "'";
}

if (isset($_GET['title']))
{
  $title .= ': ' . strtoupper($_GET['title']);
  $where[] = "MID.[Alphabetic Title] LIKE '" . $_GET['title'] . "%'";
  $swhere[] = "[Alphabetic Title] LIKE '" . $_GET['title'] . "%'";
}

if (isset($_GET['search']))
{
  $title .= ': Search';
  $logo_color = '#333399';
  $banner = 'search';

  if ($_GET['search'])
  {
	$title .= ': ' . $_GET['search'];
	if ($_GET['abstracts'])
	{
	  $where[] = "(MID.Title LIKE '%" . $_GET['search'] . "%' OR MID.Abstract LIKE '%" . $_GET['search'] . "%')";
	}
	else
	{
	  $where[] = "MID.Title LIKE '%" . $_GET['search'] . "%'";
	}
	$swhere[] = "Title LIKE '%" . $_GET['search'] . "%'";
  }
  else
  {
	$where[] = "MID.MID IS NULL";
	$swhere[] = "SID IS NULL";
  }
}

$order[] = '[Alphabetic Title]';

if (is_array($where))
{
  $where_clause = '
 WHERE ' . implode('
  AND ', $where);
}

if (is_array($swhere))
{
  $swhere_clause = '
 WHERE ' . implode('
  AND ', $swhere);
}

$Query_String = $titles_query . $where_clause . '
UNION '. $series_query . $swhere_clause;

if (is_array($order))
{
  $Query_String .= '
ORDER BY ' . implode(', ', $order);
}

if (isset($_GET['mid']))
{
  if (strncasecmp($_GET['mid'], 'S', 1))
  {
	$Query_String = $titles_query . ' WHERE MID.MID = ' . $_GET['mid'];
  }
  else
  {
	$Query_String = $series_query . " WHERE SID = '" . $_GET['mid'] . "'";
  }
}

if ($DEBUG) echo("<pre>$Query_String</pre>\n");

$tlorder[] = '[SID - MID Junction].[Title Number]';
$tlorder[] = 'MID.[Alphabetic Title]';

$slorder[] = 'SID.[Alphabetic Title]';

echo "<title>$title</title>\n";

include("bannerscript.inc.php");

?>
</head>
<body>
<?php

echo "<h1>$title</h1>\n";

if (! $_GET['supressform']) {
	include('bannerbar.inc');
?>
<img src="http://www.css.washington.edu/banner.php?text=<?php echo $bannertext; ?>" width=600 height=12 name="status" alt="">
<blockquote>
 <p>
 <table align=right border=0 cellspacing=0 cellpadding=0 bgcolor="<?php echo $logo_color; ?>">
   <tr><td><IMG alt="[EMC Logo]" SRC="http://www.washington.edu/classroom/emc/emctrans.gif"></td></tr>
 </table>

<?php
}

if (isset($_GET['search']))
{
  if (! $_GET['supressform'])
  {
?>
 <form>
  Search the EMC Database for:
  <br><input type="text" name="search" value="<?php echo $_GET['search']; ?>" size=30><input type="submit" value="Search">
  <br><input type="radio" name="abstracts" value="0"<?php if (! $_GET['abstracts']) { echo ' checked'; } ?>>Search Titles
  <br><input type="radio" name="abstracts" value="1"<?php if ($_GET['abstracts']) { echo ' checked'; } ?>>Search Titles and Descriptions
  <br><input type="checkbox" name="supressform" value="1"<?php if ($_GET['supressform']) { echo ' checked'; } ?>>Plain results (for printing)
 </form>
<?php
  }
}
elseif ($_GET['new'])
{
	$Updated_Query = 'SELECT CONVERT(varchar, MAX([Date Added]), 106) AS updated FROM MID WHERE MID IN (SELECT MID FROM FID WHERE WITHDRAWN = 0)';
	$Query_ID = mssql_query($Updated_Query, $Link_ID);
	$row = mssql_fetch_array($Query_ID);
	$updated = $row['updated'];
	echo "Updated: $updated\n";
	include('newtitles.inc');
}
elseif ($_GET['index'])
{
	include('absindex.inc');
}
elseif ($_GET['withdrawn'])
{
	$Updated_Query = 'SELECT CONVERT(varchar, MAX(DATE_WITHDRAWN), 106) AS updated FROM FID WHERE MID NOT IN (SELECT MID FROM FID WHERE WITHDRAWN = 0)';
	$Query_ID = mssql_query($Updated_Query, $Link_ID);
	$row = mssql_fetch_array($Query_ID);
	$updated = $row['updated'];
	echo "Updated: $updated\n";
	include('withdrawn.inc');
}
elseif ($_GET['topicid'])
{
	$Updated_Query = 'SELECT CONVERT(varchar, MAX([Date Added]), 106) AS updated FROM MID WHERE MID IN (SELECT MID FROM [Topic - MID Junction] WHERE TopicID=' . $_GET['topicid'] . ') AND MID IN (SELECT MID FROM FID WHERE WITHDRAWN = 0)';
	$Query_ID = mssql_query($Updated_Query, $Link_ID);
	$row = mssql_fetch_array($Query_ID);
	$updated = $row['updated'];
	echo "Updated: $updated\n";
	include('topical.inc');
}
elseif ($_GET['abstracts'])
{
	if ($_GET['title'])
	{
		$Updated_Query = "SELECT CONVERT(varchar, MAX([Date Added]), 106) AS updated FROM MID WHERE MID IN (SELECT MID FROM FID WHERE WITHDRAWN = 0) AND MID.[Alphabetic Title] LIKE '" . $_GET['title'] . "%'";
		$Query_ID = mssql_query($Updated_Query, $Link_ID);
		$row = mssql_fetch_array($Query_ID);
		$updated = $row['updated'];
		echo "Updated: $updated\n";
	}		

}
else
{
	$Updated_Query = 'SELECT CONVERT(varchar, MAX(DATE_OF_CHANGE), 106) AS updated FROM MID WHERE MID IN (SELECT MID FROM FID WHERE WITHDRAWN = 0)';
	$Query_ID = mssql_query($Updated_Query, $Link_ID);
	$row = mssql_fetch_array($Query_ID);
	$updated = $row['updated'];
	echo "Updated: $updated\n";
	include('titles.inc');
}

$Main_Query = mssql_query($Query_String, $Link_ID);

if (! $_GET['abstracts'])
{
  echo " <ul>\n";
}

while ($row = mssql_fetch_array($Main_Query))
{

  if ($_GET['index'])
  {
	if (strncasecmp($row['Alphabetic Title'], $lastTitle, $_GET['index']))
	{
	  $idx = strtoupper(substr($row['Alphabetic Title'], 0, $_GET['index']));
	  echo '<a href="' . $PHP_SELF . '?';
	  if ($_GET['abstracts'])
	  {
		echo 'abstracts=1&';
	  }
	  echo 'title=' . $idx . '">' . $idx . "</a>, \n";
	}
	$lastTitle = $row['Alphabetic Title'];
	
  }
  elseif ($_GET['abstracts'])
  {
	echo " <ul>\n  <li>";
	if (strtotime($row['Date Added']) > strtotime('-1 year'))
	{
		echo '<b>';
	}
	if ($_GET['search'])
	{
		echo html_highlight($_GET['search'], strtoupper(strtr($row['Pretty Title'], $special_lower, $special_upper)));
	}
	else
	{
		echo strtoupper(strtr($row['Pretty Title'], $special_lower, $special_upper));
	}
	if (strtotime($row['Date Added']) > strtotime('-1 year')) {
	  echo '</b>';
	}
	echo "\n   <ul>\n";

	if (strncasecmp($row['MID'], 'S', 1))
	{

	  $Query_String = "
SELECT [SID - MID Junction].*, SID.[Pretty Title],
 COALESCE(SID.[Date Added], '1970-01-01') AS [Date Added]
 FROM [SID - MID Junction]
 INNER JOIN SID ON [SID - MID Junction].SID = SID.SID";

	  $Query_String .= "
 WHERE MID = '" . $row['MID'] . "'";

	  $Query_String .= ' ORDER BY ' . implode(', ', $slorder);

	  if ($DEBUG) echo("<pre>$Query_String</pre>\n");

	  $Query_ID = mssql_query($Query_String, $Link_ID);

	  echo "    <li>${row['Year']} ----- ";
	  if (!strncasecmp($row['Color'], 'y', 1))
	  {
		echo "color";
	  }
	  elseif (!strncasecmp($row['Color'], 'n', 1))
	  {
		echo "b & w";
	  }
	  else
	  {
		echo $row['Color'];
	  }
	  echo " ----- ";
	  if ($row['Running Time'])
	  {
		echo "${row['Running Time']} min";
	  }
	  echo " ----- ";
	  if ($row['WITHDRAWN'])
	  {
		echo "<i>withdrawn</i>";
	  }
	  else
	  {
		if ($row['Rental Rate'])
		{
			echo sprintf($currency, $row['Rental Rate']);
		}
	  }
	  echo " ----- ${row['Formats']}</li>\n    <li>\n";

	  if (1) //is_resource($s_link))
	  {
		  while ($s_link = mssql_fetch_array($Query_ID))
		  {
			  echo "     (<a href=\"titles.php?abstracts=1&mid=${s_link['SID']}\">";
			  if (strtotime($s_link['Date Added']) > strtotime('-1 year'))
			  {
				  echo "<b>${s_link['Pretty Title']}</b>";
			  }
			  else
			  {
				  echo $s_link['Pretty Title'];
			  }
			  echo "</a> series";
			  if ($s_link['Title Number'])
			  {
				  echo ", Part ${s_link['Title Number']}";
			  }
			  echo ")\n";
		  }
	  }

	  // Need to split Abstract into multiple lines.
	  // Mozilla doesn't seem to be able to handle more than about 1000 chars.
	  if ($row['Abstract'])
	  {
		echo '     ';
		if ($_GET['search'])
		{
		  echo html_highlight($_GET['search'], $row['Abstract']);
		}
		else
		{
		  echo $row['Abstract'];
		}
		if ($DEBUG)
			echo "{" . strlen($row['Abstract']) . "}";

		echo "\n";
	  }
	  else
	  {
		echo "     No abstract available.\n";
	  }

	  if ($row['Language'])
	  {
		echo " (In ${Languages[$row['Language']]}";
		if ($row['Subtitles'])
		{
		  echo " with";
		}
		else
		{
		  echo " <i>without</i>";
		}
		echo " English subtitles)\n";
	  }

	  if ($row['RestrictionID'])
	  {
		echo " (<i>${Restrictions[$row['RestrictionID']]}</i>)\n";
	  }

	  echo "    </li>\n    <li>Topics:\n     <ul>\n";

	  $Query_String = '
SELECT * FROM FullTopicNames
 WHERE TopicID IN (SELECT TopicID FROM [Topic - MID Junction] WHERE MID = ' . $row['MID'] . ')
 ORDER BY Topic';

	  if ($DEBUG) echo("<pre>$Query_String</pre>\n");

	  $Topic_Query = mssql_query($Query_String, $Link_ID);

	  while ($topic = mssql_fetch_array($Topic_Query))
	  {
		echo "      <li><a href=\"titles.php?topicid=${topic['TopicID']}\">${topic['Topic']}</a></li>\n";

	  }

	  echo "     </ul>\n    </li>\n";

	}
	else
	{

	  $Query_String = "
SELECT [SID - MID Junction].*, MID.[Pretty Title],
 COALESCE(MID.[Date Added], '1970-01-01') AS [Date Added]
 FROM [SID - MID Junction]
 INNER JOIN MID ON [SID - MID Junction].MID = MID.MID";

	  $Query_String .= "
 WHERE SID = '" . $row['MID'] . "'
  AND " . implode('
  AND ', $tlwhere);

	  $Query_String .= '
 ORDER BY ' . implode(', ', $tlorder);

	  if ($DEBUG) echo("<pre>$Query_String</pre>\n");

	  $Query_ID = mssql_query($Query_String, $Link_ID);
	  echo "    <li>(<i>See listings under individual titles</i>)\n";

	  $t_links = array();
	  while ($t_links[] = mssql_fetch_array($Query_ID)) {}

	  if ($t_links[0]['Title Number']) {
		echo "     <ol>\n";
	  } else {
		echo "     <ul>\n";
	  }
	  while (list($key, $t_link) = each($t_links)) {
		if (is_array($t_link)) {
		  echo '      <li value=' . $t_link['Title Number'] . '><a href="' . $PHP_SELF . '?abstracts=1&mid=' . $t_link['MID'] . '">';
		  if (strtotime($t_link['Date Added']) > strtotime('-1 year')) {
			echo '<b>';
		  }
		  echo $t_link['Pretty Title'] . "</a></li>\n";
		  if (strtotime($t_link['Date Added']) > strtotime('-1 year')) {
			echo '</b>';
		  }

		}
	  }
	  if ($t_link[0]['Title Number']) {
		echo "     </ol>\n";
	  } else {
		echo "     </ul>\n";
	  }
	}
	echo "   </ul>\n  </li>\n </ul>\n <p>\n\n";

  } else {

	if (isset($lastTitle) && strncasecmp($row['Alphabetic Title'], $lastTitle, 1)) {
	  echo " </ul>\n <ul>\n";
	}
	$lastTitle = $row['Alphabetic Title'];

	echo '  <li><a href="' . $PHP_SELF . '?abstracts=1&mid=' . $row['MID'] . '">';
	if (strtotime($row['Date Added']) > strtotime('-1 year')) {
	  echo '<b>';
	}
	if ($_GET['topicid']) {
	  echo '<i>' . str_replace(array('<i>','</i>','</i/>'), array('</i/>','<i>','</i>'), $row['Pretty Title']) . '</i>';
	} elseif ($_GET['search']) {
	  echo html_highlight($_GET['search'], $row['Pretty Title']);
	} else {
	  echo $row['Pretty Title'];
	}
	if (strtotime($row['Date Added']) > strtotime('-1 year')) {
	  echo '</b>';
	}
	echo '</a>';
	if (!strncasecmp($row['MID'], 'S', 1)) {
	  echo ' (series)';
	}
	echo "</li>\n";

  }

}

if (! $_GET['abstracts']) {
  echo " </ul>\n";
}

?>

 <br clear=all>
</blockquote>

<?php
if (! $_GET['supressform']) {
	include 'bannerbar.inc';
	include 'footer.inc.php';
}
?>

</body>
</html>
