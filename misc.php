<?php

ini_set('memory_limit', '32M');

date_default_timezone_set('America/Los_Angeles');

$DEBUG = isset($_GET['debug']) ? $_GET['debug'] : false;

$currency = "$%01.2f";

$special_lower = 'áé';
$special_upper = 'ÁÉ';

function html_highlight( $needle, $haystack )
{
    $parts = explode( strtolower($needle), strtolower($haystack) );

    $pos = 0;

    foreach( $parts as $key=>$part ){
        $parts[ $key ] = substr($haystack, $pos, strlen($part));
        $pos += strlen($part);

        $parts[ $key ] .= '<b style="color:white;background-color:#333399">'
		  . substr($haystack, $pos, strlen($needle))
		  . '</b>';
        $pos += strlen($needle);
        }

    return( join( '', $parts ) );
}

function mssql_escape($data)
{
	$data = str_replace("'", "''", $data);
	return $data;
} 

function dprint($text)
{
  global $debug_output;
  $debug_output .= "$text\n";
}

if (!function_exists('gzdecode')) {
    function gzdecode($data)
    {
        return gzinflate(substr($data,10,-8));
    }
}

?>
