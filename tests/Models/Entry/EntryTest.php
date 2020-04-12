<?php

declare(strict_types=1);


namespace Tests\Models\Entry;

use Bitsbytes\Models\Entry;
use Bitsbytes\Models\Tag\Tag;
use DateTime;
use PHPUnit\Framework\TestCase;

class EntryTest extends TestCase
{

    public function testToArray()
    {
        $datetime_example = DateTime::createFromFormat('Y-m-d\TH:i:s', '2020-04-12T10:10:10');
        $eid_example = 3;
        $title_example = 'Entry Title';
        $slug_example = 'entry-slug';
        $url_example = 'https://example.com';
        $text_example = 'Beispiel-Text';

        $tag_array = [
            'tid' => 5,
            'slug' => 'slug',
            'title' => 'Slug',
        ];
        $tag = $this->createMock(Tag::class);
        $tag->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue($tag_array));

        $entry = new Entry(
            $eid_example,
            $title_example,
            $slug_example,
            $url_example,
            $text_example,
            $datetime_example,
            [$tag]
        );
        $expected_entry_array = [
            'eid' => $eid_example,
            'title' => $title_example,
            'slug' => $slug_example,
            'url' => $url_example,
            'text' => $text_example,
            'date' => $datetime_example,
            'tags' => [$tag_array],
        ];
        $actual_entry_array = $entry->toArray();

        $this->assertSame($expected_entry_array, $actual_entry_array);
    }
}
