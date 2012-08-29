<?php

$MOemcT = "The Educational Media Collection, the UW's primary resource for films and videos";
$MOnewtitlesT = "An alphabetical listing of the Educational Media Collection's most recent acquisitions";
$MOtitlesT = "A complete listing of the EMC with links to individual abstracts";
$MOabstractsT = "An index to the descriptions of the complete Educational Media Collection";
$MOtopicalsT = "An index by subject of the titles in the Educational Media Collection";
$MOwithdrawnT = "A listing of titles no longer available from the Educational Media Collection";
$MOcssT = "Classroom Support Services, the parent department of the Educational Media Collection";
$MOsearchT = "Search the titles and abstracts of the EMC film database for keywords";

eval('$bannertext = $MO' . $banner . 'T;');

?>

<SCRIPT LANGUAGE="Javascript">
<!-- Hide from old browsers
var doMO = 0;

if (document.images) {
    var n = navigator.appName;
    var v = parseInt (navigator.appVersion);
    var browsok = n == "Netscape" && v >= 4;
    if (browsok) {
	if (v == 3 && navigator.userAgent.indexOf ("Macintosh") >= 0)
	    browsok = 0;
    } else
	browsok = n == "Microsoft Internet Explorer" && v >= 4;
    if (browsok) {
	doMO = 1;
	arrow = new Image;
	arrow.src = "http://www.washington.edu/classroom/navarrow.gif";
	noarrow = new Image;
	noarrow.src = "http://www.washington.edu/classroom/noarrow.gif";
	MOemcP = 0; MOnewtitlesP = 0; MOtitlesP = 0; MOabstractsP = 0;
	MOtopicalsP = 0; MOwithdrawnP = 0; MOcssP = 0; MOsearchP = 0;

	MOemcT = "<?php echo $MOemcT; ?>";
	MOnewtitlesT = "<?php echo $MOnewtitlesT; ?>";
	MOtitlesT = "<?php echo $MOtitlesT; ?>";
	MOabstractsT = "<?php echo $MOabstractsT; ?>";
	MOtopicalsT = "<?php echo $MOtopicalsT; ?>";
	MOwithdrawnT = "<?php echo $MOwithdrawnT; ?>";
	MOcssT = "<?php echo $MOcssT; ?>";
	MOsearchT = "<?php echo $MOsearchT; ?>";

	MOdefault = new Image;
	MOdefault.src = "http://www.css.washington.edu/banner.php?text=" + MO<?php echo $banner; ?>T;

    }
}

function mouseIn (imagename, doarrow) {
    if (doMO) {
	if (! eval ("MO" + imagename + "P"))
	    eval ("MO" + imagename + " = new Image; MO" + imagename +
		'.src = "http://www.css.washington.edu/banner.php?text=' +
		eval("MO" + imagename + "T") + '"; MO' + imagename + "P = 1");
	document.images["status"].src = eval ("MO" + imagename + ".src");
	if (doarrow)
	    document.images[imagename].src = arrow.src;
    }
}

function mouseOut (imagename, doarrow) {
    if (doMO) {
	document.images["status"].src = MOdefault.src;
	if (doarrow)
	    document.images[imagename].src = noarrow.src;
    }
}

// Stop hiding from old browsers -->
</SCRIPT>
