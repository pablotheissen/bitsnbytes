<?php


namespace Tests\Controllers;


use AltoRouter;
use Bitsnbytes\Controllers\EntryController;
use Bitsnbytes\Models\Entry\EntryRepository;
use Bitsnbytes\Models\Tag\TagRepository;
use Bitsnbytes\Models\Template\Renderer;
use Erusev\Parsedown\Parsedown;
use Exception;
use Http\Request;
use Http\Response;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class EntryControllerTest extends TestCase
{
    private EntryController $entry_controller;
    private MockObject $entry_repository;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->entry_repository = $this->createMock(EntryRepository::class);
        $this->tag_repository = $this->createMock(TagRepository::class);
        $router = $this->createMock(AltoRouter::class);
        $parsedown = new Parsedown(); // can't be mocked
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $renderer = $this->createMock(Renderer::class);
        $this->entry_controller = new EntryController(
            $this->entry_repository,
            $this->tag_repository,
            $router,
            $parsedown,
            $request,
            $response,
            $renderer
        );
    }

    /**
     * @dataProvider titleSlugProvider
     *
     * @param $inputTitle
     * @param $expectedSlug
     *
     * @throws Exception
     */
    public function testCreateSlugFromTitle($inputTitle, $expectedSlug)
    {
        $this->entry_repository->expects($this->once())
            ->method('checkIfSlugExists')
            ->with($this->equalTo($expectedSlug))
            ->will($this->returnValue(false));
        $actual_slug = $this->entry_controller->createSlugFromTitle($inputTitle);
        $this->assertSame($expectedSlug, $actual_slug);
    }

    public function titleSlugProvider(): array
    {
        return [
            [
                'This is a test',
                'this-is-a-test'
            ],
            [
                'äöüÄÖÜßẞæœ€',
                'aeoeueaeoeuessssaeoe'
            ],
            [
                'This is a test?',
                'this-is-a-test'
            ],
            [
                'This is a test with more than thirty characters',
                'this-is-a-test-with-more-than'
            ],
            [ // avoid double dashes when converting special chars
                'PHP: The Right Way',
                'php-the-right-way'
            ],
            [ // avoid double and leading/trailing dashes when converting special chars
                '?»PHP: »"The Right Way"«',
                'php-the-right-way'
            ],
            [ // avoid double dashes when converting special chars and still truncate to 30 chars
                'a: bcdefghijklmnopqrstuvwxyz0123456789',
                'a-bcdefghijklmnopqrstuvwxyz012'
            ],
        ];
    }

    public function testCreateSlugFromTitleEmptyReturnsEmpty()
    {
        $this->entry_repository->expects($this->never())
            ->method('checkIfSlugExists');
        $actual_slug = $this->entry_controller->createSlugFromTitle('');
        $this->assertSame('', $actual_slug);
    }

    public function testCreateSlugFromTitleWhenSlugExists()
    {
        $inputTitle = 'Test';
        $proposedSlug = 'test';
        $expectedSlug = 'test-2';

        $this->entry_repository->expects($this->exactly(2))
            ->method('checkIfSlugExists')
            ->will(
                $this->returnValueMap(
                    [
                        [$proposedSlug, true],
                        [$expectedSlug, false],
                    ]
                )
            );
        $actual_slug = $this->entry_controller->createSlugFromTitle($inputTitle);
        $this->assertSame($expectedSlug, $actual_slug);
    }

    public function testCreateSlugFromTitleWhenSlugExistsTwice()
    {
        $inputTitle = 'Test';
        $proposedSlug1 = 'test';
        $proposedSlug2 = 'test-2';
        $expectedSlug = 'test-3';

        $this->entry_repository->expects($this->exactly(3))
            ->method('checkIfSlugExists')
            ->will(
                $this->returnValueMap(
                    [
                        [$proposedSlug1, true],
                        [$proposedSlug2, true],
                        [$expectedSlug, false],
                    ]
                )
            );
        $actual_slug = $this->entry_controller->createSlugFromTitle($inputTitle);
        $this->assertSame($expectedSlug, $actual_slug);
    }
}