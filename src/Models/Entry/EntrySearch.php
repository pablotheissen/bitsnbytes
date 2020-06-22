<?php

declare(strict_types=1);


namespace Bitsnbytes\Models\Entry;


use Bitsnbytes\Models\Model;
use PDO;

class EntrySearch extends Model
{
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
    }

    public function generateIndexForEntry(Entry $entry): array
    {
        $index = [];
        $index_title = preg_split('/\s+/', strtolower($entry->title));
        foreach ($index_title as $token) {
            if(array_key_exists($token, $index)) {
                $index[$token] += 10;
            } else {
                $index[$token] = 10;
            }
        }
        $index_slug = preg_split('/\s/', strtolower($entry->slug));
        foreach ($index_slug as $token) {
            if(array_key_exists($token, $index)) {
                $index[$token] += 1;
            } else {
                $index[$token] = 1;
            }
        }
        $index_url = preg_split('/\s/', strtolower($entry->url));
        foreach ($index_url as $token) {
            if(array_key_exists($token, $index)) {
                $index[$token] += 1;
            } else {
                $index[$token] = 1;
            }
        }
        $text = $entry->text;
        $text = str_replace('.', '', $text);
        $text = str_replace(',', '', $text);
        $text = str_replace(':', '', $text);
        $text = str_replace(';', '', $text);
        $text = str_replace('?', '', $text);
        $text = str_replace('!', '', $text);
        $index_content = preg_split('/\s+/', strtolower($text));
        foreach ($index_content as $token) {
            if(array_key_exists($token, $index)) {
                $index[$token] += 1;
            } else {
                $index[$token] = 1;
            }
        }
        return $index;
    }
}