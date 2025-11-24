<?php

/**
 * Returns the numeric value of the next rank from the given array
 *
 * @param array $ranks Ranks of a given Clan
 * @param int $current The numeric value of the current rank
 * @return int The numeric value of the next rank
 */
function clan_nextrank(array $ranks, int$current): int
{
	$temp = array_pop($ranks);
	$ranks = array_keys($ranks);
	while (count($ranks) > 0) {
		$key = array_shift($ranks);
		if ($key>$current) return $key;
	}
	return 30;
}

/**
 * Returns the numeric value of the previous rank from the given array
 *
 * @param array $ranks Ranks of a given Clan
 * @param int $current The numeric value of the current rank
 * @return int The numeric value of the previous rank
 */
function clan_previousrank(array $ranks, int $current): int
{
	$temp = array_pop($ranks);
	$ranks = array_keys($ranks);
	while (count($ranks) > 0) {
		$key = array_pop($ranks);
		if ($key < $current) return $key;
	}
	return 0;
}

/**
 * Outputs a form for superusers to edit/block Clan names and tags
 * @param int $clanId Id of the Clan to edit
 * @param string $clanName Name of the Clan to edit
 * @param string $clanTag Tag of the Clan to edit
 * @return void
 */
function editClanNameForm(int $clanId): void
{
    $clansPrefix = db_prefix('clans');
    $clanQuery = db_query(
        "SELECT clanname as name, clanshort as tag
         FROM $clansPrefix
         WHERE clanid = $clanId
         LIMIT 1;
        "
    );
    $clan = db_fetch_assoc($clanQuery);
    $clanName = htmlent($clan['name']);
    $clanTag = htmlent($clan['tag']);
    $formTemplate = file_get_contents('lib/clan/templates/SuperuserRenamingForm.php');
    $toggleMsg = translate_inline('[Toggle Clan Name/Tag Editor]');
	addnav('', "clan.php?op=detail&clan=$clanId");
	rawoutput(
        sprintf($formTemplate, $clanId, $clanName, $clanTag, $toggleMsg)
    );
}

/**
 * Outputs a form for creating a Clan, and provides option to rewrite upon
 * creation error
 * @param string $name Name of the Clan, or empty for new Clans
 * @param string $tag Tag of the Clan, or empty for new Clans
 * @return void
 */
function createClanForm(string $name = '', string $tag = ''): void
{
    $cleanName = stripslashes($name);
    $clanName = htmlent($cleanName);
    $cleanTag = stripslashes($tag);
    $clanTag = htmlent($cleanTag);
    $template = file_get_contents('lib/clan/templates/CreateClanForm.php');
    addnav('', 'clan.php?op=new&apply=1');
    output($template, $clanName, $clanTag, true);
}
