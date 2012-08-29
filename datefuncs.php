<?php

function mkdate($m, $d, $y)
{
	return mktime(0, 0, 0, $m, $d, $y);
}

function date_add($date, $m, $d, $y)
{
	return mkdate(date("m", $date) + $m,
				  date("d", $date) + $d,
				  date("Y", $date) + $y);
}

/*
 * strips time info from date
 */
function fix_date($date)
{
	return mkdate(date("m", $date), date("d", $date), date("Y", $date));
}

/*
 * returns current date
 */
function get_date($timestamp = NULL)
{
        if ($timestamp)
                return fix_date($timestamp);
	return fix_date(time());
}

function strtodate($date_str)
{
	return fix_date(strtotime($date_str));
}

function datestr($date)
{
	return date("M j, Y", $date);
}

function strtodatestr($date_str)
{
	return datestr(strtodate($date_str));
}

function is_weekend($date)
{
        $weekday = date('w', $date);
        if ($weekday == 0 || $weekday == 6)
                return true;
        return false;
}

function timestr($time)
{
	return date("g:ia", $time);
}

?>