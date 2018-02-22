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

foreach(array_keys($_GET) as $var) {
    if ($_GET[$var] === '') {
        $_GET[$var] = true;
    }
}   

$index     = isset($_GET['index']) ? $_GET['index'] : false;
$topicid   = isset($_GET['topicid']) ? intval($_GET['topicid']) : false;

if (isset($_GET['mid']) && preg_match('/^[sS]?[0-9]+$/', $_GET['mid']))
{
  $mid = $_GET['mid'];
  $index = false;
}

if (isset($_GET['topic']))
{
  $sql = "SELECT TopicID FROM Topics WHERE Topic = '" . mssql_escape($_GET['topic']) . "'";
  $Query_ID = mssql_query($sql, $Link_ID);
  if (is_resource($Query_ID))
    {
      $topicid = mssql_result($Query_ID, 0, 0);
    }
}

if (isset($_GET['title']))
{
  
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

if (isset($_GET['formats'])) {
        $where[] = "FID.FORMAT IN ('".implode("','", mssql_escape(array_keys($_GET['formats'])))."')";
        $swhere[] = "SID IN (SELECT SID FROM [SID - MID Junction]
  WHERE MID IN (SELECT MID FROM FID
   WHERE FORMAT IN ('".implode("','", mssql_escape(array_keys($_GET['formats'])))."')))";
}

$page_title = 'Titles';

if ($topicid) {
  $index = true;
  $Query_String = 'SELECT Topic FROM FullTopicNames
  WHERE TopicID=' . $topicid;
  if ($DEBUG) echo("<pre>$Query_String</pre>\n");
  $Query_ID = mssql_query($Query_String, $Link_ID);
  $page_title = 'Topic: ' . mssql_result($Query_ID, 0, 0);
  $where[] = 'MID.MID IN (SELECT MID FROM [Topic - MID Junction]
  WHERE TopicID=' . $topicid . ')';
  $swhere[] = 'SID IS NULL'; // don't show series
}

if (isset($_GET['withdrawn'])) {
  $page_title = $page_title . ': Withdrawn';
  $where[] = 'MID.MID NOT IN (SELECT MID FROM FID WHERE WITHDRAWN = 0)';
  $swhere[] = 'SID IN (SELECT SID FROM [SID - MID Junction]
  WHERE MID NOT IN (SELECT MID FROM FID WHERE WITHDRAWN = 0))';
  $tlwhere[] = 'MID.MID NOT IN (SELECT MID FROM FID WHERE WITHDRAWN = 0)';
} else {
  $where[] = 'MID.MID IN (SELECT MID FROM FID WHERE WITHDRAWN = 0)';
  $swhere[] = 'SID IN (SELECT SID FROM [SID - MID Junction]
  WHERE MID IN (SELECT MID FROM FID WHERE WITHDRAWN = 0))';
  $tlwhere[] = 'MID.MID IN (SELECT MID FROM FID WHERE WITHDRAWN = 0)';
}

if (isset($_GET['added'])) {
  $where[] = "MID.[Date Added] > '" . mssql_escape($_GET['added']) . "'";
  $swhere[] = "SID IN (SELECT SID FROM [SID - MID Junction]
  WHERE MID IN (SELECT MID FROM MID
   WHERE MID.[Date Added] > '" . mssql_escape($_GET['added']) . "'))";
  $tlwhere[] = "MID.[Date Added] > '" . mssql_escape($_GET['added']) . "'";
}

if (isset($_GET['new'])) {
  $page_title = 'Titles: New';
  $where[] = "MID.[Date Added] > '" . date('Y-m-d', strtotime('-1 year')) . "'";
  $swhere[] = "SID IN (SELECT SID FROM [SID - MID Junction]
  WHERE MID IN (SELECT MID FROM MID
   WHERE MID.[Date Added] > '" . date('Y-m-d', strtotime('-1 year')) . "'))";
  $tlwhere[] = "MID.[Date Added] > '" . date('Y-m-d', strtotime('-1 year')) . "'";
}

if (isset($_GET['title'])) {
  $page_title = $page_title . ': ' . mssql_escape($_GET['title']);
  $where[] = "MID.[Alphabetic Title] LIKE '" . mssql_escape($_GET['title']) . "%'";
  $swhere[] = "[Alphabetic Title] LIKE '" . mssql_escape($_GET['title']) . "%'";
}

if (isset($_GET['search'])) {
    if (isset($_GET['form_sent'])) {
	$page_title = $page_title . ': ' . $_GET['search'];
	if (! $index) {
	  $where[] = "(MID.Title LIKE '%" . mssql_escape($_GET['search']) . "%' OR MID.Abstract LIKE '%" . mssql_escape($_GET['search']) . "%')";
	} else {
	  $where[] = "MID.Title LIKE '%" . mssql_escape($_GET['search']) . "%'";
	}
	$swhere[] = "Title LIKE '%" . mssql_escape($_GET['search']) . "%'";
  } else {
	$where[] = "MID.MID IS NULL";
	$swhere[] = "SID IS NULL";
  }
}

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

$Query_String = $titles_query . $where_clause . '
UNION '. $series_query . $swhere_clause;

if (is_array($order)) {
  $Query_String .= '
ORDER BY ' . implode(', ', $order);
}

if (isset($mid)) {
  if (strncasecmp($mid, 'S', 1)) {
    $where_clause = ' WHERE MID.MID = ' . intval($mid);
    $Query_String = $titles_query . $where_clause;
  } else {
    $swhere_clause = " WHERE SID = '" . mssql_escape($mid) . "'";
    $Query_String = $series_query . $swhere_clause;
  }
}

if ($DEBUG) echo("<pre>$Query_String</pre>\n");

$tlorder[] = '[SID - MID Junction].[Title Number]';
$tlorder[] = 'MID.[Alphabetic Title]';

$slorder[] = 'SID.[Alphabetic Title]';

?>
<title><?= $page_title ?></title>
</head>
<body>
<?php

echo "<h1>$page_title</h1>";

if (isset($_GET['search'])) {
    if (!isset($_GET['suppressform'])) {
?>
 <form>
  <input type="hidden" name="form_sent" value="1">
  Search the EMC Database for:
  <br><input type="text" name="search" value="<?= $_GET['search']; ?>" size="30"><input type="submit" value="Search">
  <br><input type="radio" name="index" value="1"<?php if ($index) { echo ' checked'; } ?>>Search Titles
  <br><input type="radio" name="index" value="0"<?php if (! $index) { echo ' checked'; } ?>>Search Titles and Descriptions
  <br>Formats:
<?php

        $format_query = "
SELECT DISTINCT FORMATS.FORMAT, Description
 FROM FORMATS
  INNER JOIN FID ON FID.FORMAT = FORMATS.FORMAT AND FID.WITHDRAWN=0";
	$Query_ID = mssql_query($format_query, $Link_ID);
        while ($format = mssql_fetch_array($Query_ID)) {

?>
   <input type="checkbox" name="formats[<?= $format['FORMAT'] ?>]" value="1"<?php if (!isset($_GET['formats']) || isset($_GET['formats'][$format['FORMAT']])) { echo ' checked'; } ?>><?= $format['Description'] ?>
<?php

        }

?>
   <br><input type="checkbox" name="suppressform" value="1"<?php if (isset($_GET['suppressform'])) { echo ' checked'; } ?>>Plain results (for printing)
 </form>
<?php

  }
}

if (!isset($mid))
{
  $titles_count = "SELECT COUNT(*) FROM MID LEFT OUTER JOIN FID ON MID.MID = FID.MID AND FID.WITHDRAWN = 0" . $where_clause;
  $Query_ID = mssql_query($titles_count, $Link_ID);
  $num_titles = mssql_result($Query_ID, 0, 0);

  $series_count = "SELECT COUNT(*) FROM SID " . $swhere_clause;
  $Query_ID = mssql_query($series_count, $Link_ID);
  $num_series = mssql_result($Query_ID, 0, 0);

  echo "<p><small>$num_titles titles and $num_series series found.</small></p>";
}

$Main_Query = mssql_query($Query_String, $Link_ID);

if (is_resource($Main_Query))
{

if (! $index) {

?>
<table id="titles" class="row-border">
 <thead>
  <tr><th>MID</th><th>Alpha Title</th><th>Title</th><th>Year</th><th>Color/BW</th><th>Running Time</th><th>Formats</th><th>Abstract</th><th>Topics</th></tr>
 </thead>
<tbody>
<?php

} else {

?>
<table id="titles" class="row-border">
 <thead>
  <tr><th>MID</th><th>Alpha Title</th><th>Title</th></tr>
 </thead>
<tbody>
<?php

}

while ($row = mssql_fetch_array($Main_Query)) {
    if (! $index) {

        echo '<tr><td>' . $row['MID'] . '</td><td>' . $row['Alphabetic Title'] . '</td><td>';

	echo '<a name="' . $row['MID'] . '" href="../title/' . $row['MID'] . '">';
	if (isset($_GET['search'])) {
	  echo html_highlight($_GET['search'], strtoupper(strtr($row['Pretty Title'], $special_lower, $special_upper)));
	} else {
	  echo strtoupper(strtr($row['Pretty Title'], $special_lower, $special_upper));
	}
	echo '</a></td>';

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

	  echo '<td>' . $row['Year'] . '</td><td>';
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
	  echo '</td><td>';
	  if ($row['Running Time']) {
		echo $row['Running Time'] . ' min';
	  }
	  echo '</td><td>';
          $formats = explode('/', rtrim($row['Formats'], '/'));
          foreach ($formats as $i => $format) {
            $formats[$i] = '<a href="../prints/?mid=' . $row['MID'] . '&format=' . $format . '">' . $format . '</a>';
          }
          echo implode('/', $formats) . '</td><td>';

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
		if (isset($_GET['search'])) {
		  echo html_highlight($_GET['search'], $row['Abstract']);
		} else {
		  echo $row['Abstract'];
		}
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

	  if ($row['WITHDRAWN']) {
		echo ' (<i>withdrawn</i>)';
	  }

	  echo "    </li>\n";

	  if ($row['lib_url']) {
		echo '    <li><a href="' . $row['lib_url'] . '">Access this title at the UW Libraries Media Center</a></li>'."\n";
	  }

          echo '</td><td><ul>';

          $Query_String = '
SELECT * FROM FullTopicNames
 WHERE TopicID IN (SELECT TopicID FROM [Topic - MID Junction]
             WHERE MID = ' . $row['MID'] . ')';
          if ($DEBUG) echo("<pre>$Query_String</pre>\n");

          $Topics_Query = mssql_query($Query_String, $Link_ID);
          while ($topic = mssql_fetch_array($Topics_Query)) {
              echo '<li><a href="../topic/' . $topic['TopicID'] . '">' . $topic['Topic'] . '</a></li>';
          }
          echo '</ul></td>';


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
	  echo "<td></td><td></td><td></td><td></td><td>(<i>See listings under individual titles</i>)\n";

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
                      echo $t_link['Pretty Title'] . "</a></li>\n";
		}
	  }
	  if ($t_link[0]['Title Number']) {
		echo "     </ol>\n";
	  } else {
		echo "     </ul>\n";
	  }
          echo '</td><td></td>';
	}

        echo '</tr>';

  } else {

        echo '<tr><td>' . $row['MID'] . '</td><td>' . $row['Alphabetic Title'] . '</td><td>';

	$lastTitle = $row['Alphabetic Title'];

        echo '  <a href="../title/' . $row['MID'] . '">';
	if ($topicid) {
	  echo '<i>' . str_replace(array('<i>','</i>','</i/>'), array('</i/>','<i>','</i>'), $row['Pretty Title']) . '</i>';
	} elseif (isset($_GET['search'])) {
	  echo html_highlight($_GET['search'], $row['Pretty Title']);
	} else {
	  echo $row['Pretty Title'];
	}
	echo '</a>';
	if (!strncasecmp($row['MID'], 'S', 1)) {
	  echo ' (series)';
	}
        echo '</td></tr>';

  }
}

?>
</tbody></table>
<?php

}

if (!isset($_GET['suppressform'])) {

?>
<script>
$(document).ready(function() {
  $('#titles').DataTable({
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
<?php

}

?>
</body>
</html>
