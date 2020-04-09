<?php


namespace Tests\Controllers;


use AltoRouter;
use Bitsbytes\Controllers\EntryController;
use Bitsbytes\Models\EntryRepository;
use Bitsbytes\Template\Renderer;
use Http\Request;
use Http\Response;
use Tests\TestCase;

class EntryControllerTest extends TestCase
{
    private EntryController $entry_controller;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $entry_repository = $this->createMock(EntryRepository::class);
        $router = $this->createMock(AltoRouter::class);
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $renderer = $this->createMock(Renderer::class);
        $this->entry_controller = new EntryController(
            $entry_repository,
            $router,
            $request,
            $response,
            $renderer
        );
    }


    public function titleSlugProvider(): array
    {
        return [
            [
                '',
                ''
            ],
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
            // TODO: check title > 30 chars
            // TODO: add more tests
        ];
    }

    /**
     * @dataProvider titleSlugProvider
     *
     */
    public function testCreateSlugFromTitle($inputTitle, $expectedSlug)
    {
        $createdSlug = $this->entry_controller->createSlugFromTitle($inputTitle);
        $this->assertEquals($expectedSlug, $createdSlug);
    }

}