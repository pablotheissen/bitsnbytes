<?php


namespace Tests\Controllers;


use AltoRouter;
use Bitsbytes\Controllers\EntryController;
use Bitsbytes\Models\EntryRepository;
use Bitsbytes\Template\Renderer;
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
        $router = $this->createMock(AltoRouter::class);
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $renderer = $this->createMock(Renderer::class);
        $this->entry_controller = new EntryController(
            $this->entry_repository,
            $router,
            $request,
            $response,
            $renderer
        );
    }

    /**
     * @dataProvider titleSlugProvider
     *
     */
    public function testCreateSlugFromTitle($inputTitle, $expectedSlug)
    {
        $this->entry_repository->expects($this->once())
            ->method('checkIfSlugExists')
            ->with($this->equalTo($expectedSlug))
            ->will($this->returnValue(false));
        $createdSlug = $this->entry_controller->createSlugFromTitle($inputTitle);
        $this->assertEquals($expectedSlug, $createdSlug);
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
                'aeoeueaeoeuessssaeoe-'
            ],
            [
                'This is a test?',
                'this-is-a-test-'
            ],
            [
                'This is a test with more than thirty characters',
                'this-is-a-test-with-more-than-'
            ],
        ];
    }

    public function testCreateSlugFromTitleEmptyReturnsEmpty()
    {
        $this->entry_repository->expects($this->never())
            ->method('checkIfSlugExists');
        $createdSlug = $this->entry_controller->createSlugFromTitle('');
        $this->assertEquals('', $createdSlug);
    }
}