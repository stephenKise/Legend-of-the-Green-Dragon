<?php

function jQueryCommentary_getmoduleinfo(): array
{
    return [
        'name' => 'jQuery Commentary',
        'author' => 'Stephen Kise, Nicolas Härter',
        'version' => '1.3.0',
        'category' => 'Commentary',
        'download' => 'https://github.com/stephenKise/Legend-of-the-Green-Dragon',
        'description' => 'Live commentary through the power of JavaScript!',
        'prefs' => [
            'jQuery Commentary Prefs, title',
            'section' => 'Current Commentary Section, viewonly| village',
        ]
    ];
}

function jQueryCommentary_install(): bool
{
    module_addhook('api');
    module_addhook('insertcomment');
    module_addhook('blockcommentarea');
    module_addhook('}collapse');
    module_addhook_priority('everyfooter-loggedin', 1);
    return true;
}

function jQueryCommentary_uninstall(): bool
{
    return true;
}

function jQueryCommentary_dohook(string $hook, array $args): array
{
    global $chatFormDeclared, $chatMessagesDeclared, $session, $SCRIPT_NAME;
    if ($_COOKIE['template'] !== 'ModernDark.htm') return $args;
    if ($SCRIPT_NAME == 'moderate.php') {
        return $args;
    }
    switch ($hook) {
        case 'blockcommentarea':
            if ($args['section'] == 'motd') {
                $args['block'] = 'yes';
                break;
            }
            if ($chatMessagesDeclared == 0) {
                rawoutput("<div id='core-chat' hidden>");
            }
            $chatMessagesDeclared++;
            break;
        case 'insertcomment':
            if ($chatMessagesDeclared > 0) {
                rawoutput(
                    "</div><div id='jQuery-chat'>
                    Loading the commentary, please wait!
                    </div>"
                );
            }
            set_module_pref(
                'section',
                $args['section'],
                'jQueryCommentary',
                $session['user']['acctid']
            );
            $chatFormDeclared = 1;
            chatForm($args['section']);
            rawoutput("<div id='core-chat-navs' hidden>");
            $args['mute'] = true;
            break;
        case 'everyfooter-loggedin':
                if ($chatFormDeclared == 1) {
                    rawoutput("</div>");
                }
            break;
        case 'api':
            $args['jQueryCommentary'] = [
                'getChatSection' => [
                    'getChatSection',
                    'GET the user\'s current commentary section',
                ],
                'setChatSection' => [
                    'setChatSection',
                    'POST $section of new current commentary section',
                ],
                'postMessage' => [
                    'postMessage',
                    'POST $message of commentary to $section, by the $author.'
                ],
                'editMessage' => [
                    'editMessage',
                    'POST $id of message to edit.'
                ],
                'removeMessage' => [
                    'removeMessage',
                    'POST $id of message to send for deletion.'
                ],
                'getLastMessage' => [
                    'getLastMessage',
                    'GET $comment of player\'s most recent commentary.'
                ],
                'getChatMessages' => [
                    'getChatMessages',
                    'GET list of messages for current section, for given $offset.'
                ],
                'getAllChatData' => [
                    'getAllChatData',
                    'GET all of the data associated with the chat.'
                ]
            ];
            break;
    }
    return $args;
}


function chatForm($section){
    require_once("lib/forms.php");
    global $REQUEST_URI, $session;
    $prefs = $session['user']['prefs'];
    $req = comscroll_sanitize($REQUEST_URI) . '&comment=1';
    $req = str_replace('?&', '?', $req);
    if (!strpos($req,"?")) $req = str_replace("&","?",$req);
    addnav('', $req);
    rawoutput(
        "<br/><div id='whosTyping'>&nbsp;</div>
        <form action='$req' method='POST' autocomplete='false'
            id='jQueryChatForm'>
        <div class='jQuery-chat-form' id='chatForm'>"
    );
    previewfield(
        'insertcommentary',
        $session['user']['name'],
        'says',
        true,
        [
            'type' => 'textarea',
            'class' => 'input',
            'cols' => 60,
            'rows' => 3
        ]
    );
    rawoutput(
        "<input type='submit' class='button' value='Add' id='submitChat'>
        </div>
        <input type='hidden' name='section' value='$section' />
        <div id='previewtext    '></div>"
    );
}

function getAllChatData(): array
{
    return [
        'chatSection' => getChatSection()['chatSection'],
        'myComment' => getLastMessage()
    ];
}

function getChatSection(): array
{
    global $session;
    $section = get_module_pref(
        'section',
        'jQueryCommentary',
        $session['user']['acctid']
    );
    return ['chatSection' => $section];
}

function getLastMessage(): array
{
    global $session;
    $commentary = db_prefix('commentary');
    $section = get_module_pref(
        'section',
        'jQueryCommentary',
        $session['user']['acctid']
    );
    $section = addslashes($section);
    $sql = db_query(
        "SELECT comment, section, commentid FROM $commentary
        WHERE author = {$session['user']['acctid']}
        AND (section = '$section' OR section = 'global-ooc')
        ORDER BY commentid+0 DESC LIMIT 1"
    );
    if (db_num_rows($sql) == 0) {
        $row = [
            'comment' => '',
            'section' => '',
            'commentid' => 0,
        ];
    }
    $row = db_fetch_assoc($sql);
    $row['comment'] = stripslashes($row['comment']);
    db_free_result($sql);
    return $row;
}

function editMessage(): array
{
    global $session;
    if (!$session['user']['loggedin']) {
        return [];
    }
    $post = httpallpost();
    $post['commentid'] = (int) $post['commentid'];
    $commentary = db_prefix('commentary');
    $sql = db_query(
        "SELECT author, section, comment, commentid FROM $commentary
        WHERE commentid = {$post['commentid']}
        LIMIT 1"
    );
    $row = db_fetch_assoc($sql);
    $row['comment'] = stripslashes($row['comment']);
    if ((int) $row['author'] != (int) $session['user']['acctid'] &&
            (int) $row['author'] != 0) {
        require_once('lib/gamelog.php');
        gamelog(
            '`$tried to edit a comment that was not theirs!',
            'bug abuse'
        );
        return [];
    }
    return $row;
}

function removeMessage(): array
{
    require_once('lib/gamelog.php');
    global $session;
    $post = httpallpost();
    $post['commentid'] = (int) $post['commentid'];
    $commentary = db_prefix('commentary');
    $sql = db_query(
        "SELECT author, comment, commentid FROM $commentary
        WHERE commentid = {$post['commentid']}
        LIMIT 1"
    );
    $row = db_fetch_assoc($sql);
    $row['author'] = (int) $row['author'];
    $row['skipPermission'] = false;
    $row = modulehook('chat-delete', $row);
    if (!$session['user']['superuser'] &~ SU_EDIT_COMMENTS &&
        $row['author'] != $session['user']['acctid'] &&
        $row['skipPermission'] == false) {
        gamelog('`$tried to delete commentary through exploits.', 'bug abuse');
        return [];
    }
    gamelog("`Qdeleted \"{$row['comment']}`Q\"");
    db_query("DELETE FROM $commentary WHERE commentid = {$post['commentid']}");
    return $post;
}

function getChatMessages(): array
{
    global $session, $ret;
    $response = [];
    if (!$session['user']['loggedin']) {
        array_push(
            $response,
            [
            'formattedComment' => appoencode(
                "`Q`b`cYou have timed out!`c`b`0`n" .
                "`2Hey, you need to relog! It seems as" .
                " that you have timed out due to not typing! Please refresh " .
                "this page to log back in!`0`n`n`^Thank you for roleplaying!" .
                "`0`n`@`iSunday, Xythen Dev.`i`0"
            )
            ]
        );
        return $response;
    }
    $post = httpallpost();
    $page = (int) $post['page'];
    $ooc = (int) $post['ooc'];
    $offset = "LIMIT " . ((0+$page)*13) . ", 13";
    $oocOffset = "LIMIT " . ((0+$ooc)*7) . ", 7";
    $user = $session['user'];
    // $user['allowednavs'] = json_decode($user['allowednavs']);
    $section = get_module_pref(
        'section',
        'jQueryCommentary',
        $session['user']['acctid']
    );
    $section = addslashes($section);
    $ret = URLEncode($post['ret']);
    $commentary = db_prefix('commentary');
    $accounts = db_prefix('accounts');
    $sql = db_query(
        "SELECT c.*, a.name FROM $commentary AS c
        LEFT JOIN $accounts AS a ON c.author = a.acctid
        WHERE section = '$section'
        ORDER BY commentid+0 DESC
        $offset"
    );
    if (db_num_rows($sql) < 1) {
        array_push(
            $response,
            [
                'formattedComment' => appoencode(
                    "`n`2`iSilence reigns in these parts...`i"
                )
            ]
        );
    }
    while ($row = db_fetch_assoc($sql)) {
        if ($row['name'] == '') {
            $row['name'] = '';
        }
        array_push(
            $response,
            formatComment($row)
        );
    }
    array_push(
        $response,
        ['formattedComment' => 
            appoencode(
                "`n`b`QRoleplay Chat`b`0 &nbsp;&nbsp;
                    <input class='button' onclick='incrementRP();'
                        type='submit' id='prevrp' value='< Previous'>
                    <input class='button' onclick='decrementRP();'
                        type='submit' id='nextrp' value='Refresh'>",
                true
            )
        ]
    );
    $sql = db_query(
        "SELECT c.*, a.name FROM $commentary AS c
        LEFT JOIN $accounts AS a ON c.author = a.acctid
        WHERE section = 'global-ooc'
        ORDER BY commentid+0 DESC
        $oocOffset"
    );
    if (db_num_rows($sql) < 1) {
        array_push(
            $response,
            [
                'formattedComment' => appoencode(
                    "`n`2`iThere is nothing to see here. Literally.`i"
                )
            ]
        );
    }
    while ($row = db_fetch_assoc($sql)) {
        if ($row['name'] == '') {
            $row['name'] = '';
        }
        array_push(
            $response,
            formatComment($row)
        );
    }
    db_free_result($sql);
    array_push(
        $response,
        ['formattedComment' => 
            appoencode(
                "`Q`b`QOOC Chat`b`0 &nbsp;&nbsp;
                    <input class='button' onclick='incrementOOC();'
                        type='submit' id='prevooc' value='< Previous'>
                    <input class='button' onclick='decrementOOC();'
                        type='submit' id='nextooc' value='Refresh'>
                ",
                true
            )
        ]
    );
    // $session['user']['allowednavs'] = json_encode($session['allowednavs']);
    // $navs = addslashes($session['user']['allowednavs']);
    // db_query(
    //     "UPDATE $accounts SET allowednavs = '$navs',
    //     laston = CURRENT_TIMESTAMP
    //     WHERE acctid = {$user['acctid']}"
    // );
    $response = array_reverse($response);
    return $response;
}

function formatComment(array $row): array
{
    require_once('lib/sanitize.php');
    global $ret, $session;
    $original = $row['comment'];
    $patterns = [
        '/^::/',
        '/^:/',
        '/^\/me/'
    ];
    $replace = [' ', ' ', ' '];
    $row['comment'] = preg_replace($patterns, $replaces, $row['comment']);
    if ($original == $row['comment']) {
        $row['comment'] = " `3says, \"`#{$row['comment']}`3\"";
    }
    if ($row['comment'] != sanitize_html($row['comment'])) {
        $row['comment'] = sanitize_html($row['comment']);
    }
    $edit = '';
    if ($row['author'] == $session['user']['acctid'] ||
        $session['user']['superuser'] & SU_EDIT_COMMENTS) {
        $edit .= appoencode(
            "<a class='removeMessage'
                onclick='removeChatMessage({$row['commentid']});'>
                `^ &#x274C;
            </a>",
            true
        );
    }
    if ($row['author'] == $session['user']['acctid']) {
        $edit .= appoencode(
            "<a class='editMessage'
                onclick='editMessageOf({$row['commentid']});'>
                `^&#x1F4DD;
            </a>",
            true
        );
    }
    $session['allowednavs']["bio.php?char={$row['author']}&ret=$ret"] = true;
    $bioLink = "<a href='bio.php?char={$row['author']}&ret=$ret'>
        {$row['name']}
    </a>";
    // $bioLink = "<a href='petition.php'>
    //     {$row['name']}
    // </a>";
    $row['comment'] = sanitize_html(stripslashes($row['comment']));
    return  modulehook(
        'chat-format',
        [
            'author' => $row['author'],
            'name' => $row['name'],
            'id' => $row['commentid'],
            'posted' => $row['postdate'],
            'rawComment' => $row['comment'],
            'formattedComment' => appoencode("<div class='jQuery-message'>
                    `0$edit $bioLink {$row['comment']}
                </div>", true)
        ]
    );
}
/*
function formatComment(int $author, string $name, string $comment): string
{
    global $ret;
    $original = $comment;
    $patterns = [
        '/^::/',
        '/^:/',
        '/^\/me/'
    ];
    $replaces = [' ', ' ', ' '];
    $comment = preg_replace($patterns, $replaces, $comment);
    if ($comment == $original) {
        $comment = " `3says, \"`#$comment`3\"";
    }
    if ($comment != sanitizeHTML($comment)) {
        require_once('lib/gamelog.php');
        gamelog('`$tried to execute HTML script in comment!', 'bug abuse');
    }
    $bioLink =
        "<a href='bio.php?char=$author&ret=$ret'>
            $name
        </a>";
    return appoencode($bioLink . sanitizeHTML(stripslashes($comment)), true);
}
*/
function formatChatString(string $formattedComment,  array $row): array
{
    $timeStamp = reltime(strtotime($row['postdate']));
    $formattedComment = "<div class='chatMessage' data-author='{$row['author']}'
        data-commentid='{$row['commentid']}'>
            `)(`2$timeStamp`)) $formattedComment
        </div>";
    // $session['allowednavs']["bio.php?char={$row['author']}&ret=$ret"] =
    //     true;
    return  modulehook(
        'chat-format',
        [
            'author' => $row['author'],
            'name' => $row['name'],
            'id' => $row['commentid'],
            'posted' => $row['postdate'],
            'rawComment' => $row['comment'],
            'formattedComment' => appoencode(
                "<div class='jQuery-message'>$formattedComment</div>"
            )
        ]
    );
}

function setChatSection(): array
{
    global $session;
    $section = httppost('section');
    if ($section == 'superuser' &&
        !$session['user']['superuser'] &~ SU_GIVE_GROTTO) {
        return ['section' => get_module_pref(
                    'section',
                    'jQueryCommentary',
                    $session['user']['acctid']
                )
        ];
    }
    set_module_pref(
        'section',
        $section,
        'jQueryCommentary',
        $session['user']['acctid']
    );
    return ['section' => $section];
}

function postMessage(): array
{
    global $session;
    require_once('lib/gamelog.php');
    $post = httpallpost();
    $patterns = ['/^:ooc/', '/^\/ooc/'];
    $replaces = [':', ''];
    $post['comment'] = trim($post['comment']);
    $comment = trim(preg_replace($patterns, $replaces, $post['comment']));
    $commentary = db_prefix('commentary');
    $acctid = (int) $session['user']['acctid'];
    if ($post['comment'] != $comment) {
        gamelog("{$post['comment']} != $comment", 'debugging');
        $post['section'] = 'global-ooc';
    }
    $post['acctid'] = $acctid;
    if ($comment != sanitize_html($comment)) {
        $comment = sanitize_html($comment);
        gamelog('`$tried to execute HTML script in comment!', 'bug abuse');
    }
    $post['comment'] = $comment;
    $post = modulehook('chat-intercept', $post);
    $comment = $post['comment'];
    if (array_key_exists('edited', $post)) {
        $post['edited'] = (int) $post['edited'];
        $sql = db_query(
            "SELECT author FROM $commentary
            WHERE commentid = {$post['edited']} LIMIT 1"
        );
        $row = db_fetch_assoc($sql);
        if ((int) $row['author'] != $acctid && (int) $row['author'] != 0) {
            return [
                'status' => false,
                'errorMessage' => 'Failed to edit commentid of ' . $post['edited']
            ];
        }
        db_free_result($sql);
        $sql = db_query(
            "UPDATE $commentary SET comment = '$comment'
            WHERE commentid = {$post['edited']} LIMIT 1"
        );
        return [
            'status' => true,
            'successMessage' => 'Updated commentid of ' . $post['edited']
        ];
    }
    $sql = db_query(
        "INSERT INTO $commentary (section, comment, author, postdate)
        VALUES ('{$post['section']}', '{$comment}', {$post['acctid']}, NOW());"
    );
    $post['lastID'] = db_insert_id();
    modulehook('chat-inserted', $post);
/*
    //$post['section'], $post['comment'];
    //INSERT INTO commentary (section, comment, author) 
    //VALUES (get_module_pref('section'), $post['comment'],
    //$session['user']['acctid']);

    //Clear Cache
    return [
        'lastID' => db_insert_id()
    ];*/
    return [
        'status' => true,
        'sql' => "INSERT INTO $commentary (section, comment, author)\
        VALUES ('{$post['section']}', '{$comment}', {$post['acctid']});",
        'successMessage' => "{$post['comment']}|{$comment}|{$post['section']}",
        'response' => $post
    ];
}

function checkForOOC(string $comment) {
    $patterns = ['/^:ooc/', '/^\/ooc/'];
    $replaces = [':', ''];
    return preg_replace($patterns, $replaces, $comment);
}