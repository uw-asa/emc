<?php

/*
 * This is a fairly simplistic script that generates a pdf of a (blank) room scheduling matrix, with the dates pre-filled.
 * Weekends and holidays (as read from the EMC database) are removed.
 */

include('datefuncs.php');

$start_date = $end_date = get_date();

if ($_POST['start']) {
  $start_date = strtodate($_POST['start']);
}
if ($_POST['end']) {
  $end_date = strtodate($_POST['end']);
}   

if (!$start_date) {
  $errors[] = "Start date invalid";
}

if (!$end_date) {
  $errors[] = "End date invalid";
}

if ($start_date > $end_date) {
  $errors[] = "Start date is after end date";
}

if (($end_date - $start_date) > (366 * 24 * 60 * 60)) {
  $errors[] = "Range is greater than 1 year";
}

if (empty($_POST) || !empty($errors))
{

?>
<html>
<head><title>EMC Schedule PDF generator</title></head>
<body>
<form method="post">
<label>Start Date:<input type="text" name="start" value="<?php echo date("Y-m-d", $start_date); ?>" /></label>
<label>End Date:<input type="text" name="end" value="<?php echo date("Y-m-d", $end_date); ?>" /></label>
<input type="submit" value="Submit" />
</form>
<?php if ($errors): ?>
<ul>
<?php  foreach ($errors as $error): ?>
<?php   echo "<li>$error</li>\n"; ?>
<?php  endforeach; ?>
</ul>
<?php endif; ?>
</body>
</html>
<?php

        exit;

}

require('/usr/share/fpdf/fpdf.php');

class PDF extends FPDF
{

//Colored table
function CalendarTable($caption, $header, $data, $date)
{
    //Colors, line width and bold font
    $this->SetFillColor(0, 0, 0);
    $this->SetTextColor(255);
    $this->SetDrawColor(0, 0, 0);
    $this->SetLineWidth(1);
    $this->SetFont('Times', '', 14);
    $this->SetFont('', 'B');

    $w = array(54, 108, 108, 144, 54, 108, 108, 144, 54);
    $total_width = array_sum($w);
    foreach ($w as $col => $width) $w[$col] = 936/$total_width * $width;

    //Caption
    $this->Cell(array_sum($w), 18, $caption, 1, 0, 'C', 1);
    $this->Ln();

    //Color and font restoration
    $this->SetTextColor(0);

    //Header
    for($i=0;$i<count($header);$i++)
        $this->Cell($w[$i], 18, $header[$i], 1, 0, 'C', 0);
    $this->Ln();

    //Data
    foreach($data as $row)
    {
        for ($col = 0; $col <= count($w); $col++)
                $this->Cell($w[$col], 27, $row[$col], 1, 0, 'C', 0);
        $this->Ln();
    }

    //Date
    $this->SetFont('', '', 24);
    $this->Cell(array_sum($w), 27, $date, 0, 0, 'R');

}
}


$pdf = new PDF('L', 'pt', 'Legal');
$pdf->SetMargins(36, 36, 36);
$pdf->SetAutoPageBreak(false);

$caption = 'EDUCATIONAL MEDIA COLLECTION - PREVIEW SCHEDULE';

//Column titles
$header=array('TIME', 'CARREL A', 'CARREL B', 'PREVIEW ROOM 23B', 'TIME', 'CINESCAN', 'PANIC', 'CONF. ROOM (19)', 'TIME');

$start_time = strtotime('8:00 am');
$end_time = strtotime('5:00 pm');

for ($time = $start_time; $time < $end_time; $time += 30 * 60)
{
        $times[] = $time;
}

//$times = array('8:00a', '8:30a', '9:00a', '9:30a', '10:00a', '10:30a', '11:00a', '11:30a', '12:00p', '12:30p', '1:00p', '1:30p', '2:00p', '2:30p', '3:00p', '3:30p', '4:00p', '4:30p');
foreach ($times as $row => $time)
        $data[$row] = array(timestr($time), '', '', '', timestr($time), '', '', '', timestr($time));

include('dbinfo.php');
include('../misc.php');
$Database="emc";

$Link_ID = mssql_connect($dbhost, $dbuser, $dbpass);
mssql_select_db($Database)
  or die("Sorry, the system is currently down. Please try again later.");

$sql = "SELECT * FROM HOLIDAYS WHERE DATE_OF_HOLIDAY >= '" . datestr($start_date) . "'";

$result = mssql_query($sql);

while ($row = mssql_fetch_array($result))
        $holidays[] = strtodate($row['DATE_OF_HOLIDAY']);

for ($date = $start_date; $date <= $end_date; $date = date_add($date, 0, 1, 0))
{
        if (!in_array($date, $holidays) && !is_weekend($date))
                $dates[] = $date;
}

foreach($dates as $date)
{
        $pdf->AddPage();
        $pdf->CalendarTable($caption, $header, $data, date('l, j F, Y', $date));
}
$pdf->Output();

?>
