<?php

declare(strict_types=1);


namespace Tests\Models\Tag;

use Bitsnbytes\Models\Tag\Tag;
use PHPUnit\Framework\TestCase;

class TagTest extends TestCase
{
    /**
     * @dataProvider tagProvider
     */
    public function test__toString(Tag $tag, $tag_array)
    {
        $expected_string = "Tag #" . $tag_array['tid'] . ":" .
            "\nSlug: " . $tag_array['slug'] .
            "\nTitle: " . $tag_array['title'];
        $this->assertSame($expected_string, (string)$tag);
    }

    /**
     * @dataProvider tagProvider
     */
    public function testToArray(Tag $tag, $expected_array)
    {
        $this->assertSame($expected_array, $tag->toArray());
    }

    public function tagProvider()
    {
        $tid_1 = 5;
        $slug_1 = 'slug';
        $title_1 = 'Slug';
        $tag_1 = new Tag($tid_1, $slug_1, $title_1);

        return [
            [
                $tag_1,
                ['tid' => $tid_1, 'slug' => $slug_1, 'title' => $title_1,]
            ]
        ];
    }
}
