<?php

declare(strict_types = 1);

function reltime(int $date, bool $short = true)
{
    $now = strtotime('now');
    $x = abs($now - $date);
    $d = floor($x / 86400);
    $x = $x % 86400;
    $h = floor($x / 3600);
    $x = $x % 3600;
    $m = floor($x / 60);
    $x = $x % 60;
    $s = $x;
    $array = [
        'hour' => 'h',
        'minute' => 'm',
        'second' => 's',
    ];
    if (!$short) {
        array_unshift($array, 'd');
    }
    foreach ($array as $long => $short) {
        $o .= $$short . $short . " ";
    }
    return trim($o);
}

function relativedate(string $inDate): string
{
    $last = intval((strtotime('now') - strtotime($inDate)) / 86400, 0) . ' days';
    if (strtotime($last) !== false) {
        $date = date('Y-m-d', strtotime($last));
    }
    if ($inDate == '0000-00-00 00:00:00') {
        return 'Never';
    }
    if (date('Y-m-d', strtotime($inDate)) == date('Y-m-d', strtotime('-1 day'))) {
        return 'Yesterday';
    }
    if ($date === date('Y-m-d', strtotime('now'))) {
        return 'Today';
    }
    return sprintf(
        '%s days',
        round((strtotime('now') - strtotime($inDate)) / 86400, 0)
    );
}

function checkday(bool $force = true): bool
{
    global $session, $revertsession, $REQUEST_URI, $timeDetails;
    output_notl('<!--checkday()-->', true);
    $timeDetails = gametimedetails();
    $lastOn = strtotime($session['user']['laston'] ?: 'now');
    if ($session['user']['loggedin']) {
        if (getsetting('nextDay', 0) <= $lastOn
            && $session['user']['loggedin']
        ) {
            savesetting('nextDay', $timeDetails['nextdaytime']);
            //debug($timeDetails);
            resetLastHits();
        }
    }
    if (runNewDay()) {
        if ($force && $session['user']['loggedin']) {
            $session = $revertsession;
            $session['user']['restorepage'] = $REQUEST_URI;
            $session['allowednavs'] = [];
            addnav('', 'newday.php');
            redirect('newday.php');
        }
        return true;
    } else {
        return false;
    }
}

function resetLastHits(): bool
{
    $accounts = db_prefix('accounts');
    db_query("UPDATE $accounts SET lasthit = '0000-00-00 00:00:00'");
    return false;
}

function runNewDay(): bool
{
    global $session;
    if ($session['user']['lasthit'] == "0000-00-00 00:00:00") {
        return true;
    }
    return false;
}

function getgametime(): string
{
    return date('g:i a', gametime());
}

function gametime(): int
{
    return convertgametime(strtotime('now'));
}

function convertgametime(int $inTime): int
{
    $inTime -= getsetting('gameoffsetseconds', 0);
    return strtotime(date('Y-m-d H:i:s O', $inTime));
}

function gametimedetails(): array
{
    $gameTime = gametime();
    $today = strtotime('today');
    $tomorrow = strtotime('tomorrow');
    $daysPerDay = getsetting('daysperday', 4);
    $secondsPerDay = intval(86400 / $daysPerDay);
    $timeRanToday = $gameTime - $today;
    $timeTillTomorrow = $tomorrow - $gameTime;
    $lastDay = floor($timeRanToday / $secondsPerDay) * $secondsPerDay;
    $nextDay = $lastDay + $secondsPerDay;
    $details = [
        'now' => date('Y-m-d H:i:s'),
        'gametime' => $gameTime,
        'daysperday' => $daysPerDay,
        'secsperday' => $secondsPerDay,
        'today' => $today,
        'tomorrow' => $tomorrow,
        'lastdaytime' => $lastDay + $today,
        'nextdaytime' => $nextDay + $today,
        'secssofartoday' => $timeRanToday % $secondsPerDay,
        'secstotomorrow' => $nextDay - $timeRanToday,
        'realsecssofartoday' => $timeRanToday,
        'realsecstotomorrow' => $timeTillTomorrow,
    ];
    return $details;
}

function secondstonextgameday($details = false): int
{
    if ($details === false) {
        $details = gametimedetails();
    }
    return intval(strtotime("now + {$details['secstotomorrow']} seconds"));
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
        $divided = $seconds / $time;
        if ($divided > 1) {
            if ($unit != 'ms' && $unit != 's') {
                $seconds = $seconds % $time;
            } elseif ($unit == 's') {
                $seconds = $seconds - floor($divided);
            } elseif ($unit == 'ms') {
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
