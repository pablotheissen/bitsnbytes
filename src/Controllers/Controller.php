<?php

declare(strict_types=1);

namespace Bitsnbytes\Controllers;


use DateTime;
use Erusev\Parsedown\Parsedown;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\RouteParserInterface;
use Slim\Views\Twig;
use Transliterator;

abstract class Controller
{
    protected RouteParserInterface $route_parser;
    protected Parsedown $parsedown;
    protected Twig $twig;

    public function __construct(
        Parsedown $parsedown,
        Twig $twig,
        RouteParserInterface $route_parser
    ) {
        $this->parsedown = $parsedown;
        $this->twig = $twig;
        $this->route_parser = $route_parser;
    }

    /**
     * @param string $url User input URL
     *
     * @return string|null Sanitized Url, NULL if sanitizing fails
     */
    public function filterUrl(string $url): ?string
    {
        // filter_var(..., FILTER_VALIDATE_URL) also removes als international domain names (like öko.de) so it
        // currently is not used here
        if (!mb_startswith($url, 'https://') and !mb_startswith($url, 'http://')) {
            return null;
        }
        return $url;
    }

    /**
     * Transform all characters to ASCII codes while trying to correctly replace umlauts, etc. Remove all chars that
     * are
     * not [a-z0-9_-]. Slug is truncated to 30 chars.
     *
     * @param string   $slug     User-input slug
     * @param int|null $truncate Truncate filtered slug to X characters. If omitted or <b>NULL</b> is passed, slug is
     *                           not truncated.
     *
     * @return string|null Sanitized slug
     */
    public function filterSlug(string $slug, int $truncate = null): ?string
    {
        $slug_filtered = $this->removeSpecialCharsAndConvertToLower($slug);
        if ($slug_filtered === null) {
            return null;
        }

        // truncate to X characters. mb_substr() does not truncate when start=0 and length=null
        $slug_filtered = mb_substr($slug_filtered, 0, $truncate);

        return $slug_filtered;
    }

    private function removeSpecialCharsAndConvertToLower(string $str): ?string
    {
        // http://userguide.icu-project.org/transforms/general for info on rules
        $transliterator = Transliterator::createFromRules(
            ':: Any-Latin; :: de-ASCII; :: Latin-ASCII; :: Lower(); [^a-z0-9_-] > \'-\'; ',
            Transliterator::FORWARD
        );
        if ($transliterator === null) {
            return null;
        }

        $str_filtered = $transliterator->transliterate($str);
        if ($str_filtered === false) {
            return null;
        }

        return $str_filtered;
    }

    /**
     * Check if user submitted date conforms to format YYYY-MM-DD
     *
     * @param string $date User submitted date, NOT parsed to DateTimeInterface
     *
     * @return string|null If $date is valid, return $date, if $time is empty, return '', otherwise null
     */
    public function filterDate(string $date): ?string
    {
        if ($date === '') {
            return '';
        }
        // 1: check via regex
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date) !== 1) {
            // preg_match returns int(1) if pattern matches; if something else is returned, return null
            // D modifier: Dollar. Force the a dollar sign, $, to always match end of the string, instead of end of the
            //             line. This option is ignored if the m-flag is set
            return null;
        }
        // 2: check via php internal function if date actually exists (eg. check against Feb 30)
        $parsed_date = DateTime::createFromFormat('Y-m-d', $date);
        if ($parsed_date === false or $date !== $parsed_date->format('Y-m-d')) {
            return null;
        }
        // 3: return unmodified date string if everything looks ok
        return $date;
    }

    /**
     * Check if user submitted time conforms to format HH:MM:SS or HH:MM
     *
     * @param string $time User submitted time
     *
     * @return string|null If $time is valid, return $time in format HH:MM:SS, if $time is empty, return '', otherwise
     *                     null
     */
    public function filterTime(string $time): ?string
    {
        if ($time === '') {
            return '';
        }
        // 1: check via regex
        if (preg_match('/^(?|[01][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/D', $time) !== 1) {
            return null;
        }
        // 2: add seconds
        if (preg_match('/^(?|[01][0-9]|2[0-3]):[0-5][0-9]$/D', $time) === 1) {
            $time = $time . ':00';
        }
        return $time;
    }

    /**
     * Combine a date- and a time-string to a DateTime. Both input strings have to be formatted as YYYY-MM-DD and
     * HH:MM:SS
     *
     * @param string|null $date Date MUST be in format YYYY-MM-DD
     * @param string|null $time Time MUST be in format HH:MM:SS
     *
     * @return DateTime|null
     */
    public function createDateTimeFromDateAndTime(?string $date, ?string $time): ?DateTime
    {
        if ($date === null or $time === null) {
            return null;
        }
        $datetime_string = $date . 'T' . $time;
        $datetime = DateTime::createFromFormat('Y-m-d\TH:i:s', $datetime_string);
        if ($datetime === false or $datetime_string !== $datetime->format('Y-m-d\TH:i:s')) {
            return null;
        }
        return $datetime;
    }

    /**
     * @param array<mixed>      $data
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    protected function returnJsonResponse(array $data, ResponseInterface $response): ResponseInterface
    {
        $json_data = json_encode($data);
        if ($json_data === false) {
            return $response;
        }
        $response->getBody()->write($json_data);
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    protected function error404(ResponseInterface $response): ResponseInterface
    {
        return $this->error($response, 404, 'Sorry, I can’t seem to find what you’re looking for.');
    }

    /**
     * @param ResponseInterface $response
     * @param int               $code
     * @param string            $message
     *
     * @return ResponseInterface
     */
    protected function error(ResponseInterface $response, int $code, string $message = ''): ResponseInterface
    {
        $data = [];

        $data['heading'] = sprintf('Error %d', $code);
        $data['message'] = $message;
        $data['url_newentry'] = $this->route_parser->urlFor('new-entry');

        try {
            $this->twig->render($response, 'error.twig', $data);
        } catch (Exception $exception) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(
                "There was an internal error while building this page. Please inform the site admin."
            );
            $code = 500; // Internal Server Error
            // TODO Log error
        }
        return $response->withStatus($code);
    }
}