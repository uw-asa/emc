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

if (preg_match('/^[sS]?[0-9]+$/', $_GET['mid']))
{
  $mid = $_GET['mid'];
}
else
{
  header('Location: http://www.cte.uw/emc/title/' . $_GET['mid'], true, 301);
  exit;
}

if (strpos($_SERVER['REQUEST_URI'], '.php'))
{
  header('Location: http://www.cte.uw.edu/emc/title/' . $mid, true, 301);
  exit;
}

$titles_query = "
SELECT DISTINCT CONVERT(varchar, MID.MID) AS MID, MID.Title, MID.[Alphabetic Title],
 MID.[Pretty Title], COALESCE(MID.[Date Added], '1970-01-01') AS [Date Added],
 MID.Year, MID.Color, MID.[Running Time],
 MID.[Rental Rate], MID.Abstract, MID.Language, MID.Subtitles,
 MID.RestrictionID, [MIDFormats-Combined].Formats,
 COALESCE(FID.WITHDRAWN, 1) AS WITHDRAWN,
 lib_url
 FROM MID
  LEFT OUTER JOIN [MIDFormats-Combined] ON MID.MID = [MIDFormats-Combined].MID
  LEFT OUTER JOIN FID ON MID.MID = FID.MID AND FID.WITHDRAWN = 0";

$series_query = "
SELECT SID AS MID, Title, [Alphabetic Title],
 [Pretty Title], COALESCE([Date Added], '1970-01-01') AS [Date Added],
 NULL AS Year, NULL AS Color, NULL AS [Running Time],
 NULL AS [Rental Rate], NULL as Abstract, NULL AS Language, NULL AS Subtitles,
 NULL AS RestrictionID, NULL AS Formats,
 NULL AS WITHDRAWN,
 NULL AS lib_url
 FROM SID";

  $page_title = 'EMC: Abstract';
  $logo_color = '#993333';
  $banner = 'abstracts';

  $Query_String = 'SELECT * FROM Restrictions';
  if ($DEBUG) echo("<pre>$Query_String</pre>\n");
  $Query_ID = mssql_query($Query_String, $Link_ID);
  while ($row = mssql_fetch_array($Query_ID)) {
	$Restrictions[$row['RestrictionID']] = $row['Restriction'];
  }

  $Query_String = 'SELECT * FROM Languages';
  if ($DEBUG) echo("<pre>$Query_String</pre>\n");
  $Query_ID = mssql_query($Query_String, $Link_ID);
  while ($row = mssql_fetch_array($Query_ID)) {
	$Languages[$row['Language ID']] = $row['Language'];
  }



$where[] = 'MID.MID IN (SELECT MID FROM FID WHERE WITHDRAWN = 0)';
$swhere[] = 'SID IN (SELECT SID FROM [SID - MID Junction]
WHERE MID IN (SELECT MID FROM FID WHERE WITHDRAWN = 0))';
$tlwhere[] = 'MID.MID IN (SELECT MID FROM FID WHERE WITHDRAWN = 0)';

$order[] = '[Alphabetic Title]';

if (is_array($where)) {
  $where_clause = '
 WHERE ' . implode('
  AND ', $where);
}

if (is_array($swhere)) {
  $swhere_clause = '
 WHERE ' . implode('
  AND ', $swhere);
}

if (is_array($order)) {
  $Query_String .= '
ORDER BY ' . implode(', ', $order);
}

if (strncasecmp($mid, 'S', 1)) {
    $where_clause = ' WHERE MID.MID = ' . intval($mid);
    $Query_String = $titles_query . $where_clause;
} else {
    $swhere_clause = " WHERE SID = '" . mssql_escape($mid) . "'";
    $Query_String = $series_query . $swhere_clause;
}

if ($DEBUG) echo("<pre>$Query_String</pre>\n");

$tlorder[] = '[SID - MID Junction].[Title Number]';
$tlorder[] = 'MID.[Alphabetic Title]';

$slorder[] = 'SID.[Alphabetic Title]';

$Main_Query = mssql_query($Query_String, $Link_ID);

$row = mssql_fetch_array($Main_Query);

f_set_parm('title', strip_tags($row['Pretty Title']));

	echo "   <ul>\n";

	if (strncasecmp($row['MID'], 'S', 1)) {

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

	  echo '    <li>' . $row['Year'] . ' ----- ';
	  if (!strncasecmp($row['Color'], 'y', 1))
	  {
		echo 'color';
	  }
	  elseif (!strncasecmp($row['Color'], 'n', 1))
	  {
		echo 'b & w';
	  }
	  else
	  {
		echo $row['Color'];
	  }
	  echo ' ----- ';
	  if ($row['Running Time']) {
		echo $row['Running Time'] . ' min';
	  }
	  if ($row['WITHDRAWN']) {
		echo ' ----- ';
		echo '<i>withdrawn</i>';
	  }
          /*
          else {
		echo ' ----- ';
		if ($row['Rental Rate']) {
		  echo sprintf($currency, $row['Rental Rate']);
		}
	  }
          */
	  echo ' ----- ';
          $formats = explode('/', $row['Formats']);
          foreach ($formats as $i => $format) {
            $formats[$i] = '<a href="../prints/?mid=' . $row['MID'] . '&format=' . $format . '">' . $format . '</a>';
          }
          echo implode('/', $formats) . "</li>\n";

	  echo "    <li>\n";

	  if (is_resource($Query_ID))
	  {
		  while ($s_link = mssql_fetch_array($Query_ID)) {
			  echo '     (<a href="../title/' . $s_link['SID'] . '">';
			  if (strtotime($s_link['Date Added']) > strtotime('-1 year')) {
				  echo '<b>';
			  }
			  echo $s_link['Pretty Title'];
			  if (strtotime($s_link['Date Added']) > strtotime('-1 year')) {
				  echo '</b>';
			  }
			  echo '</a> series';
			  if ($s_link['Title Number']) {
				  echo ', Part ' . $s_link['Title Number'];
			  }
			  echo ")\n";
		  }
	  }

	  // Need to split Abstract into multiple lines.
	  // Mozilla doesn't seem to be able to handle more than about 1000 chars.
	  if ($row['Abstract']) {
		echo '     ';
		echo $row['Abstract'];
		if ($DEBUG)
			echo "{" . strlen($row['Abstract']) . "}";

		echo "\n";
	  } else {
		echo "     No abstract available.\n";
	  }

	  if ($row['Language']) {
		echo ' (In ' . $Languages[$row['Language']];
		if ($row['Subtitles']) {
		  echo ' with';
		} else {
		  echo ' <i>without</i>';
		}
		echo " English subtitles)\n";
	  }

	  if ($row['RestrictionID']) {
		echo ' (<i>' . $Restrictions[$row['RestrictionID']] . "</i>)\n";
	  }

	  echo "    </li>\n";

          $Query_String = '
SELECT * FROM FullTopicNames
 WHERE TopicID IN (SELECT TopicID FROM [Topic - MID Junction]
             WHERE MID = ' . $mid . ')';
          if ($DEBUG) echo("<pre>$Query_String</pre>\n");

          $Topics_Query = mssql_query($Query_String, $Link_ID);

          while ($topic = mssql_fetch_array($Topics_Query)) {
                $topiclinks[] = '<a href="../topic/' . $topic['TopicID'] . '">' . $topic['Topic'] . '</a>';
          }

          echo '  <li>Topics: (' . implode(', ', $topiclinks) . ")\n";

	  if ($row['lib_url']) {
		echo '    <li><a href="' . $row['lib_url'] . '">Access this title at the UW Libraries Media Center</a></li>'."\n";
	  }

	} else {

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
                      echo '      <li value=' . $t_link['Title Number'] . '><a href="../title/' . $t_link['MID'] . '">';
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

?>
    </li>
   </ul>

<?php

f_lucid_render(ob_get_clean());
