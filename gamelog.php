<?php
require_once("common.php");
global $REQUEST_URI;
$cleanURI = array_shift(explode('&c', $REQUEST_URI));
$cleanURI = array_shift(explode('?c', $cleanURI));
$offset = httpget('offset');
$category = httpget('category');
$gamelog = db_prefix('gamelog');
$accounts = db_prefix('accounts');
check_su_access(SU_EDIT_CONFIG);
tlschema('gamelog');
page_header('Game Log');
require_once("lib/superusernav.php");
superusernav();
if ($offset == '' || $offset == 0) {
    $start = '-1 week';
    $end = 'now';
}
else if ($offset >= 1) {
    $start = '-' . ($offset+1) . ' weeks';
    $end = "-$offset weeks";
}
$sql = db_query(
    "SELECT g.*, a.name
    FROM $gamelog AS g LEFT JOIN $accounts AS a ON g.who = a.acctid 
    WHERE date > '" . date('Y-m-d', strtotime($start)) . "'
    AND date < '" . date('Y-m-d H:i:s', strtotime($end)) . "'
    ORDER BY date+0 DESC"
);
$grouped = db_query(
    "SELECT category, COUNT(*) AS count FROM $gamelog
    WHERE date > '" . date('Y-m-d', strtotime($start)) . "'
    AND date < '" . date('Y-m-d H:i:s', strtotime($end)) . "'
    GROUP BY category"
);
addnav("Operations");
addnav("Refresh", $cleanURI);
addnav("Previous week", "gamelog.php?offset=" . ($offset+1));
if ($offset != '' && $offset != 0) {
    addnav("Next week", "gamelog.php?offset=" . ($offset-1));
}
if ($category > "") addnav("View all", "gamelog.php");
if (db_num_rows($grouped) > 0) {
    addnav('Entries');
}
while ($row = db_fetch_assoc($grouped)) {
    addnav(
        [
            appoencode("`<`i%s`i (%s)`<"),
            ucfirst($row['category']),
            $row['count']
        ],
        '',
        true
    );
}
$dateSeperator = '';
while ($row = db_fetch_assoc($sql)) {
    $dom = date("D, M d",strtotime($row['date']));
    if ($dateSeperator != $dom){
        output_notl("`n`b`@%s`0`b`n", $dom);
        $dateSeperator = $dom;
    }
    output_notl(
        "`2[`)%s`2] `0`Q%s `@%s`n`0",
        date('H:i:s', strtotime($row['date'])),
        $row['name'],
        $row['message']
    );
}

page_footer();

?>