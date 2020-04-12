<?php

declare(strict_types=1);


namespace Tests\Models\Tag;

use Bitsbytes\Models\Tag\TagRepository;
use PDO;
use PHPUnit\Framework\TestCase;

class TagRepositoryTest extends TestCase
{
    private TagRepository $tag_repository;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $pdo = $this->createMock(PDO::class);
        $this->tag_repository = new TagRepository($pdo);
    }

    public function testFetchTagBySlug()
    {
    }

    public function testFindTagsByEntries()
    {
//        TODO: correct test
//        $entry1 = $this->createMock(Entry::class);
//        $entry2 = $this->createMock(Entry::class);
//        $entry1->eid = 3;
//        $entry2->eid = 4;
//        $this->tag_repository->findTagsByEntries([$entry1, $entry2]);
    }
}
