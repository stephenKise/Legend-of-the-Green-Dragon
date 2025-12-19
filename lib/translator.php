<?php

/**
 * Set up the correct language for the current user
 * @return void
 */
function translator_setup(): void
{
	if (
        !file_exists('dbconnect.php') ||
        defined('TRANSLATOR_IS_SET_UP')
    ) return;
	define('TRANSLATOR_IS_SET_UP', true);

	global $language, $mysqli_resource, $session;
    if (isset($session['user']['prefs']['language'])) {
        $language = mysqli_real_escape_string(
            $mysqli_resource,
            $session['user']['prefs']['language']
        );
        return;
    }
	$language = mysqli_real_escape_string(
        $mysqli_resource,
        getsetting('defaultlanguage', 'en')
    );
    return;
}

/**
 * Takes input from a string or array, saves untranslated texts to the database,
 * and returns the translated text(s) if available.
 * @param string|array $input
 * @param string|null $namespace
 * @return string|array
 */
function translate(string|array $input, string|null $namespace = null): string|array
{
    if (
        !file_exists('dbconnect.php') ||
        getsetting('translatorenabled', true) == false
    ) {
        return $input;
    }
    $translation_table = [];
    global $language, $session, $translation_table, $i18nNamespace, $mysqli_resource;
    if (!$namespace) $namespace = $i18nNamespace;
    if ($namespace == 'notranslate') {
        return $input;
    }
    
    if (!$language) {
        translator_setup();
    }

	$output = $input;
    $foundTranslation = false;
	if ($namespace == '') tlschema();

	if (
        !isset($translation_table[$namespace]) ||
		!is_array($translation_table[$namespace])
    ) {
		$translation_table[$namespace] = translate_loadnamespace(
            $namespace,
            $language
        );
	}

	if (is_array($input)) {
		$output = [];
		foreach ($input as $key => $val) {
			$output[$key] = translate($val, $namespace);
		}
        return $output;
	}
	if (isset($translation_table[$namespace][$input])) {
		$output = $translation_table[$namespace][$input];
		$foundTranslation = true;
	}
    else if (getsetting('collecttexts', false)) {
        $untranslatedTable = db_prefix('untranslated');
        $input = mysqli_real_escape_string($mysqli_resource, $input);
        $namespace = mysqli_real_escape_string($mysqli_resource, $namespace);
		// db_query(
        //     "INSERT IGNORE INTO $untranslatedTable (
        //         intext,
        //         language,
        //         namespace
        //     ) 
        //     VALUES ('$input', '$language', '$namespace')",
        //     false
        // );
        // @TODO: temporrily disable untranslated collection due to performance issues
	}
	tlbutton_push($input, !$foundTranslation, $namespace);
	return $output;
}

function sprintf_translate(){
	$args = func_get_args();
	$setSchema = false;
	// Handle if an array is passed in as the first arg
	if (is_array($args[0])) {
		$args[0] = call_user_func_array('sprintf_translate', $args[0]);
	} else {
		if (is_bool($args[0]) && array_shift($args)) {
			tlschema(array_shift($args));
			$setSchema = true;
		}
		$args[0] = str_replace('`%','`%%',$args[0]);
		$args[0] = translate($args[0]);
		if ($setSchema) {
			tlschema();
		}
 	}
	reset($args);
	next($args);
	foreach ($args as $key => $val) {
		if (is_array($val)) {
			$args[$key] = call_user_func_array('sprintf_translate', $val);
		}
	}
	ob_start();
	$return = call_user_func_array('sprintf', $args);
	$err = ob_get_contents();
	ob_end_clean();
	if ($err > '') {
		$args['error'] = $err;
	}
	return $return;
}

function translate_inline(string|array $input, string|null $namespace = null): string|array
{
	$out = translate($input, $namespace);
	rawoutput(tlbutton_clear());
	return $out;
}

function translate_mail($in,$to=0){
	global $session;
	tlschema("mail"); // should be same schema like systemmails!
	if (!is_array($in)) $in=array($in);
	//this is done by sprintf_translate.
	//$in[0] = str_replace("`%","`%%",$in[0]);
	if ($to>0){
		$language = db_fetch_assoc(db_query("SELECT prefs FROM ".db_prefix("accounts")." WHERE acctid=$to"));
		$language['prefs'] = unserialize($language['prefs']);
		$session['tlanguage'] = $language['prefs']['language']?$language['prefs']['language']:getsetting("defaultlanguage","en");
	}
	reset($in);
	// translation offered within translation tool here is in language
	// of sender!
	// translation of mails can't be done in language of recipient by
	// the sender via translation tool.

	$out = call_user_func_array("sprintf_translate", $in);

	tlschema();
	unset($session['tlanguage']);
	return $out;
}

function tl($in){
	$out = translate($in);
	return tlbutton_clear().$out;
}

function translate_loadnamespace(string $namespace, string|null $language = null): array
{
    if (defined('IS_INSTALLER') && IS_INSTALLER === 1) return [];

    global $language;
	$page = translator_page($namespace);
	$uri = translator_uri($namespace);
	if ($page==$uri)
		$where = "uri = '$page'";
	else
		$where = "(uri='$page' OR uri='$uri')";
	$sql = "
		SELECT intext,outtext
		FROM ".db_prefix("translations")."
		WHERE language='$language'
			AND $where";
		// debug(nl2br(htmlentities($sql, ENT_COMPAT, getsetting("charset", "ISO-8859-1"))));
	if (getsetting('cachetranslations', 0) == 0) {
		$result = db_query($sql);
	}
    else {
        // Convert to file storage caching later
		$result = db_query_cached($sql, "translations-$namespace-$language", 600);
		//store it for 10 Minutes, normally you don't need to refresh this often
	}
	$out = [];
	while ($row = db_fetch_assoc($result)){
		$out[$row['intext']] = $row['outtext'];
	}
	return $out;
}

$translatorbuttons = [];
$seentlbuttons = [];
function tlbutton_push(string|array $indata, bool $hot = false, string|bool $namespace = FALSE): bool
{
	require_once('lib/session.php');
	global $translatorbuttons;
	global $translation_is_enabled,$seentlbuttons,$session;
	if (!$namespace) $namespace = 'unknown';
    if (
        !(getSessionSuperUser() & SU_IS_TRANSLATOR) ||
        !$translation_is_enabled ||
        preg_replace("/[ 	\n\r]|`./", '', $indata) == ''
    ) {
        return false;
    }
	if (isset($seentlbuttons[$namespace][$indata])) {
		$link = '';
	}
    else {
		$seentlbuttons[$namespace][$indata] = true;
		require_once('lib/sanitize.php');
		$uri = cmd_sanitize($namespace);
		$uri = comscroll_sanitize($uri);
		$link = "translatortool.php?u=".
			rawurlencode($uri)."&t=".rawurlencode($indata);
		$link = "<a href='$link' target='_blank' onClick=\"".
			popup($link).";return false;\" class='t".
			($hot?"hot":"")."'>T</a>";
        array_push($translatorbuttons, $link);
	}
    return true;
}

function tlbutton_pop(): string
{
	require_once('lib/session.php');
	global $translatorbuttons;
	if (!(getSessionSuperUser() & SU_IS_TRANSLATOR)) {
        return '';
	}
	return array_pop($translatorbuttons) ?: '';
}

function tlbutton_clear(): string
{
	global $translatorbuttons;
    if (!getSessionSuperUser() & SU_IS_TRANSLATOR) {
        return '';
    }
    $return = tlbutton_pop().join('', $translatorbuttons);
    $translatorbuttons = [];
    return $return;
}

$translation_is_enabled = true;
function enable_translation(bool $enable = true): void
{
	global $translation_is_enabled;
	$translation_is_enabled = $enable;
}

$i18nNamespace = '';
$i18nNamespaceArray = [];
function tlschema(string|null $schema = null){
	global $i18nNamespace, $i18nNamespaceArray, $REQUEST_URI;
	if (!$schema) {
		$i18nNamespace = array_pop($i18nNamespaceArray);
		if ($i18nNamespace == '') {
			$i18nNamespace = translator_uri($REQUEST_URI);
        }
	}
    else {
		array_push($i18nNamespaceArray, $i18nNamespace);
		$i18nNamespace = $schema;
	}
}

function translator_check_collect_texts(): bool
{
	$maxTranslations = getsetting('tl_maxallowed',0);
	if (
        getsetting('permacollect', 0) || 
        ($maxTranslations && getsetting('OnlineCount', 0) <= $maxTranslations)
    ) {
		savesetting('collecttexts', 1);
        return true;
	}

	savesetting('collecttexts', 0);
	return false;
}

