<?php
//addnews ready
// mail ready
// translator ready

function specialtydarkarts_getmoduleinfo(){
	$info = array(
		"name" => "Specialty - Dark Arts",
		"author" => "Eric Stevens",
		"version" => "1.1",
		"download" => "core_module",
		"category" => "Specialties",
		"prefs" => array(
			"Specialty - Dark Arts User Prefs,title",
			"skill"=>"Skill points in Dark Arts,int|0",
			"uses"=>"Uses of Dark Arts allowed,int|0",
		),
	);
	return $info;
}

function specialtydarkarts_install(){
	$sql = "DESCRIBE " . db_prefix("accounts");
	$result = db_query($sql);
	$specialty="DA";
	while($row = db_fetch_assoc($result)) {
		// Convert the user over
		if ($row['Field'] == "darkarts") {
			debug("Migrating darkarts field");
			$sql = "INSERT INTO " . db_prefix("module_userprefs") . " (modulename,setting,userid,value) SELECT 'specialtydarkarts', 'skill', acctid, darkarts FROM " . db_prefix("accounts");
			db_query($sql);
			debug("Dropping darkarts field from accounts table");
			$sql = "ALTER TABLE " . db_prefix("accounts") . " DROP darkarts";
			db_query($sql);
		} elseif ($row['Field']=="darkartuses") {
			debug("Migrating darkarts uses field");
			$sql = "INSERT INTO " . db_prefix("module_userprefs") . " (modulename,setting,userid,value) SELECT 'specialtydarkarts', 'uses', acctid, darkartuses FROM " . db_prefix("accounts");
			db_query($sql);
			debug("Dropping darkartuses field from accounts table");
			$sql = "ALTER TABLE " . db_prefix("accounts") . " DROP darkartuses";
			db_query($sql);
		}
	}
	debug("Migrating Darkarts Specialty");
	$sql = "UPDATE " . db_prefix("accounts") . " SET specialty='$specialty' WHERE specialty='1'";
	db_query($sql);

	module_addhook("choose-specialty");
	module_addhook("set-specialty");
	module_addhook("fightnav-specialties");
	module_addhook("apply-specialties");
	module_addhook("newday");
	module_addhook("incrementspecialty");
	module_addhook("specialtynames");
	module_addhook("specialtymodules");
	module_addhook("specialtycolor");
	module_addhook("dragonkill");
	return true;
}

function specialtydarkarts_uninstall(){
	// Reset the specialty of anyone who had this specialty so they get to
	// rechoose at new day
	$sql = "UPDATE " . db_prefix("accounts") . " SET specialty='' WHERE specialty='DA'";
	db_query($sql);
	return true;
}

function specialtydarkarts_dohook($hookname,$args){
	global $session,$resline;

	$spec = "DA";
	$name = "Dark Arts";
	$ccode = "`$";

	switch ($hookname) {
	case "dragonkill":
		set_module_pref("uses", 0);
		set_module_pref("skill", 0);
		break;
	case "choose-specialty":
		if ($session['user']['specialty'] == "" ||
				$session['user']['specialty'] == '0') {
			addnav("$ccode$name`0","newday.php?setspecialty=$spec$resline");
			$t1 = translate_inline("Killing a lot of woodland creatures");
			$t2 = appoencode(translate_inline("$ccode$name`0"));
			rawoutput("<a href='newday.php?setspecialty=$spec$resline'>$t1 ($t2)</a><br>");
			addnav("","newday.php?setspecialty=$spec$resline");
		}
		break;
	case "set-specialty":
		if($session['user']['specialty'] == $spec) {
			page_header($name);
			output("`5Growing up, you recall killing many small woodland creatures, insisting that they were plotting against you.");
			output("Your parents, concerned that you had taken to killing the creatures barehanded, bought you your very first pointy twig.");
			output("It wasn't until your teenage years that you began performing dark rituals with the creatures, disappearing into the forest for days on end, no one quite knowing where those sounds came from.");
		}
		break;
	case "specialtycolor":
		$args[$spec] = $ccode;
		break;
	case "specialtynames":
		$args[$spec] = translate_inline($name);
		break;
	case "specialtymodules":
		$args[$spec] = "specialtydarkarts";
		break;
	case "incrementspecialty":
		if($session['user']['specialty'] == $spec) {
			$new = get_module_pref("skill") + 1;
			set_module_pref("skill", $new);
			$c = $args['color'];
			$name = translate_inline($name);
			output("`n%sYou gain a level in `&%s%s to `#%s%s!",
					$c, $name, $c, $new, $c);
			$x = $new % 3;
			if ($x == 0){
				output("`n`^You gain an extra use point!`n");
				set_module_pref("uses", get_module_pref("uses") + 1);
			}else{
				if (3-$x == 1) {
					output("`n`^Only 1 more skill level until you gain an extra use point!`n");
				} else {
					output("`n`^Only %s more skill levels until you gain an extra use point!`n", (3-$x));
				}
			}
			output_notl("`0");
		}
		break;
	case "newday":
		$bonus = getsetting("specialtybonus", 1);
		if($session['user']['specialty'] == $spec) {
			$name = translate_inline($name);
			if ($bonus == 1) {
				output("`n`2For being interested in %s%s`2, you receive `^1`2 extra `&%s%s`2 use for today.`n",$ccode, $name, $ccode, $name);
			} else {
				output("`n`2For being interested in %s%s`2, you receive `^%s`2 extra `&%s%s`2 uses for today.`n",$ccode, $name,$bonus, $ccode,$name);
			}
		}
		$amt = (int)(get_module_pref("skill") / 3);
		if ($session['user']['specialty'] == $spec) $amt = $amt + $bonus;
		set_module_pref("uses", $amt);
		break;
	case "fightnav-specialties":
		$uses = get_module_pref("uses");
		$script = $args['script'];
		if ($uses > 0) {
			addnav(array("$ccode$name (%s points)`0", $uses),"");
			addnav(array("$ccode &#149; Skeleton Crew`7 (%s)`0", 1),
					$script."op=fight&skill=$spec&l=1", true);
		}
		if ($uses > 1) {
			addnav(array("$ccode &#149; Voodoo`7 (%s)`0", 2),
					$script."op=fight&skill=$spec&l=2",true);
		}
		if ($uses > 2) {
			addnav(array("$ccode &#149; Curse Spirit`7 (%s)`0", 3),
					$script."op=fight&skill=$spec&l=3",true);
		}
		if ($uses > 4) {
			addnav(array("$ccode &#149; Wither Soul`7 (%s)`0", 5),
					$script."op=fight&skill=$spec&l=5",true);
		}
		break;
	case "apply-specialties":
		$skill = httpget('skill');
		$l = httpget('l');
		if ($skill==$spec){
			if (get_module_pref("uses") >= $l){
				switch($l){
				case 1:
					if (getsetting("enablecompanions", true)) {
						apply_companion('skeleton_warrior', array(
							"name"=>"`4Skeleton Warrior",
							"hitpoints"=>round($session['user']['level']*3.33,0)+10,
							"maxhitpoints"=>round($session['user']['level']*3.33,0)+10,
							"attack"=>round((($session['user']['level']/4)+2))*round((($session['user']['level']/3)+2))+1.5,
							"defense"=>floor((($session['user']['level']/3)+0))*ceil(($session['user']['level']/6)+2)+2.5,
							"dyingtext"=>"`\$Your skeleton warrior crumbles to dust.`n",
							"abilities"=>array(
								"fight"=>true,
							),
							"ignorelimit"=>true, // Does not count towards companion limit...
						), true);
						// Because of this last "true" the companion can be added any time.
						// Even, if the player controls already more companions than normally allowed!
					} else {
						apply_buff('da1',array(
							"startmsg"=>"`\$You call on the spirits of the dead, and skeletal hands claw at {badguy} from beyond the grave.",
							"name"=>"`\$Skeleton Crew",
							"rounds"=>5,
							"wearoff"=>"Your skeleton minions crumble to dust.",
							"minioncount"=>round($session['user']['level']/3)+1,
							"maxbadguydamage"=>round($session['user']['level']/2,0)+1,
							"effectmsg"=>"`)An undead minion hits {badguy}`) for `^{damage}`) damage.",
							"effectnodmgmsg"=>"`)An undead minion tries to hit {badguy}`) but `\$MISSES`)!",
							"schema"=>"module-specialtydarkarts"
						));
					}
					break;
				case 2:
					apply_buff('da2',array(
						"startmsg"=>"`\$You pull out a tiny doll that looks like {badguy}.",
						"effectmsg"=>"You thrust a pin into the {badguy} doll hurting it for `^{damage}`) points!",
						"rounds"=>1,
						"minioncount"=>1,
						"maxbadguydamage"=>round($session['user']['attack']*3,0),
						"minbadguydamage"=>round($session['user']['attack']*1.5,0),
						"schema"=>"module-specialtydarkarts"
					));
					break;
				case 3:
					apply_buff('da3',array(
						"startmsg"=>"`\$You place a curse on {badguy}'s ancestors.",
						"name"=>"`\$Curse Spirit",
						"rounds"=>5,
						"wearoff"=>"Your curse has faded.",
						"badguydmgmod"=>0.5,
						"roundmsg"=>"{badguy} staggers under the weight of your curse, and deals only half damage.",
						"schema"=>"module-specialtydarkarts"
					));
					break;
				case 5:
					apply_buff('da5',array(
						"startmsg"=>"`\$You hold out your hand and {badguy} begins to bleed from its ears.",
						"name"=>"`\$Wither Soul",
						"rounds"=>5,
						"wearoff"=>"Your victim's soul has been restored.",
						"badguyatkmod"=>0,
						"badguydefmod"=>0,
						"roundmsg"=>"{badguy} claws at its eyes, trying to release its own soul, and cannot attack or defend.",
						"schema"=>"module-specialtydarkarts"
					));
					break;
				}
				set_module_pref("uses", get_module_pref("uses") - $l);
			}else{
				apply_buff('da0', array(
					"startmsg"=>"Exhausted, you try your darkest magic, a bad joke.  {badguy} looks at you for a minute, thinking, and finally gets the joke.  Laughing, it swings at you again.",
					"rounds"=>1,
					"schema"=>"module-specialtydarkarts"
				));
			}
		}
		break;
	}
	return $args;
}

function specialtydarkarts_run(){
}
?>
