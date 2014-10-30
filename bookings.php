<html>
<head>
<link rel="stylesheet" href="http://www.washington.edu/classroom/classroom.css">
<script src="http://www.washington.edu/classroom/emc/banner.js" language="JavaScript"></script>

<?php

//$DEBUG=1;

include('emccal.php');
include('dbinfo.php');
$Database="emc";

$Link_ID = mssql_connect($dbhost, $dbuser, $dbpass);
mssql_select_db($Database);

$title = 'EMC: Bookings';
$logo_color = '#339933';
$banner = 'http://www.washington.edu/classroom/blank.gif';

$Query_String = "
SELECT BOOK.BOOK_ID, BOOK.FILM_ID_NUMBER,
  CONVERT(varchar, COALESCE(BOOK.SHIP_DATE, BOOK.BEGIN_RESERVE_DATE), 112) AS START_DATE,
  CONVERT(varchar, COALESCE(BOOK.DUE_BACK_DATE, BOOK.RETURN_DATE), 112) AS END_DATE,
  COALESCE(SHELF_LIST.SHELF_NUMBER, '<i>withdrawn</i>') AS SHELF_NUMBER,
  FID.MID, MID.[Pretty Title], MID.[Alphabetic Title], SHIP_MODES.BOOK_TYPE
 FROM BOOK
 LEFT JOIN SHELF_LIST ON BOOK.FILM_ID_NUMBER = SHELF_LIST.FID
 LEFT JOIN FID ON BOOK.FILM_ID_NUMBER = FID.FID
 LEFT JOIN MID ON FID.MID = MID.MID
 LEFT JOIN SHIP_MODES ON BOOK.SHIPPING_MODE = SHIP_MODES.SHIPPING_MODE";

$preview_query = "
SELECT BOOK.BOOK_ID, BOOK.FILM_ID_NUMBER,
  CONVERT(varchar, BOOK.BEGIN_RESERVE_DATE, 112) AS START_DATE,
  CONVERT(varchar, BOOK.BEGIN_RESERVE_DATE, 112) AS END_DATE,
  COALESCE(SHELF_LIST.SHELF_NUMBER, '<i>withdrawn</i>') AS SHELF_NUMBER,
  FID.MID, MID.[Pretty Title], MID.[Alphabetic Title], SHIP_MODES.BOOK_TYPE
 FROM BOOK
 LEFT JOIN SHELF_LIST ON BOOK.FILM_ID_NUMBER = SHELF_LIST.FID
 LEFT JOIN FID ON BOOK.FILM_ID_NUMBER = FID.FID
 LEFT JOIN MID ON FID.MID = MID.MID
 LEFT JOIN SHIP_MODES ON BOOK.SHIPPING_MODE = SHIP_MODES.SHIPPING_MODE";

$pwhere[] = "BOOK.SHIPPING_MODE IN (SELECT SHIPPING_MODE FROM SHIP_MODES WHERE BOOK_TYPE = 'p')";

$pwhere[] = 'BEGIN_RESERVE_DATE >= GETDATE()';

if (! $HTTP_GET_VARS['received']) {
  $where[] = "NOT STATUS_OF_TRANSACTION = 'r'";
} else {
  $title .= ': Received';
  $where[] = "STATUS_OF_TRANSACTION = 'r'";
}

if (! $HTTP_GET_VARS['cancelled']) {
  $where[] = "NOT STATUS_OF_TRANSACTION LIKE 'x%'";
} else {
  $title .= ': Cancelled';
  $where[] = "STATUS_OF_TRANSACTION LIKE 'x%'";
}

if (isset($HTTP_GET_VARS['fid'])) {
  $fid_query = '
SELECT FID.*, MID.[Pretty Title], SHELF_LIST.SHELF_NUMBER
 FROM FID
 LEFT OUTER JOIN MID ON FID.MID = MID.MID
 LEFT OUTER JOIN SHELF_LIST ON FID.FID = SHELF_LIST.FID
WHERE FID.FID=' . $HTTP_GET_VARS['fid'];
  if ($DEBUG) echo("<pre>$fid_query</pre>\n");
  $Query_ID = mssql_query($fid_query, $Link_ID);
  $row = mssql_fetch_array($Query_ID);
  $title .= ': ' . $row['Pretty Title'] . ' (' . $row['SHELF_NUMBER'] . ')';
  $where[] = 'FILM_ID_NUMBER = ' . $HTTP_GET_VARS['fid'];
  $pwhere[] = 'FILM_ID_NUMBER = ' . $HTTP_GET_VARS['fid'];
}

if ($HTTP_GET_VARS['mid']) {
  $mid_query = 'SELECT [Pretty Title] FROM MID WHERE MID=' . $HTTP_GET_VARS['mid'];
  if ($DEBUG) echo("<pre>$mid_query</pre>\n");
  $Query_ID = mssql_query($mid_query, $Link_ID);
  $row = mssql_fetch_array($Query_ID);
  $title .= ': ' . $row['Pretty Title'];
  $where[] = 'FID.MID = ' . $HTTP_GET_VARS['mid'];
  $pwhere[] = 'FID.MID = ' . $HTTP_GET_VARS['mid'];
}

if ($HTTP_GET_VARS['bookid']) {
  $title .= ': BOOK_ID: ' . $HTTP_GET_VARS['bookid'];
  $where[] = 'BOOK.BOOK_ID = ' . $HTTP_GET_VARS['bookid'];
  $pwhere[] = 'BOOK.BOOK_ID = ' . $HTTP_GET_VARS['bookid'];
}

if ($HTTP_GET_VARS['sort'] == 'title') {
  $order[] = '[Alphabetic Title]';
}
$order[] = 'START_DATE';
$order[] = 'END_DATE';

if (is_array($where)) {
  $Query_String .= '
 WHERE ' . implode('
  AND ', $where);
}

$Query_String .= '
UNION' . $preview_query;

if (is_array($pwhere)) {
  $Query_String .= '
 WHERE ' . implode('
  AND ', $pwhere);
}

if (is_array($order)) {
  $Query_String .= '
ORDER BY ' . implode(', ', $order);
}

if ($DEBUG) {
  echo "<pre>\n";
  echo $Query_String;
  echo "</pre>\n";
}

echo "<title>$title</title>\n";

?>
<script language="javascript"> MOdefault.src="<?php echo $banner; ?>";</script>
<style type="text/css">
.calendarHeader { font-weight: bolder;
				  color: #CC0000;
				  background-color: #FFFFCC }
.calendarToday { background-color: #FFFFFF }
.calendar { background-color: #FFFFCC }
</style>
</head>
<body>
<?php

echo "<h1>$title</h1>\n";

include('bannerbar.inc');

?>
<img src="<?php echo $banner; ?>" width=600 height=12 name="status" alt="">
<blockquote>
 <p>
 <table align=right border=0 cellspacing=0 cellpadding=0 bgcolor="<?php echo $logo_color; ?>">
   <tr><td><IMG alt="[EMC Logo]" SRC="http://www.washington.edu/classroom/emc/emctrans.gif"></td></tr>
 </table>

<?php

if ($HTTP_GET_VARS['year']) {

  $Main_Query = mssql_query($Query_String, $Link_ID);

  while ($row = mssql_fetch_array($Main_Query)) {
	for($day = $row['START_DATE']; $day <= $row['END_DATE'];
		$day = date('Ymd', strtotime('+1 day', strtotime($day)))) {
	  $status[$day] = strtoupper($row['BOOK_TYPE']);
	  $bookid[$day] = $row['BOOK_ID'];
	}
  }

  $cal = new EMCCalendar;

  if ($HTTP_GET_VARS['month']) {
	$cur_month = mktime(0,0,0, $HTTP_GET_VARS['month'], 1, $HTTP_GET_VARS['year']);
	$prev_month = strtotime('-1 month', $cur_month);
	$next_month = strtotime('+1 month', $cur_month)
?>
 <table>
  <tr>
   <td valign="top" class="calendar">
    <?php echo $cal->getMonthView(date('m', $prev_month), date('Y', $prev_month)); ?>
   </td>
   <td valign="top" class="calendar">
    <?php echo $cal->getMonthView(date('m', $cur_month), date('Y', $cur_month)); ?>
   </td>
   <td valign="top" class="calendar">
    <?php echo $cal->getMonthView(date('m', $next_month), date('Y', $next_month)); ?>
   </td>
  </tr>
 </table>
<?php
  } else {
	echo $cal->getYearView($HTTP_GET_VARS['year']);
  }
}

?>

 <br clear="all">
 <table>
  <tr><th>ID</th><th>Print</th><th>Title</th><th>Start</th><th>End</th><th>Type</th></tr>
<?php

$Main_Query = mssql_query($Query_String, $Link_ID);

while ($row = mssql_fetch_array($Main_Query)) {
  echo "  <tr>\n";
  echo '   <td>' . $row['BOOK_ID'] . "</td>\n";
  echo '   <td><a href="' . $PHP_SELF . '?fid=' . $row['FILM_ID_NUMBER']
	. '&year=' . date('Y', strtotime($row['START_DATE']))
	. '&month=' . date('m', strtotime($row['START_DATE'])) . '">'
	. $row['SHELF_NUMBER'] . "</a></td>\n";
  echo '   <td><a href="' . $PHP_SELF . '?mid=' . $row['MID'] . '">'
	. $row['Pretty Title'] . "</a></td>\n";
  echo '   <td>' . $row['START_DATE'] . "</td>\n";
  echo '   <td>' . $row['END_DATE'] . "</td>\n";
  echo '   <td>' . strtoupper($row['BOOK_TYPE']) . "</td>\n";
  echo "  </tr>\n";
}

?>
 </table>

</blockquote>

<?php include 'bannerbar.inc'; ?>
<?php include 'footer.inc.php'; ?>

</body>
</html>
