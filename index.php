<?php

header("Location: http://guides.lib.washington.edu/emc");
exit;





require_once('../lucid_f.php');

f_set_parm('name', 'titles');
//f_set_parm('title', 'STF Equipment Request');
f_set_parm('parent', db_getPageID('emc'));
f_set_parm('template', db_getTemplateID('uwit'));
f_set_parm('style', db_getStyleID('blog'));
f_set_parm('markup', 'none');

ob_start();

//$DEBUG=1;

$banner = "emc";
//include("bannerscript.inc.php");

include('dbinfo.php');
$Database="emc";

$Link_ID = mssql_connect($dbhost, $dbuser, $dbpass);
mssql_select_db($Database)
  or die("Sorry, the system is currently down. Please try again later.");

$logo_color = '#339933';
$title = 'Educational Media Collection';

f_set_parm('title', $title);

echo "<h1>$title</h1>\n";

$Query_String = 'SELECT CONVERT(varchar, MAX(DATE_OF_CHANGE), 106) AS updated FROM MID';
$Query_ID = mssql_query($Query_String, $Link_ID);
$row = mssql_fetch_array($Query_ID);
$updated = $row['updated'];

echo "Updated: $updated\n";

echo "<p>\n";

readfile('http://localhost/content/?page=emc&template=none');

f_lucid_render(ob_get_clean());
