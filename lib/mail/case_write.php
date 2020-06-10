<?php

$mail = db_prefix('mail');
$accounts = db_prefix('accounts');
$subject = httppost('subject');
$replyTo = httpget('replyto');
$to = httpget('to');
if ($session['user']['superuser'] & SU_IS_GAMEMASTER) {
    $from = httppost('from');
}
$body = '';
$row = '';
if ($replyTo) {
    $sql = db_query(
        "SELECT m.sent, m.body, m.msgfrom, m.subject, a.login, a.superuser, a.name
        FROM $mail AS m
        LEFT JOIN $accounts AS a ON a.acctid = m.msgfrom
        WHERE msgto = '{$session['user']['acctid']}' AND messageid = '$replyTo'"
    );
    $row = db_fetch_assoc($sql);
    if (empty($row)) {
        output("`4`iNo such message was found.`i`0 `n");
        include_once 'lib/mail/case_default.php';
        popup_footer();
    } elseif (!$row['login']) {
        output("`4`iYou cannot reply to a system message.`i`0 `n");
        unset($row);
        include_once 'lib/mail/case_default.php';
        popup_footer();
    }
}
if ($to) {
    $sql = db_query(
        "SELECT login, name, superuser FROM $accounts
        WHERE login = '$to'"
    );
    if (!($row = db_fetch_assoc($sql))) {
        output(
            "`4`iCould not find a user named `^'%s'`4.`i`0 `n",
            ucfirst($to)
        );
        include_once 'lib/mail/case_default.php';
        popup_footer();
    }
}
if (is_array($row)) {
    if ($row['subject'] != '') {
        $subject = $row['subject'];
        if (strncmp($subject, "RE: ", 4) !== 0) {
            $subject = "RE: $subject";
        }
    }
    if ($row['body'] > '') {
        $original = sprintf_translate(
            [
            'Original Message from %s (%s)',
            sanitize($row['name']),
            date('Y-m-d H:i:s', strtotime($row['sent']))
            ]
        );
        $body = "\n\n---$original---\n{$row['body']}";
    }
}
rawoutput("<form action='mail.php?op=send' method='post'>");
if ($session['user']['superuser'] & SU_IS_GAMEMASTER) {
    output(
        "<input type='hidden' name='from' value='%s'>",
        htmlent(stripslashes($from)),
        true
    );
}
output(
    "<input type='hidden' name='returnto' value='%s'>",
    htmlent(stripslashes(httpget("replyto"))),
    true
);
$superusers = [];
if (($session['user']['superuser'] & SU_IS_GAMEMASTER) && $from > '') {
    output("`@`bFrom:`b `^%s`0`n", $from);
}
if (isset($row['login']) && $row['login'] != "") {
    output_notl(
        "<input type='hidden' name='to' id='to' value='%s'>",
        htmlent($row['login']),
        true
    );
    output("`@`bTo:`b `^%s`0`n", $row['name']);
    if (($row['superuser'] & SU_GIVES_YOM_WARNING) && !($row['superuser'] & SU_OVERRIDE_YOM_WARNING)) {
        array_push($superusers, $row['login']);
    }
} else {
    output("`@`bTo:`b `^");
    $accounts = db_prefix('accounts');
    $to = str_split(httppost('to'));
    $to = implode('%', $to);
    $to = "%$to%";
    $sql = db_query(
        "SELECT login, name, superuser FROM accounts
        WHERE (login LIKE '$to' OR name LIKE '$to')
        AND locked = 0
        ORDER BY superuser+0 DESC, acctid"
    );
    $numRows = db_num_rows($sql);
    if ($numRows < 1) {
        $to = str_replace('%', '', $to);
        output(
            "%s `n`4Sorry, but we could not find a user with that name.`0",
            $to
        );
        httpset('prepop', $to, true);
        rawoutput("</form>");
        include_once 'lib/mail/case_address.php';
        popup_footer();
    } elseif ($numRows > 1) {
        output_notl("<select name='to' id='to' onchange='check_su_warning();'>", true);
    }
    while ($row = db_fetch_assoc($sql)) {
        if ($numRows == 1) {
            rawoutput("<input type='hidden' name='to' id='to' value='{$row['login']}'>");
            output_notl("{$row['name']}`0`n");
        } else {
            $rowNum++;
            $row['name'] = htmlent(full_sanitize($row['name']));
            output_notl(
                "<option value='%s' data-superuser='%s'>%s</option>",
                $row['login'],
                $row['superuser'],
                $row['name'],
                true
            );
            if ($numRows == $rowNum) {
                output_notl("</select>`0`n", true);
            }
            if (($row['superuser'] & SU_GIVES_YOM_WARNING) && !($row['superuser'] & SU_OVERRIDE_YOM_WARNING)) {
                array_push($superusers, $row['login']);
                debug('su');
            }
        }
    }
}
rawoutput("<script type='text/javascript'>var superusers = new Array();");
foreach ($superusers as $val) {
    rawoutput(" superusers['" . addslashes($val) . "'] = true;");
}
rawoutput("</script>");
output("`@`bSubject:`b`0");
if ($replyTo == '') {
    output(
        "<input name='subject' value='%s' autofocus><br>",
        htmlent($subject),
        true
    );
} else {
    output(
        "<input name='subject' value='%s'><br>",
        htmlent($subject),
        true
    );
}
rawoutput("<div id='warning' style='visibility: hidden; display: none;'>");
output("`@`bNotice:`b `^$superusermessage`0`n");
rawoutput("</div>");
output("`@`bBody:`b`0`n");
require_once 'lib/forms.php';
previewfield(
    'body',
    '`^',
    false,
    false,
    [
    'type' => 'textarea',
    'class' => 'input',
    'cols' => 60,
    'rows' => 9,
    'onKeyDown' => 'sizeCount(this);'
        ],
    htmlent($body) . htmlent(stripslashes(httpget('body')))
);
$send = translate_inline('Send');
rawoutput(
    "<table border='0' cellpadding='0' cellspacing='0' width='100%'>
        <tr>
            <td>
                <input type='submit' class='button' value='$send'>
            </td>
            <td align='right'>
                <div id='sizemsg'></div>
            </td>
        </tr>
    </table>"
);
rawoutput("</form>");
$sizeLimit = getsetting('mailsizelimit', 1024);
$sizeMsg = sprintf_translate(
    [
    "`#Max message size is `@%s`#, you have `^XX`# characters left.",
    $sizeLimit
    ]
);
$sizeMsgOver = sprintf_translate(
    [
    "`\$Max message size is `@%s`\$, you are over by `^XX`\$ characters!",
    $sizeLimit
    ]
);
$sizeMsg = explode('XX', $sizeMsg);
$sizeMsgOver = explode('XX', $sizeMsgOver);
$uSize1 = addslashes("<span>" . appoencode($sizeMsg[0]) . "</span>");
$uSize2 = addslashes("<span>" . appoencode($sizeMsg[1]) . "</span>");
$oSize1 = addslashes("<span>" . appoencode($sizeMsgOver[0]) . "</span>");
$oSize2 = addslashes("<span>" . appoencode($sizeMsgOver[1]) . "</span>");
rawoutput(
    "<script type='text/javascript'>
        var maxlen = $sizeLimit;
        function sizeCount(box)
        {
            if (box == null) {
                return;
            }
            var len = box.value.length;
            var msg = '';
            if (len <= maxlen) {
                msg = '$usize1' + (maxlen - len) + '$usize2';
            }
            else {
                msg = '$osize1' + (len - maxlen) + '$osize2';
            }
            document.getElementById('sizemsg').innerHTML = msg;
        }
        sizeCount(document.getElementById('inputbody'));
        function check_su_warning()
        {
            var to = document.getElementById('to');
            var warning = document.getElementById('warning');
            if (superusers[to.value]) {
                warning.style.visibility = 'visible';
                warning.style.display = 'inline';
            }
            else {
                warning.style.visibility = 'hidden';
                warning.style.display = 'none';
            }
        }
        check_su_warning();
    </script>"
);
