<?php

declare(strict_types = 1);

/**
 * Adds a news item for the current user
 *
 * @param string $text Line of text for the news.
 * @param array $options List of options, including replacements, to modify the acctid, date, or hide from biographies.
 * @todo Change the date format from Y-m-d to Y-m-d H:i:s.
 */
function addnews(string $text = '', array $options = []): void
{
    global $translation_namespace, $session;
    $options = modulehook('addnews', $options);
    $news = db_prefix('news');
    $date = ($options['date']) ?? date('Y-m-d');
    unset($options['date']);
    $acctid = ($options['acctid']) ?? $session['user']['acctid'];
    unset($options['acctid']);
    $hide = isset($options['hide']);
    unset($options['hide']);
    $text = str_replace("`%", "`%%", $text);
    $text = vsprintf($text, $options);
    if (!$hide) {
        $sql = db_query(
                "INSERT INTO $news (newstext, newsdate, accountid, tlschema)
            VALUES ('$text', '$date', '$acctid', '$translation_namespace')"
        );
    } else {
        $sql = db_query(
                "INSERT INTO $news (newstext, newsdate, tlschema)
            VALUES ('$text', '$date', '$translation_namespace')"
        );
    }
}
