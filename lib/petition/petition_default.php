<?php
tlschema('petition');
popup_header('Petition for Help');
$post = httpallpost();
$petitions = db_prefix('petitions');
$hidden = getSessionUser('loggedin') ? 'hidden' : 'text';
$emailAddress = getSessionUser('emailaddress') ?: '';
$userName = getSessionUser('name') ?: '';
if (!$session['user']['loggedin']) {
    $session['user']['lastip'] = $_SERVER['REMOTE_ADDR'];
}
$sessionJson = json_encode($session, JSON_PRETTY_PRINT);
$petitionHeader = "`^Please make sure to read the `b`4FAQ`b`^ before sending in a petition. Most common answers can be found there and will save both you and the administration time!";
$petitionForm = "
    <form action='petition.php' method='POST'>
    <label for='name'>Username: {$userName}</label>
    <input type='{$hidden}' name='name' value=\"{$userName}\" />
    <br />
    <label for='emailaddress'>Email: {$emailAddress}</label>
    <input type='{$hidden}' name='emailaddress' value='{$emailAddress}' />
    <br />
    <label for='body'>Reason for contacting us:</label>
    <br />
    <textarea name='body' class='input' cols='30' rows='5'></textarea>
    <br />
    <input type='submit' value='Submit' />
    </form>
";
$petitionFootNote = "If there is an issue, please be as descriptive as possible! The administration will have a much easier time when you give more info, resulting in faster response times.";
$petition = modulehook(
    'petition-form',
    [
        'header' => $petitionHeader,
        'form' => $petitionForm,
        'footnote' => $petitionFootNote
    ]
);
if (empty($post)) {
    output(
        "`^%s `n`n`@%s`0 `n`Q%s`0",
        $petition['header'],
        $petition['form'],
        $petition['footnote'],
        true
    );
}
else {
    $ip = substr($session['user']['lastip'], 0, -2);
    $lgi = isset($_COOKIE['lgi']) ? addslashes($_COOKIE['lgi']) : '';
    $sql = db_query(
        "SELECT count(petitionid) AS count FROM $petitions
        WHERE (ip LIKE '$ip%' OR id = '$lgi')
        AND date > '" . date('Y-m-d H:i:s', strtotime('-1 day')) . "'
        AND status != '0'"
    );
    $row = db_fetch_assoc($sql);
    if ($row['count'] >= 5 && !($session['user']['superuser'] & SU_EDIT_PETITIONS)) {
        output(
            "`4We are sorry, but in an effort to keep spamming of the petitions to a minimum, we ask that users limit themselves to five petitions a day. Please try again tomorrow, or when one of your current issues is resolved."
        );
    }
    else {
        $date = date('Y-m-d H:i:s');
        $post['cancelpetition'] = false;
        $post['cancelreason'] = '';
        $post = modulehook('addpetition', $post);
        $closingDate = date('Y-m-d H:i:s', strtotime('+40 days'));
        if ($post['cancelpetition'] == true) {
            output($post['cancelreason']);
        } else {
            $post['body'] = addslashes($post['body']);
            $lgi = isset($_COOKIE['lgi']) ? addslashes($_COOKIE['lgi']) : '';
            db_query(
                "INSERT INTO $petitions (`author`, `date`, `body`, `pageinfo`,
                `ip`, `id`, `closedate`)
                VALUES (
                    '{$session['user']['acctid']}',
                    '$date',
                    '" . addslashes($post['body']) . "',
                    '" . addslashes($sessionJson) . "',
                    '$ip',
                    '$lgi',
                    '$closingDate'
                )"

            );
            invalidatedatacache('petition_counts');
            output("`@Your petition has been sent!`n");
            output("As soon as the administration sees your petition for help, they will answer it immediately. Please give time for them to handle your issue and have a nice day!");
            if (getsetting('emailpetitions', 0)) {
                $name = translate_inline(full_sanitize($post['name']));
                $url = getsetting('serverurl', 'http://lotgd.net');
                $body = translate_inline($post['body']);
                mail(getsetting('gameadminemail', 'admin@lotgd.net'), "New petition from $name!", $body);
            }
        }
    }
}
popup_footer();
