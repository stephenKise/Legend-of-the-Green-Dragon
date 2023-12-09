<?php
declare(strict_types=1);

/**
 * Adds a news item for the current user
 *
 * @param string $text Line of text for the news.
 * @param array $options List of options, including replacements, to modify the acctid, date, or hide from biographies.
 * @todo Change the date format from Y-m-d to Y-m-d H:i:s.
 */
function addnews(string $text = '', array $options = [])
{
    global $translation_namespace, $session;
    $options = modulehook('addnews', $options);
    $news = db_prefix('news');
    $date = $options['date'] ?? date('Y-m-d');
    $acctid = isset($options['hide']) ?
    0 : ($options['acctid'] ?? $session['user']['acctid']);
    foreach (['date', 'hide'] as $key) {
        unset($options[$key]);
    }
    $text = addslashes($text);
    $newsArgs = json_encode($options);
    db_query(
        "INSERT INTO $news
        (newstext, newsdate, accountid, tlschema, arguments)
        VALUES
        ('$text', '$date', '$acctid', '$translation_namespace', '$newsArgs')"
    );
}
