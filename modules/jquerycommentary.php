<?php

//Changes
// 1.0 - Stephen Kise
//    Initial creation.
// 1.1 - Anharat
//    Reworked the functionality for deletion of comments.
//    Reworked the functionality for refreshing the output of comments.
//    Introduced a timeout function, to prevent infinite loading.
//    Removed the path setting and $_SERVER variables.
//    Removed the allow_anonymous key in the _getmoduleinfo().
// 1.2 - Stephen Kise & Anharat
//    When a comment is made, the input field will be auto focused,
//        to allow for continuation. Suggested by HunterD.
//    Introduced a preference to change the refresh time.
//    Provided a message with a link to refresh the commentary.
//    Removed the blockcommentarea hook, forgot that insertcomment
//        provided the same args as blockcommentarea. Woops.
//    We found the me='$section'> being displayed and causing
//        the ajax call to break whenever commentary was cleared.
//    Removed the second AJAX call.
// 1.2.1 - Stephen Kise &  Nicolas Härter
//    If someone has trouble with the ä make an ae out of it :D
//    Added request uri to the delete call, else one ends up in
//        a badnav after delete and before the next call when entering a bio
//    Added ajax as get variable to avoid adding the live-commentary in ajax
//    Removed the check for the div when refreshing the commentary
//    Removed the check for session loggedin due the module does not allow anonymous users anymore

function jquerycommentary_getmoduleinfo()
{
    $info = [
        'name' => 'jQuery Commentary',
        'author' => '`b`&Stephen Kise`b, and Nicolas Härter',
        'version' => '1.2.5',
        'category' => 'Commentary',
        'download' => 'http://dragonsource/distro/get/commentary/jquerycommentary',
        'description' => 'Commentary that loads AJAX via jQuery statements, to replace the old prototype.js commentary, making the commentary friendly for other scripts. Edits were slightly made for Xythen.',
        'override_forced_nav' => true,
        'settings' => [
            'message' => 'What should your message above the commentary input be?,text|Interject your own commentary?',
            'talkline' => 'What action should be used for speaking?,text|says',
            'limit' => 'How much commentary should we provide?,int|25',
            'timeout' => 'How many seconds should we wait to timeout the commentary?,int|600'
        ],
        'prefs' => [
            'jQuery Commentary Prefs,title',
			'user_disable' => 'Do you want to disable the live chat?,bool|0',
            'user_refresh' => 'How often should we refresh the commentary (in seconds)?,range,1,10|1',
            'user_sounds' => 'Enable sounds for the commentary?, bool| 0',
            'user_jumpto' => 'Do you want to automatically scroll to the commentary?, bool| 0',
            'user_ninja' => 'Should we hide you from the \'Who\'s Typing\'?, bool| 0',
            'is_typing' => 'Is this player typing?, bool| 0',
            'current_section' => 'Current Commentary Section, viewonly| village',
        ]
    ];
    return $info;
}

function jquerycommentary_install()
{
    module_addhook("insertcomment");
    module_addhook("viewcommentary");
    module_addhook("endofcommentary");
    return true;
}

function jquerycommentary_uninstall()
{
    return true;
}

function jquerycommentary_dohook($hook, $args)
{
    global $jQueryDiv, $jQueryScript, $session, $output, $_SERVER, $SCRIPT_NAME;
    switch ($hook)
    {
        case "viewcommentary":
            if ($SCRIPT_NAME != 'moderate.php' && $session['user']['superuser'] & SU_EDIT_COMMENTS && get_module_pref('user_disable') != 1) {
                if (httpget('ajax') != '1' && $jQueryDiv == 0) {
                    rawoutput("<div class='live-commentary'>");
                    $jQueryDiv++;
                }
                preg_match_all("/\[([^\]]*)\]/", $args['commentline'], $matches);
                $text = $matches[1][0];
                preg_match_all("/removecomment=(\\d*)/", $text, $matches);
                $commid = $matches[1][0];
                $args['commentline'] = str_replace($text, "<a href='#live-commentary' class='deleteCommentary' data-comment-id='$commid'>Del</a>", $args['commentline']);
            }
            break;
        case "insertcomment":
            $session['current_commentary_area'] = $args['section'];
            set_module_pref('current_section', $args['section']);
            $sql = db_query("SELECT * FROM commentary WHERE author = '{$session['user']['acctid']}' AND deleted = 0 AND (section = 'globalooc' OR section = '{$args['section']}') ORDER BY commentid DESC LIMIT 0,1");
            $row = db_fetch_assoc($sql);
            $r = urlencode($_SERVER['REQUEST_URI']);
            $refresh = get_module_pref('user_refresh');
            $enableSounds = get_module_pref('user_sounds');
            $ninja = get_module_pref('user_ninja');
            $timeout = get_module_setting('timeout');
            if (get_module_pref('user_jumpto') == 1) {
                $autoscroll = "
                        $('html, body').animate({
                            scrollTop: $('.live-commentary').delay(500).offset().top
                        }, 400);";
            }
            rawoutput("<script src='https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js'></script>");
            if ((httpget('comscroll') == '' || httpget('comscroll') == '0') && $args['section'] != ' OR 1=1 ' && $jQueryScript == 0 && get_module_pref('user_disable') != 1) {
                rawoutput("<script type='text/javascript'>
                    var currentTimeout = 0;
                    var lastKeyUp = 0;
                    var myComment = 'test';
                    var myCommentID = 0;
                    var lastCommentID = 0;
                    var messageid = 0;
                    var whosTyping = '<br />';
                    var isInputEmpty = 0;
                    $(document).ready(function(){
                        $('#inputinsertcommentary').val(localStorage['commentaryForm']);
                        $('#commentaryform .button').hide();
                        if (typeof renewWhosHere == 'function') {
                            renewWhosHere();
                        }
                        $autoscroll
                        reloadCommentary(true);
                        setInterval(function() {
                            currentTimeout += $refresh;
                            if(currentTimeout > $timeout) {
                                $('.live-commentary').html('Please click \'Refresh\' to reload the commentary!');
                                $('.whoshere').html('...');
                            }
                            else {
                                if ((currentTimeout - lastKeyUp) > 0) {
                                    notTyping();
                                    isInputEmpty = 0;
                                }
                                reloadCommentary();
                            }
                        }, $refresh*1000);
                        $('#inputinsertcommentary').keypress(function(e) {
                            var lastKeyUp = currentTimeout;
                            var input = $(this).val();
                            localStorage.setItem('commentaryForm', input);
                            $('#charsleftinsertcommentary').hide();
                            if (isInputEmpty == 0 && input.length != 0 && $ninja != 1) {
                                isTyping();
                                isInputEmpty++;
                            }
                            if (input.length == 0) {
                                $('#previewtextinsertcommentary').hide();
                            }
                            else if (input.length != 0) {
                                $('#previewtextinsertcommentary').show();
                            }
                            if (input == '/edit' || (e.keyCode == '38' && input.length < 1)) {
                                $(this).val(myComment.replace(/(\<.*?\>)/ig, '').replace(/\`\`/ig, '`')).attr('name','updatecommentary_' + myCommentID);
                            }
                            if (e.keyCode == 13) {
                                e.preventDefault();
                                jquerypostcommentary();
                                return false;
                            }
                            currentTimeout = 0;
                        });
                    $('.live-commentary').on('click', '.deleteCommentary', function(event) {
                        $('.live-commentary').load('runmodule.php?module=jquerycommentary&ajax=1&section={$args['section']}&c={$session['counter']}&r=$r&rmvcmmnt='+$(this).attr('data-comment-id'));
                    });
                    });
                    function jquerypostcommentary() 
                    {
                        var postData = $('#inputinsertcommentary').val().replace('/(\<.*?\>)/', ' ');
                        var nameData = $('#inputinsertcommentary').attr('name');
                        var formURL = $('#jquerycommentaryform').attr('action');
                        $.ajax({
                            url : 'runmodule.php?module=jquerycommentary&op=post',
                            type: 'POST',
                            data : {method: nameData, comment: postData},
                            success: function (data){
                                localStorage.setItem('commentaryForm', '');
                                $('#inputinsertcommentary').val('').attr('name','insertcommentary');
                                notTyping();
                                $('#charsleftinsertcommentary, #previewtextinsertcommentary').hide();
                            },
                            error: function(){
                                alert('We could not successfully post. Check your internet connection, or wait a minute.');
                            }
                        });
                        reloadCommentary(true);
                        return false;
                    }
                    function reloadCommentary(force)
                    {
                        $('.is_typing').html(whosTyping);
                        $.getJSON('runmodule.php?module=jquerycommentary&op=last_comment', function(comments) {
                            console.log(comments);
                                myComment = comments.comment;
                                myCommentID = comments.commentid;
                                lastMessage = comments.last_message;
                                if ((comments.last_comment != lastCommentID && lastCommentID != 0 && comments.last_section != 'blackhole' && $enableSounds == 1) || force == true) {
                                    $('.live-commentary').load('runmodule.php?module=jquerycommentary&ajax=1&section={$args['section']}&c={$session['counter']}&r=$r');
                                    $.getJSON('runmodule.php?module=api&modulename=checkmail', function(messages) {
                                        if (messages.length > 0) {
                                            if (messages[0].seen == 1) {
                                                newMail();
                                            }
                                            else {
                                                clearMail();
                                            }
                                        }
                                        else {
                                            clearMail();
                                        }
                                    });
                                    if (!force) {
                                        notifyNewComment(lastCommentID);
                                    }
                                }
                                else if (comments.last_comment < lastCommentID || (comments.last_section == 'blackhole' && comments.last_comment != lastCommentID)) {
                                    $('.live-commentary').load('runmodule.php?module=jquerycommentary&ajax=1&section={$args['section']}&c={$session['counter']}&r=$r');
                                    if (typeof renewWhosHere == 'function') {
                                        renewWhosHere();
                                    }
                                    $.getJSON('runmodule.php?module=api&modulename=checkmail', function(messages) {
                                        if (messages.length > 0) {
                                            if (messages[0].seen == 1) {
                                                newMail();
                                            }
                                            else {
                                                clearMail();
                                            }
                                        }
                                        else {
                                            clearMail();
                                        }
                                    });
                                }
                                lastCommentID = comments.last_comment;
                        });
                    }
                    function notifyNewComment(ID)
                    {
                        notification = new Audio('templates/assets/newMessage.mp3');
                        notification.volume = .4;
                        notification.play();
                    }
                    function isTyping()
                    {
                        $.ajax({
                            type: 'POST',
                            url: 'runmodule.php?module=jquerycommentary&op=is_typing',
                            data: {typing: 'yes'},
                            success: function (message, status, jqXHR) {
                                whosTyping = message;
                            }
                        });
                    }
                    function notTyping()
                    {
                        $.ajax({
                            type: 'POST',
                            url: 'runmodule.php?module=jquerycommentary&op=is_typing',
                            data: {typing: 'no'},
                            success: function (message, status, jqXHR) {
                                whosTyping = message;
                            }
                        });
                    }
                    function newMail()
                    {
                        $('a[name=maillink]').html('Mailbox').removeClass('motd').addClass('unreadmotd');
                        $('.alerts').html('You have a new mail!').animate({
                            height: '1.25em'
                        }, 500);
                    }
                    function clearMail()
                    {
                        $('a[name=maillink]').html('Mailbox').removeClass('unreadmotd').addClass('motd');
                        
                    $('.alerts').html('').animate({
                        height: '0em'
                    }, 500);
                    }
                </script>");
                $jQueryScript++;
            rawoutput("</div>
                        <jquerycommentaryend></jquerycommentaryend>
                        <div class='is_typing' style='display: inline-block;'></div>
                        <div class='commentary_sound'></div>");
            }
            elseif (httpget('comscroll') != '' && httpget('comscroll') != '0')
            {
                rawoutput("<script type='text/javascript'>
                    $(document).ready(function(){
                        $('#commentaryform').hide();
                    });
                    </script>");
            }
            break;
    }
    return $args;
}

function jquerycommentary_run()
{
    global $_SERVER, $output, $session;

    require_once('lib/commentary.php');
    $section = httpget('section');
    $commentary = db_prefix('commentary');
    $accounts = db_prefix('accounts');
    if (($commid = httpget('rmvcmmnt')) != "")
    {
        $prefix = db_prefix('commentary');
        check_su_access(SU_EDIT_COMMENTS);
        if ($session['user']['superuser'] & SU_EDIT_COMMENTS) {
            db_query("DELETE FROM $prefix WHERE commentid = '$commid'");
        }
        db_query("INSERT INTO $commentary (section, author, comment, postdate) VALUES ('blackhole', '{$session['user']['acctid']}', 'I fucked up', '" . date('Y-m-d H:i:s') . "')");
        invalidatedatacache("comments-$section");
        invalidatedatacache("comments-blackhole");
    }
    if (httpget('section') == get_module_pref('current_section') && httpget('section') != '')
    {
        //echo 'x';
        //var_dump(get_all_module_settings());
        $output = "";
        $_SERVER['REQUEST_URI'] = httpget('r');
        $session['counter'] = httpget('c');
        viewcommentary(get_module_pref('current_section'), get_module_setting('message'), get_module_setting('limit'), get_module_setting('talkline'));
        $output = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $output);
        $output = substr($output, 0, strpos($output, "<jquerycommentaryend>"));
        db_query("UPDATE accounts SET laston = '" . date('Y-m-d H:i:s') . "' WHERE acctid = '{$session['user']['acctid']}'");
        echo trim("$output");
        invalidatedatacache("comments-$section");
        /*$sql = db_query(
            "SELECT a.name, a.acctid
            FROM accounts AS a
            LEFT JOIN module_userprefs AS m
            ON m.userid = a.acctid
            LEFT JOIN module_userprefs AS u
            ON u.userid = m.userid
            WHERE m.modulename = 'jquerycommentary'
            AND m.setting = 'is_typing'
            AND m.value = '1'
            AND u.modulename = 'jquerycommentary'
            AND u.setting = 'current_section'
            and u.value = '" . get_module_pref('current_section') ."'"
        );
        $typing = [];
        while ($row = db_fetch_assoc($sql)) {
            array_push($typing, [$row['acctid'], $row['name']]);
        }
        $isTyping = appoencode('`@');
        $i = 0;
        echo appoencode('`@Who\'s typing: `n');
        if (count($typing) != 0) {
            foreach ($typing as $key => $val) {
                $i++;
                if ($i == 1) {
                    $isTyping .= appoencode($val[1]);
                }
                else if ($i > 1 && count($typing) > $i) {
                    $isTyping .= appoencode("`@, {$val[1]}");
                }
                else if ($i == count($typing)) {
                    $isTyping .= appoencode("`@ and {$val[1]}");
                }
            }
            echo $isTyping;
        }
        else {
            echo appoencode('`@No one');
        }*/
    }
    switch (httpget('op'))
    {
        case 'get_json':
            $sql = db_query("SELECT commentid, author, comment FROM commentary WHERE section = '{$session['current_commentary_area']}' AND deleted = '0' ORDER BY commentid+0 DESC LIMIT 0, 25");
            $json = [];
            while ($row = db_fetch_assoc($sql)) {
                array_push($json, $row);
            }
            echo "<pre>";
            echo json_encode($json, JSON_PRETTY_PRINT);
            echo "</pre>";
            break;
        case 'post':
            $post = httpallpost();
            $post = modulehook('jquery-post-commentary', $post);
            $commentary = db_prefix('commentary');
            if ($post['method'] == 'insertcommentary') {
                require_once('lib/commentary.php');
                injectcommentary(
                    get_module_pref('current_section'),
                    get_module_setting('talkline'),
                    $post['comment']
                );
            }
            else {
                $commentid = explode('_',$post['method']);
                require_once('lib/systemmail.php');
                require_once('lib/sanitize.php');
                $post['comment'] = htmlent($post['comment']);
                db_query("UPDATE $commentary SET comment = '{$post['comment']}' WHERE commentid = '{$commentid[1]}'");
                db_query("INSERT INTO $commentary (section, author, comment, postdate) VALUES ('blackhole', '{$session['user']['acctid']}', 'I fucked up', '" . date('Y-m-d H:i:s') . "')");
                invalidatedatacache("comments-{$session['current_commentary_section']}");
                invalidatedatacache("comments-blackhole");
            }
            break;
        case 'last_comment':
            require_once('lib/sanitize.php');
            db_query("UPDATE accounts SET laston = '" . date('Y-m-d H:i:s') . "' WHERE acctid = '{$session['user']['acctid']}'");
            //$sql = db_query("SELECT comment, commentid FROM ".db_prefix('commentary')." WHERE author = '{$session['user']['acctid']}' AND section = '{$session['current_commentary_area']}' ORDER BY commentid DESC LIMIT 0,1");
            $sql = db_query(
                "SELECT comment, commentid FROM $commentary
                WHERE author = '{$session['user']['acctid']}'
                AND (section = 'globalooc'
                    OR section = '{$session['current_commentary_area']}')
                ORDER BY commentid DESC
                LIMIT 0,1"
            );
            $row = db_fetch_assoc($sql);
            $data =  $row;
            $sql = db_query(
                "SELECT commentid, section, comment FROM $commentary
                WHERE (section = 'globalooc'
                OR section = '{$session['current_commentary_area']}'
                OR section = 'blackhole'
                )
                ORDER BY commentid+0 DESC
                LIMIT 0,1"
            );
            $row = db_fetch_assoc($sql);
            $data['last_section'] = $row['section'];
            $data['last_comment'] = $row['commentid'];
            $data['last_message'] = $row['comment'];
            echo json_encode($data);
            break;
        case 'is_typing':
            $post = httpallpost();
            if ($post['typing'] == 'yes'){
                set_module_pref('is_typing', 1);
            }
            else {
                set_module_pref('is_typing', 0);
            }
            $sql = db_query(
                "SELECT a.name, a.acctid
                FROM accounts AS a
                LEFT JOIN module_userprefs AS m
                ON m.userid = a.acctid
                LEFT JOIN module_userprefs AS u
                ON u.userid = m.userid
                WHERE m.modulename = 'jquerycommentary'
                AND m.setting = 'is_typing'
                AND m.value = '1'
                AND u.modulename = 'jquerycommentary'
                AND u.setting = 'current_section'
                and u.value = '" . get_module_pref('current_section') ."'"
            );
            $typing = [];
            while ($row = db_fetch_assoc($sql)) {
                array_push($typing, [$row['acctid'], $row['name']]);
            }
            $isTyping = appoencode('`@');
            $i = 0;
            if (count($typing) != 0) {
                foreach ($typing as $key => $val) {
                    $i++;
                    if ($i == 1) {
                        $isTyping .= appoencode($val[1]);
                    }
                    else if ($i > 1 && count($typing) > $i) {
                        $isTyping .= appoencode("`@, {$val[1]}");
                    }
                    else if ($i == count($typing)) {
                        $isTyping .= appoencode("`@ and {$val[1]}");
                    }
                    if ($i == count($typing)) {
                        $isTyping .= appoencode("`@...");
                    }
                }
                echo "✏ $isTyping";
            }
            else {
                echo "<br />";
            }
            break;
        case 'api':
        header('Content-Type: application/json');
            /*$sql = db_query(
                "SELECT c.*, a.name FROM $commentary AS c
                LEFT JOIN $accounts AS a
                ON a.acctid = c.author
                WHERE (section = '{$session['current_commentary_area']}'
                OR section = 'global-ooc')
                AND deleted = 0
                ORDER BY commentid+0 DESC
                GROUP BY section
                LIMIT 0, 25"
            );*/
            $sql = db_query(
                "SELECT comm.*, acc.name FROM
                (
                    (SELECT * FROM
                        (SELECT * FROM commentary
                        WHERE section = 'globalooc'
                        AND deleted = '0'
                        ORDER BY commentid+0 DESC
                        LIMIT 0, 10)
                    AS c
                    ORDER BY c.commentid+0 ASC
                    LIMIT 0, 10)
                    UNION (
                        SELECT * FROM
                        (SELECT * FROM commentary
                        WHERE section = 'superuser'
                        AND deleted = '0'
                        ORDER BY commentid+0 DESC
                        LIMIT 0, 25)
                        AS c
                        ORDER BY c.commentid+0 ASC
                        LIMIT 0, 25
                    )
                ) AS comm
                LEFT JOIN accounts AS acc
                ON acc.acctid = comm.author"
            );
            $json = [];
            while ($row = db_fetch_assoc($sql)) {
                $row['name'] = appoencode($row['name']);
                $row['comment'] = appoencode($row['comment']);
                array_push($json, $row);
            }
            echo json_encode($json, JSON_PRETTY_PRINT);
            break;
    }
}
