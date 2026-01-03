<?php
	$fields = 'raceauthor,raceactive,racespecial,racename,racevillage,racecolour,racechoose,raceset,racenewday,raceturns,racetravel,racepvp,racegrave,newdaybuff,pvpadjust,alter-gemchance,creatureencounter,battle-victory,battle-defeat';

	$races_array = array(
		array('`7Gray Ferret', 4800, 3, 55, 0, 1, 0, 'A small gray ferret popular as pets among the younger crowd.', 'Emerging from your backpack with a yawn, your little ferret is ready for the days travels.', 'Your ferret hides in your pack as you wander about the village.', 'Your ferret goes chasing after a grasshopper as you stroll through the gardens.', 'Your ferret buries itself in your backpack as the battle begins.',0,0,0,0,0,'All'),
		array('`&White Rabbit', 4500, 2, 100, 0, 1, 0, 'A small rabbit with a soft white coat. Popular among children.', 'Your rabbit hops around your feet as you prepare for the new day at hand.', 'As you wander through the village, you hold your pet rabbit in your arms.', 'Your pet rabbit hops among the flower patches, searching for a meal.', 'With a squeal, your rabbit hides among the bushes as the enemy draws closer.',0,0,0,0,0,'All'),
		array('`)Black Rat', 3150, 2, 67, 0, 0, 0, 'A small, slightly diseased black rat. Perfect for any social occassion.', 'Not one for mornings, your little black rat remains inside your backpack.', 'Villagers keep a cautious distance from you and your black rat.', 'Your little black rat hunts for food scraps and insects among the flower beds.', 'Terrified, your little black rat buries itself in your backpack as the battle begins.',0,0,0,0,0,'All'),
		array('`qBrown Rat', 3200, 2, 85, 0, 0, 0, 'A small brown rat, common to many alleyways and shops.', 'New day dawning, your pet rat peeks out from your pack with a somewhat disinterested look upon its face.', 'Fearful of the villagers, your little brown rat hides in your backpack.', 'Your little brown rat hides in your pack as a fairy buzzes by.', 'Terrified, your little brown rat buries itself in your backpack as the battle begins.',0,0,0,0,0,'All'),
	);

	$count = count($races_array);

	$sop = httpget('sop');
	if( $sop == 'install' )
	{

		$races = httppost('races');
		$allraces = httppost('allraces');
		$count2 = count($races_array[0]);
		$passfail = '';

		$j = 0;
		$k = 0;
		for( $i=0; $i<$count; $i++ )
		{
			if( $races["race$i"] == 1 || $allraces == 1 )
			{
				$race = '';
				foreach( $races_array[$i] as $key => $value )
				{
					$race .= "'".addslashes($value)."',";
				}
				$race = rtrim($race,',');
				$sql = "INSERT INTO " . db_prefix('races') . " (" . $fields . ") VALUES (" . $race . ")";

				if( db_query($sql) !== FALSE )
				{
					$passfail .= "`^$i `@Pass`n";
					$j++;
				}
				else
				{
					$passfail .= "`^$i `\$Fail`n";
				}
				$k++;
			}
		
		}

		debug(appoencode($passfail));
		output("`n`#%s `3races were added to the database for your convenience.`n`n", $j);
	}
	else
	{
		require_once('lib/showform.php');

		output("`3Which of the following races do you want to install?.`n`n");

		$races = '';
		for( $i=0; $i<$count; $i++ )
		{
			$races .= ",race$i," . appoencode($races_array[$i][0]);
		}

		$row = array(
			'allraces'=>'',
			'races'=>array()
		);
		$form = array(
			'Install Which Races?,title',
			'allraces'=>'Install ALL races?,bool',
			'`^Aren\'t you glad I put this at the top? Heh.`0,note',
			'races'=>'Races:,checklist' . $races
		);

		rawoutput('<form action="runmodule.php?module=race_creator&op=addpets&sop=install" method="POST">');
		addnav('','runmodule.php?module=race_creator&op=addpets&sop=install');
		showform($form,$row);
		rawoutput('</form>');

		addnav('Back');
		addnav('Go back','runmodule.php?module=race_creator');
	}

?>