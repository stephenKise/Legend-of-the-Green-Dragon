<?php
// translator ready
// addnews ready
// mail ready
require_once("common.php");
require_once("lib/dhms.php");
require_once("lib/http.php");

tlschema("referers");

check_su_access(SU_EDIT_CONFIG);

$expire = getsetting("expirecontent",180);
if($expire > 0) $sql = "DELETE FROM " . db_prefix("referers") . " WHERE last<'".date("Y-m-d H:i:s",strtotime("-".$expire." days"))."'";
db_query($sql);
$op = httpget('op');

if ($op=="rebuild"){
	$sql = "SELECT * FROM " . db_prefix("referers");
	$result = db_query($sql);
	$number=db_num_rows($result);
	for ($i=0;$i<$number;$i++){
		$row = db_fetch_assoc($result);
		$site = str_replace("http://","",$row['uri']);
		if (strpos($site,"/")) $site = substr($site,0,strpos($site,"/"));
		$sql = "UPDATE " . db_prefix("referers") . " SET site='".addslashes($site)."' WHERE refererid='{$row['refererid']}'";
		db_query($sql);
	}
}
require_once("lib/superusernav.php");
superusernav();
addnav("Referer Options");
addnav("",$_SERVER['REQUEST_URI']);
$sort = httpget('sort');
addnav("Refresh","referers.php?sort=".URLEncode($sort)."");
addnav("C?Sort by Count","referers.php?sort=count".($sort=="count DESC"?"":"+DESC"));
addnav("U?Sort by URL","referers.php?sort=uri".($sort=="uri"?"+DESC":""));
addnav("T?Sort by Time","referers.php?sort=last".($sort=="last DESC"?"":"+DESC"));

addnav("Rebuild Sites","referers.php?op=rebuild");

page_header("Referers");
$order = "count DESC";
if ($sort!="") $order=$sort;
$sql = "SELECT SUM(count) AS count, MAX(last) AS last,site FROM " . db_prefix("referers") . " GROUP BY site ORDER BY $order LIMIT 100";
$count = translate_inline("Count");
$last = translate_inline("Last");
$dest = translate_inline("Destination");
$none = translate_inline("`iNone`i");
$notset = translate_inline("`iNot set`i");
$skipped = translate_inline("`i%s records skipped (over a week old)`i");
rawoutput("<table border=0 cellpadding=2 cellspacing=1><tr class='trhead'><td>$count</td><td>$last</td><td>URL</td><td>$dest</td><td>IP</td></tr>");
$result = db_query($sql);
$number=db_num_rows($result);
for ($i=0;$i<$number;$i++){
	$row = db_fetch_assoc($result);

	rawoutput("<tr class='trdark'><td valign='top'>");
	output_notl("`b".$row['count']."`b");
	rawoutput("</td><td valign='top'>");
	$diffsecs = strtotime("now")-strtotime($row['last']);
	//output((int)($diffsecs/86400)."d ".(int)($diffsecs/3600%3600)."h ".(int)($diffsecs/60%60)."m ".(int)($diffsecs%60)."s");
	output_notl("`b".dhms($diffsecs)."`b");
	rawoutput("</td><td valign='top' colspan='3'>");
	output_notl("`b".($row['site']==""?$none:$row['site'])."`b");
	rawoutput("</td></tr>");

	$sql = "SELECT count,last,uri,dest,ip FROM " . db_prefix("referers") . " WHERE site='".addslashes($row['site'])."' ORDER BY {$order} LIMIT 25";
	$result1 = db_query($sql);
	$skippedcount=0;
	$skippedtotal=0;
	$number=db_num_rows($result1);
	for ($k=0;$k<$number;$k++){
		$row1=db_fetch_assoc($result1);
		$diffsecs = strtotime("now")-strtotime($row1['last']);
		if ($diffsecs<=604800){
			rawoutput("<tr class='trlight'><td>");
			output_notl($row1['count']);
			rawoutput("</td><td valign='top'>");
			//output((int)($diffsecs/86400)."d".(int)($diffsecs/3600%3600)."h".(int)($diffsecs/60%60)."m".(int)($diffsecs%60)."s");
			output_notl(dhms($diffsecs));
			rawoutput("</td><td valign='top'>");
			if ($row1['uri']>"")
				rawoutput("<a href='".HTMLEntities($row1['uri'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."' target='_blank'>".HTMLEntities(substr($row1['uri'],0,100))."</a>");
			else
				output_notl($none);
			output_notl("`n");
			rawoutput("</td><td valign='top'>");
			output_notl($row1['dest']==''?$notset:$row1['dest']);
			rawoutput("</td><td valign='top'>");
			output_notl($row1['ip']==''?$notset:$row1['ip']);
			rawoutput("</td></tr>");
		}else{
			$skippedcount++;
			$skippedtotal+=$row1['count'];
		}
	}
	if ($skippedcount>0){
		rawoutput("<tr class='trlight'><td>$skippedtotal</td><td valign='top' colspan='4'>");
		output_notl(sprintf($skipped,$skippedcount));
		rawoutput("</td></tr>");
	}
	//output("</td></tr>",true);
}
rawoutput("</table>");
page_footer();
?>