<?php

declare(strict_types=1);


namespace Tests\Models\Entry;

use Bitsnbytes\Models\Entry\Entry;
use Bitsnbytes\Models\Entry\EntrySearch;
use PDO;
use PHPUnit\Framework\TestCase;

class EntrySearchTest extends TestCase
{
    private EntrySearch $entry_search;

    protected function setUp(): void
    {
        parent::setUp();

        $pdo = $this->createMock(PDO::class);
        $this->entry_search = new EntrySearch($pdo);
    }

    public function testGenerateIndexForEntry()
    {
        $entry = new Entry(
            null,
            'Favicon Generator',
            'favicon-generator',
            'https://example.org',
            'The  ultimate favicon generator. Design your icons platform per platform and make them look great everywhere. Including in Google results pages.',
            null,
            []
        );

        $actual = $this->entry_search->generateIndexForEntry($entry);
        $expected = [
            'and' => 1,
            'design' => 1,
            'everywhere' => 1,
            'favicon-generator' => 1,
            'favicon' => 11,
            'generator' => 11,
            'google' => 1,
            'great' => 1,
            'https://example.org' => 1,
            'icons' => 1,
            'in' => 1,
            'including' => 1,
            'look' => 1,
            'make' => 1,
            'pages' => 1,
            'per' => 1,
            'platform' => 2,
            'results' => 1,
            'the' => 1,
            'them' => 1,
            'ultimate' => 1,
            'your' => 1,
        ];

        $this->assertEquals($expected, $actual);
    }
}
