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

$abstracts = isset($_GET['abstracts']) ? intval($_GET['abstracts']) : false;
$index     = isset($_GET['index']);
$new       = isset($_GET['new']);
$withdrawn = isset($_GET['withdrawn']);
$topicid   = isset($_GET['topicid']) ? intval($_GET['topicid']) : false;

if (preg_match('/^[sS]?[0-9]+$/', $_GET['mid']))
{
  $mid = $_GET['mid'];
  $abstracts = true;
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


if ($new || $withdrawn)
{
  $abstracts = false;
  $index = false;
}

if (strpos($_SERVER['REQUEST_URI'], '.php') === false)
{
  $rest = true;
}
else
{
  $flags['abstracts'] = $abstracts;
  $flags['index'] = $index;
  $flags['new'] = $new;
  $flags['withdrawn'] = $withdrawn;

  $query = implode('&', array_keys(array_intersect($flags, array(true))));

  if ($query)
    {
      $query = '?' . $query;
    }

  if ($topicid)
    {
      header('Location: http://www.cte.uw.edu/emc/topic/' . $topicid . $query, true, 301);
    }
  elseif ($mid)
    {
      header('Location: http://www.cte.uw.edu/emc/title/' . $mid . $query, true, 301);
    }
  else
    {
      header('Location: http://www.cte.uw.edu/emc/titles/' . $_GET['title'] . $query, true, 301);
    }
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

if ($abstracts) {
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

}

if ($index) {
  f_set_parm('title', 'Titles: Index');
}


if (isset($_GET['formats'])) {
        $where[] = "FID.FORMAT IN ('".implode("','", mssql_escape(array_keys($_GET['formats'])))."')";
        $swhere[] = "SID IN (SELECT SID FROM [SID - MID Junction]
  WHERE MID IN (SELECT MID FROM FID
   WHERE FORMAT IN ('".implode("','", mssql_escape(array_keys($_GET['formats'])))."')))";
}

if ($topicid) {
  $index = true;
  $Query_String = 'SELECT Topic FROM FullTopicNames
  WHERE TopicID=' . $topicid;
  if ($DEBUG) echo("<pre>$Query_String</pre>\n");
  $Query_ID = mssql_query($Query_String, $Link_ID);
  f_set_parm('title', 'Topic: ' . mssql_result($Query_ID, 0, 0));
  $where[] = 'MID.MID IN (SELECT MID FROM [Topic - MID Junction]
  WHERE TopicID=' . $topicid . ')';
  $swhere[] = 'SID IS NULL'; // don't show series
}

if ($withdrawn) {
  f_set_parm('title', $f_pageData['title'] . ': Withdrawn');
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

if ($new) {
  f_set_parm('title', 'Titles: New');
  $where[] = "MID.[Date Added] > '" . date('Y-m-d', strtotime('-1 year')) . "'";
  $swhere[] = "SID IN (SELECT SID FROM [SID - MID Junction]
  WHERE MID IN (SELECT MID FROM MID
   WHERE MID.[Date Added] > '" . date('Y-m-d', strtotime('-1 year')) . "'))";
  $tlwhere[] = "MID.[Date Added] > '" . date('Y-m-d', strtotime('-1 year')) . "'";
}

if (isset($_GET['title'])) {
  f_set_parm('title', $f_pageData['title'] . ': ' . mssql_escape($_GET['title']));
  $where[] = "MID.[Alphabetic Title] LIKE '" . mssql_escape($_GET['title']) . "%'";
  $swhere[] = "[Alphabetic Title] LIKE '" . mssql_escape($_GET['title']) . "%'";
}

if (isset($_GET['search'])) {
  f_set_parm('title', $f_pageData['title'] . ': Search');

  if ($_GET['form_sent']) {
	f_set_parm('title', $f_pageData['title'] . ': ' . $_GET['search']);
	if ($abstracts) {
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

if (isset($_GET['search'])) {
  if (! $_GET['supressform']) {
?>
 <form>
  <input type="hidden" name="form_sent" value="1">
  Search the EMC Database for:
  <br><input type="text" name="search" value="<?php echo $_GET['search']; ?>" size=30><input type="submit" value="Search">
  <br><input type="radio" name="abstracts" value="0"<?php if (! $abstracts) { echo ' checked'; } ?>>Search Titles
  <br><input type="radio" name="abstracts" value="1"<?php if ($abstracts) { echo ' checked'; } ?>>Search Titles and Descriptions
  <br>Formats:
<?php

        $format_query = "
SELECT DISTINCT FORMATS.FORMAT, Description
 FROM FORMATS
  INNER JOIN FID ON FID.FORMAT = FORMATS.FORMAT AND FID.WITHDRAWN=0";
	$Query_ID = mssql_query($format_query, $Link_ID);
        while ($format = mssql_fetch_array($Query_ID)) {

?>
   <input type="checkbox" name="formats[<?php echo $format['FORMAT'] ?>]" value="1"<?php if (!is_array($_GET['formats']) || $_GET['formats'][$format['FORMAT']]) { echo ' checked'; } ?>><?php echo $format['Description'] ?>
<?php

        }

?>
  <br><input type="checkbox" name="supressform" value="1"<?php if ($_GET['supressform']) { echo ' checked'; } ?>>Plain results (for printing)
 </form>
<?php

  }
  else {
    f_set_parm('template', db_getTemplateID('none'));
  }
} elseif ($new) {
	$Updated_Query = 'SELECT CONVERT(varchar, MAX([Date Added]), 106) AS updated FROM MID WHERE MID IN (SELECT MID FROM FID WHERE WITHDRAWN = 0)';
	$Query_ID = mssql_query($Updated_Query, $Link_ID);
	$row = mssql_fetch_array($Query_ID);
	$updated = $row['updated'];
	echo "Updated: $updated\n";
} elseif ($withdrawn) {
	$Updated_Query = 'SELECT CONVERT(varchar, MAX(DATE_WITHDRAWN), 106) AS updated FROM FID WHERE MID NOT IN (SELECT MID FROM FID WHERE WITHDRAWN = 0)';
	$Query_ID = mssql_query($Updated_Query, $Link_ID);
	$row = mssql_fetch_array($Query_ID);
	$updated = $row['updated'];
	echo "Updated: $updated\n";
} elseif ($topicid) {
	$Updated_Query = 'SELECT CONVERT(varchar, MAX([Date Added]), 106) AS updated FROM MID WHERE MID IN (SELECT MID FROM [Topic - MID Junction] WHERE TopicID=' . $topicid . ') AND MID IN (SELECT MID FROM FID WHERE WITHDRAWN = 0)';
	$Query_ID = mssql_query($Updated_Query, $Link_ID);
	$row = mssql_fetch_array($Query_ID);
	$updated = $row['updated'];
	echo "Updated: $updated\n";
} elseif ($abstracts) {
	if ($_GET['title']) {
		$Updated_Query = "SELECT CONVERT(varchar, MAX([Date Added]), 106) AS updated FROM MID WHERE MID IN (SELECT MID FROM FID WHERE WITHDRAWN = 0) AND MID.[Alphabetic Title] LIKE '" . mssql_escape($_GET['title']) . "%'";
		$Query_ID = mssql_query($Updated_Query, $Link_ID);
		$row = mssql_fetch_array($Query_ID);
		$updated = $row['updated'];
		echo "Updated: $updated\n";
	}		

	
} else {
	$Updated_Query = 'SELECT CONVERT(varchar, MAX(DATE_OF_CHANGE), 106) AS updated FROM MID WHERE MID IN (SELECT MID FROM FID WHERE WITHDRAWN = 0)';
	$Query_ID = mssql_query($Updated_Query, $Link_ID);
	$row = mssql_fetch_array($Query_ID);
	$updated = $row['updated'];
	echo "Updated: $updated\n";
}
echo $f_pageData['content'];

if (! $mid)
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

if (! $abstracts) {
  echo " <ul>\n";
}

if (is_resource($Main_Query))
{
while ($row = mssql_fetch_array($Main_Query)) {

  if (!$index && !$abstracts) {
	if (strncasecmp($row['Alphabetic Title'], $lastTitle, 1)) {
	  $idx = strtoupper(substr($row['Alphabetic Title'], 0, 1));
          if ($rest)
            {
              echo '<a href="' . $idx . '">' . $idx . "</a>, \n";
            }
          else
            {
              echo '<a href="' . $PHP_SELF . '?';
              if ($abstracts) {
                echo 'abstracts=1&';
              }
              echo 'title=' . $idx . '">' . $idx . "</a>, \n";
            }
        }
	$lastTitle = $row['Alphabetic Title'];
	
  } elseif ($abstracts) {
    if ($rest)
      {
	echo " <ul>\n"
	  . '  <li><a name="' . $row['MID'] . '" href="../topics/' . $row['MID'] . '">';
      }
    else
      {
	echo " <ul>\n"
	  . '  <li><a name="' . $row['MID'] . '" href="topics.php?mid='
	  . $row['MID'] . '">';
      }
	if (strtotime($row['Date Added']) > strtotime('-1 year')) {
	  echo '<b>';
	}
	if ($_GET['search']) {
	  echo html_highlight($_GET['search'], strtoupper(strtr($row['Pretty Title'], $special_lower, $special_upper)));
	} else {
	  echo strtoupper(strtr($row['Pretty Title'], $special_lower, $special_upper));
	}
	if (strtotime($row['Date Added']) > strtotime('-1 year')) {
	  echo '</b>';
	}
	echo "</a>\n   <ul>\n";

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
                    if ($rest)
                      {
			  echo '     (<a href="../title/' . $s_link['SID'] . '">';
                      }
                    else
                      {
			  echo '     (<a href="titles.php?abstracts=1&mid=' . $s_link['SID'] . '">';
                      }
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
		if ($_GET['search']) {
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

	  echo "    </li>\n";

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
                  if ($rest)
                    {
                      echo '      <li value=' . $t_link['Title Number'] . '><a href="../title/' . $t_link['MID'] . '">';
                    }
                  else
                    {
                      echo '      <li value=' . $t_link['Title Number'] . '><a href="' . $PHP_SELF . '?abstracts=1&mid=' . $t_link['MID'] . '">';
                    }
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
	echo "    </li>\n   </ul>\n  </ul>\n <p>\n\n";

  } else {

	if (isset($lastTitle) && strncasecmp($row['Alphabetic Title'], $lastTitle, 1)) {
	  echo " </ul>\n <ul>\n";
	}
	$lastTitle = $row['Alphabetic Title'];

        if ($rest)
          {
            echo '  <li><a href="../title/' . $row['MID'] . '">';
          }
        else
          {
            echo '  <li><a href="' . $PHP_SELF . '?abstracts=1&mid=' . $row['MID'] . '">';
          }
	if (strtotime($row['Date Added']) > strtotime('-1 year')) {
	  echo '<b>';
	}
	if ($topicid) {
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
}

if (! $abstracts) {
  echo " </ul>\n";
}

f_lucid_render(ob_get_clean());
