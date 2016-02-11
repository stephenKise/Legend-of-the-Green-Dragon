<?php
$sql = "DELETE FROM " . db_prefix("bans") . " WHERE ipfilter = '".httpget("ipfilter"). "' AND uniqueid = '".httpget("uniqueid")."'";
db_query($sql);
redirect("user.php?op=removeban");
?>