<?php
declare(strict_types = 1);

function reltime(int $date, bool $short = true)
{
    $now = strtotime('now');
    $x = abs($now - $date);
    $d = ($x/86400);
    $x = $x % 86400;
    $h = ($x/3600);
    $x = $x % 3600;
    $m = ($x/60);
    $x = $x % 60;
    $s = ($x);
    if ($short) {
        $array = [
            'day' => 'd',
            'hour' => 'h',
            'minute' => 'm',
            'second' => 's',
        ];
        $array = translate_inline($array, 'datetime');
        if ($d > 0) {
            $o = $d . $array['d'] . ($h > 0 ? $h . $array['h'] : '');
        }
        else if ($h > 0) {
            $o = $h . $array['h'] . ($m > 0 ? $m . $array['m'] : '');
        }
        else if ($m > 0) {
            $o = $m . $array['m'] . ($s > 0 ? $s . $array['s'] : '');
        }
        else {
            $o = $s . $array['s'];
        }
    }
    else {
        $array = [
            'day' => 'day',
            'days' => 'days',
            'hour' => 'hour',
            'hours' => 'hours',
            'minute' => 'minutes',
            'minutes' => 'minutes',
            'second' => 'second',
            'seconds' => 'seconds',
        ];
        $array = translate_inline($array, 'datetime');
        if ($d > 0) {
            $o = "$d ".($d>1?$array['days']:$array['day']).($h>0?", $h ".($h>1?$array['hours']:$array['hour']):"");
        }
        else if ($h > 0) {
            $o = "$h " . ($h > 1 ? $array['hours'] : $array['hour']) . ($m > 0 ? ", $m " . ($m > 1 ? $array['minutes'] : $array['minute']) : '');
        }
        else if ($m > 0) {
            $o = "$m " . ($m > 1 ? $array['minutes'] : $array['minute']) . ($s > 0 ? ", $s " . ($s > 1 ? $array['seconds'] : $array['second']) : '');
        }
        else {
            $o = "$s " . ($s > 0 ? $array['seconds'] : $array['second']);
        }
    }
    return $o;
}

function relativedate(string $indate): string
{
    $lastOn = round((strtotime('now') - strtotime($indate)) / 86400, 0) . 'days';
    tlschema('datetime');
    if (substr($lastOn, 0, 2) == '1 ') {
        $lastOn = translate_inline('1 day');
    }
    else if (date('Y-m-d', strtotime($lastOn)) == date('Y-m-d')) {
        $lastOn = translate_inline('Today');
    }
    else if (date('Y-m-d', strtotime($lastOn)) == date('Y-m-d', strtotime('-1 day'))) {
        $lastOn = translate_inline('Yesterday');
    }
    else if (strpos($indate, '0000-00-00') !== false){
        $lastOn = translate_inline('Never');
    }
    else {
        $lastOn = sprintf_translate(
            '%s days',
            round((strtotime('now') - strtotime($indate)) / 86400, 0)
        );
        rawoutput(tlbutton_clear());
    }
    tlschema();
    return $lastOn;
}

function checkday(bool $force = true): bool
{
    global $session, $revertsession, $REQUEST_URI;
    output_notl('<!--checkday()-->', true);
    if (is_new_day()) {
        if ($force && $session['user']['loggedin']) {
            $session = $revertsession;
            $session['user']['restorepage'] = $REQUEST_URI;
            $session['allowednavs'] = [];
            addnav('', 'newday.php');
            redirect('newday.php');
        }
        return true;
    }
    else {
        return false;
    }
}

function is_new_day(float $now = 0): bool
{
    global $session;
    if (!getSession('loggedin')) {
        return false;
    }
    if ($session['user']['lasthit'] == '0000-00-00 00:00:00') {
        return true;
    }
    $gameTime = gmdate('Y-m-d', gametime());
    $lastHit = gmdate(
        'Y-m-d',
        convertgametime(strtotime("{$session['user']['lasthit']} +0000"))
    );
    if ($gameTime != $lastHit) {
        return true;
    }
    return false;
}

function getgametime(): string
{
    return gmdate('g:i a', gametime());
}

function gametime(): int
{
    $time = convertgametime(strtotime('now'));
    return $time;
}

function convertgametime(int $inTime, bool $debug = false): int
{
    $inTime -= getsetting('gameoffsetseconds',0);
    $epoch = strtotime(
        getsetting(
            'game_epoch',
            gmdate('Y-m-d 00:00:00 O', strtotime('-30 days'))
        )
    );
    $now = strtotime(gmdate('Y-m-d H:i:s O', $inTime));
    $logdTimestamp = (($now - $epoch) * getsetting('daysperday', 4));
    if ($debug) {
        debug(
            "Game Timestamp: %s, which makes it %s.",
            $logdTimestamp,
            gmdate('Y-m-d H:i:s', $logdTimestamp)
        );
    }
    return $logdTimestamp;
}

function gametimedetails(): array
{
    $gameTime = gametime();
    $today = strtotime(gmdate('Y-m-d 00:00:00 O'), $gameTime);
    $tomorrow = strtotime(gmdate('Y-m-d 00:00:00 O') . '+1 day', $gameTime);
    $daysPerDay = getsetting('daysperday', 4);
    $details = [
        'now' => date('Y-m-d H:i:s'),
        'gametime' => $gameTime,
        'daysperday' => $daysPerDay,
        'secsperday' => (86400 / $daysPerDay),
        'today' => $today,
        'tomorrow' => $tomorrow,
        'secssofartoday' => ($gameTime - $today),
        'secstotomorrow' => ($tomorrow - $gameTime),
        'realsecssofartoday' => (($gameTime - $today) / $daysPerDay),
        'realsecstotomorrow' => (($tomorrow - $gameTime) / $daysPerDay),
        'dayduration' => (($tomorrow - $today) / $daysPerDay),
    ];
    return $details;
}

function secondstonextgameday($details = false): int
{
    if ($details === false) {
        $details = gametimedetails();
    }
    return strtotime("{$details['now']} + {$details['realsecstotomorrow']} seconds");
}

function getmicrotime(): float
{
    list($usec, $sec) = explode(' ', microtime());
    return $usec + $sec;
}


function dhms(float $seconds, bool $ms = false): string
{
    $times = [
        604800 => 'w',
        86400 => 'd',
        3600 => 'h',
        60 => 'm',
        1 => 's',
        '0.001' => 'ms',
    ];
    $return = '';
    foreach ($times as $time => $unit) {
        $divided = $seconds/$time;
        if ($divided > 1) {
            if ($unit != 'ms' && $unit != 's') {
                $seconds = $seconds % $time;
            }
            else if ($unit == 's') {
                $seconds = $seconds - floor($divided);
            }
            else if ($unit == 'ms') {
                $seconds = 0;
            }
            $return .= round($divided, 0) . "$unit ";
        }
    }
    if ($ms == false) {
        $explode = explode(' ', trim($return));
        array_pop($explode);
        $return = implode(' ', $explode);
    }
    return $return;
}
