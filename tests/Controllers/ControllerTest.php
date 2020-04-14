<?php

declare(strict_types=1);

namespace Tests\Controllers;

use Bitsnbytes\Controllers\Controller;
use Bitsnbytes\Models\Template\Renderer;
use DateTime;
use Exception;
use Http\Request;
use Http\Response;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class ControllerTest extends TestCase
{
    private MockObject $controller;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $renderer = $this->createMock(Renderer::class);
        $this->controller = $this->getMockForAbstractClass(Controller::class, array($request, $response, $renderer));
    }

    /**
     * @dataProvider urlProvider
     *
     * @param $inputUrl
     * @param $expectedSanitizedUrl
     */
    public function testFilterUrl($inputUrl, $expectedSanitizedUrl)
    {
        $sanitizedUrl = $this->controller->filterUrl($inputUrl);
        $this->assertSame($expectedSanitizedUrl, $sanitizedUrl);
    }

    public function urlProvider(): array
    {
        return [
            [
                'http://google.de/',
                'http://google.de/'
            ],
            [ // only http/https allowed
                'htstp://google.de/',
                null
            ],
            [
                'https://säfte.com/',
                'https://säfte.com/'
            ],
            [ // only http/https allowed
                'javascript://test%0Aalert(321)',
                null
            ],
            [ // XSS is filtered before output
                'http://example.com/"><script>alert("xss")</script>',
                'http://example.com/"><script>alert("xss")</script>'
            ],
        ];
    }

    public function slugProvider(): array
    {
        return [
            [ // [a-z0-9_-] is allowed
                'abc_de-fg1',
                'abc_de-fg1'
            ],
            [ // only lowercase allowed
                'AbcdEf',
                'abcdef'
            ],
            [ // replace umlauts, etc.
                'abäcd',
                'abaecd'
            ],
            [ // replace umlauts, etc.
                'abæcd',
                'abaecd'
            ],
            [ // replace umlauts, etc.
                'abácd',
                'abacd'
            ],
            [ // replace umlauts, etc.
                'abÄcd',
                'abaecd'
            ],
            [ // remove special chars
                'ab&cd',
                'ab-cd'
            ],
            [ // remove special chars
                '\'abcd',
                '-abcd'
            ],
            [ // truncate to 30 chars
                'abcdefghijklmnopqrstuvwxyz0123456789',
                'abcdefghijklmnopqrstuvwxyz0123'
            ],
        ];
    }

    /**
     * @dataProvider slugProvider
     *
     * @param $inputSlug
     * @param $expectedSanitizedSlug
     *
     * @throws Exception
     */
    public function testFilterSlug($inputSlug, $expectedSanitizedSlug)
    {
        $sanitizedSlug = $this->controller->filterSlug($inputSlug);
        $this->assertSame($expectedSanitizedSlug, $sanitizedSlug);
    }

    /**
     * @dataProvider dateProvider
     *
     * @param $inputDate
     * @param $expectedSanitizedDate
     */
    public function testFilterDate($inputDate, $expectedSanitizedDate)
    {
        $sanitizedDate = $this->controller->filterDate($inputDate);
        $this->assertSame($expectedSanitizedDate, $sanitizedDate);
    }

    public function dateProvider(): array
    {
        return [
            [
                '',
                ''
            ],
            [
                '2020-01-01',
                '2020-01-01'
            ],
            [ // valid date, but we only care about years <= 9999
                '20000-01-01',
                null
            ],
            [ // missing leading zeros
                '2000-1-1',
                null
            ],
            [ // missing leading digits for year
                '99-01-01',
                null
            ],
            [ // addition characters not part of the date
                "2020-01-01\n",
                null
            ],
            [ // date doesn't exist
                '2020-02-30',
                null
            ],
            [ // leap year
                '2020-02-29',
                '2020-02-29'
            ],
            [ // not a leap year, date doesn't exist
                '2021-02-29',
                null
            ],
        ];
    }

    /**
     * @dataProvider timeProvider
     *
     * @param $inputTime
     * @param $expectedSanitizedTime
     */
    public function testFilterTime($inputTime, $expectedSanitizedTime)
    {
        $sanitizedTime = $this->controller->filterTime($inputTime);
        $this->assertSame($expectedSanitizedTime, $sanitizedTime);
    }

    public function timeProvider(): array
    {
        return [
            [
                '',
                ''
            ],
            [
                '10:10',
                '10:10:00'
            ],
            [
                '10:10:10',
                '10:10:10'
            ],
            [
                '24:00',
                null
            ],
            [
                '22:60',
                null
            ],
            [
                '10:10:10.100',
                null
            ],
            [
                '01:20 pm',
                null
            ],
            [
                "10:10\n",
                null
            ],
        ];
    }


    /**
     * @dataProvider dateAndTimeProvider
     *
     * @param               $inputDate
     * @param               $inputTime
     * @param DateTime|null $expectedDateTime
     */
    public function testCreateDateTimeFromDateAndTime($inputDate, $inputTime, ?DateTime $expectedDateTime)
    {
        $createdDateTime = $this->controller->createDateTimeFromDateAndTime($inputDate, $inputTime);
        $this->assertEquals($expectedDateTime, $createdDateTime); // assertEquals as we are comparing objects
    }

    public function dateAndTimeProvider()
    {
        return [
            [
                '2020-01-01',
                '10:10:00',
                DateTime::createFromFormat('Y-m-d\TH:i:s', '2020-01-01T10:10:00'),
            ],
            [ // leap year
                '2020-02-29',
                '10:10:10',
                DateTime::createFromFormat('Y-m-d\TH:i:s', '2020-02-29T10:10:10'),
            ],
            [ // date does not exist
                '2020-02-30',
                '10:10:10',
                null,
            ],
            [ // seconds missing
                '2020-01-01',
                '10:10',
                null,
            ],
            [
                null,
                '10:10:00',
                null,
            ],
            [
                '2020-01-01',
                null,
                null,
            ],
        ];
    }

}