<?php

declare(strict_types = 1);

function soap(string $input, bool $debug = false, bool $skiphook = false): string
{
    global $session;
    include_once "lib/sanitize.php";
    include_once 'lib/sanitize.php';
    $final_output = $input;
    $output = full_sanitize($input);
    $mix_mask = str_pad('', strlen($output), 'X');
    if (getsetting('soap', 1)) {
        $search = nasty_word_list();
        $exceptions = array_flip(good_word_list());
        $changed_content = false;
        while (list($key, $word) = each($search)) {
            do {
                if ($word > '') {
                    $times = @preg_match_all($word, $output, $matches);
                } else {
                    $times = 0;
                }
                for ($x = 0; $x < $times; $x++) {
                    if (strlen($matches[0][$x]) < strlen($matches[1][$x])) {
                        $shortword = $matches[0][$x];
                        $longword = $matches[1][$x];
                    } else {
                        $shortword = $matches[1][$x];
                        $longword = $matches[0][$x];
                    }
                    if (isset($exceptions[strtolower($longword)])) {
                        $x--;
                        $times--;
                        if ($debug) {
                            output(
                                "This word is ok because it was caught by an exception: `b`^%s`7`b`n",
                                $longword
                            );
                        }
                    } else {
                        if ($debug) {
                            output(
                                "`7This word is not ok: \"`%%s`7\"; it blocks on the pattern `i%s`i at \"`\$%s`7\".`n",
                                $longword,
                                $word,
                                $shortword
                            );
                        }
                        $len = strlen($shortword);
                        $pad = str_pad('', $len, '_');
                        $p = strpos($output, $shortword);
                        $output = substr($output, 0, $p) . $pad . substr($output, $p + $len);
                        $mix_mask = substr($mix_mask, 0, $p) . $pad . substr($mix_mask, $p + $len);
                        $changed_content = true;
                    }
                }
            } while ($times > 0);
        }
        $y = 0;
        $pad = '#@%$!';
        for ($x = 0; $x < strlen($mix_mask); $x++) {
            while (substr($final_output, $y, 1) == '`') {
                $y += 2;
            }
            if (substr($mix_mask, $x, 1) == '_') {
                $final_output = substr($final_output, 0, $y) .
                        substr($pad, $x % strlen($pad), 1) .
                        substr($final_output, $y + 1);
            }
            $y++;
        }
        if ($session['user']['superuser'] & SU_EDIT_COMMENTS && $changed_content) {
            output(
                "`0The filter would have tripped on \"`#%s`0\" but since you're a moderator, I'm going to be lenient on you.  The text would have read, \"`#%s`0\"`n`n",
                $input,
                $final_output
            );
            return $input;
        } else {
            if ($changed_content && !$skiphook) {
                modulehook('censor', ['input' => $input]);
            }
            return $final_output;
        }
    } else {
        return $final_output;
    }
}

function good_word_list(): array
{
    $nastyWords = db_prefix('nastywords');
    $sql = db_query_cached(
        "SELECT * FROM $nastyWords WHERE type = 'good'",
        'goodwordlist'
    );
    $row = db_fetch_assoc($sql);
    if (!empty($row['words'])) {
        return explode(' ', $row['words']);
    } else {
        return [];
    }
}

function nasty_word_list(): array
{
    $search = datacache('nastywordlist', 86400);
    if ($search !== false && is_array($search)) {
        return $search;
    }
    $nastyWords = db_prefix('nastywords');
    $sql = db_query("SELECT * FROM $nastyWords WHERE type = 'nasty'");
    $row = db_fetch_assoc($sql);
    $search = $row['words'];
    $search = preg_replace('/(?<=.)(?<!\\\\)\'(?=.)/', '\\\'', $search);
    $search = str_replace('a', '[a4@ªÀÁÂÃÄÅàáâãäå]', $search);
    $search = str_replace('b', '[bß]', $search);
    $search = str_replace('d', '[dÐÞþ]', $search);
    $search = str_replace('e', '[e3ÉÊËÈèéêë]', $search);
    $search = str_replace('n', '[nÑñ]', $search);
    $search = str_replace('o', '[o°º0ÒÓÔÕÖØðòóôõöø¤]', $search);
    $search = str_replace('p', '[pÞþ¶]', $search);
    $search = str_replace('r', '[r®]', $search);
    $search = preg_replace('/(?<!\\\\)s/', '[sz$§]', $search);
    $search = str_replace('t', '[t7+]', $search);
    $search = str_replace('u', '[uÛÜÙÚùúûüµ]', $search);
    $search = str_replace('x', '[x×¤]', $search);
    $search = str_replace('y', '[yÝ¥ýÿ]', $search);
    $search = str_replace('l', '[l1!£]', $search);
    $search = str_replace('i', '[li1!¡ÌÍÎÏìíîï]', $search);
    $search = str_replace('k', 'c', $search);
    $search = str_replace('c', '[c\\(kç©¢]', $search);
    $start = "'\\b";
    $end = "\\b'iU";
    $ws = "[^[:space:]\\t]*";
    $search = preg_replace('\'(?<!\\*) \'', ")+$end ", $search);
    $search = preg_replace('\' (?!\\*)\'', " $start(", $search);
    $search = str_replace('* ', ")+$ws$end ", $search);
    $search = str_replace(' *', " $start$ws(", $search);
    $search = "$start(" . trim($search) . ")+$end";
    $search = str_replace("$start()+$end", '', $search);
    $search = explode(' ', $search);
    updatedatacache('nastywordlist', $search);
    return $search;
}
