<?php

declare(strict_types=1);


namespace Bitsnbytes\Models\Remote;


use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Class Remote
 *
 * Access remote websites and retrieve data bits.
 *
 * @package Bitsnbytes\Models\Remote
 */
class Remote
{
    /**
     * Fetch title and description of a remote website.
     * The title es fetched from the title tag, the description from the meta[name="description"] tag.
     *
     * @param string $url URL of remote website
     * @param string $default_title
     * @param string $default_description
     *
     * @return array<string>
     */
    public function fetchTitleAndDescription(
        string $url,
        string $default_title = '',
        string $default_description = ''
    ): array {
        $remote_html_raw = $this->fetchRemoteData($url);
        if ($remote_html_raw === null) {
            return [$default_title, $default_description];
        }

        $dom = $this->parseHTMLToDOMDocument($remote_html_raw);
        if ($dom === null) {
            return [$default_title, $default_description];
        }

        $title = $this->findTitleInDom($dom, $default_title);
        $description = $this->findMetaDescriptionInDom($dom, $default_description);

        return [$title, $description];
    }

    private function fetchRemoteData(string $url): ?string
    {
        $curl_handle = curl_init();
        if ($curl_handle == false) {
            return null;
        }
        curl_setopt($curl_handle, CURLOPT_URL, $url);
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($curl_handle, CURLOPT_TIMEOUT, 2);
        $buffer = curl_exec($curl_handle);
        curl_close($curl_handle);
        if (is_bool($buffer)) {
            return null;
        }
        return $buffer;
    }

    private function parseHTMLToDOMDocument(string $raw_html): ?DOMDocument
    {
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        if($raw_html === '') {
            // DOMDocument::loadHTML can't handle empty string without issuing a PHP Warning
            return null;
        }
        $ret = $doc->loadHTML($raw_html);

        if ($ret === false) {
            return null;
        }

        return $doc;
    }

    private function findTitleInDom(DOMDocument $dom, string $default_title = ''): string
    {
        $title_tags = $dom->getElementsByTagName('title');
        if ($title_tags->length === 0) {
            return $default_title;
        }
        $title_tag = $title_tags->item(0);
        if ($title_tag === null) {
            return $default_title;
        }
        return $title_tag->nodeValue;
    }

    private function findMetaDescriptionInDom(DOMDocument $dom, string $default_description = ''): string
    {
        $meta_tags = $dom->getElementsByTagName('meta');
        if ($meta_tags->length === 0) {
            return $default_description;
        }

        foreach ($meta_tags as $meta_tag) {
            $meta_tag = $this->castDOMNodeToDOMElement($meta_tag);

            if ($meta_tag === null || !$meta_tag->hasAttribute('name') || !$meta_tag->hasAttribute('content')) {
                continue;
            }
            if ($meta_tag->getAttribute('name') !== 'description') {
                continue;
            }
            return $meta_tag->getAttribute('content');
        }
        return $default_description;
    }

    private function castDOMNodeToDOMElement(DOMNode $node): ?DOMElement
    {
        if ($node->nodeType === XML_ELEMENT_NODE) {
            /** @var DOMElement $node */
            return $node;
            // DOMNodeList returns DOMNode, but only DOMNode with nodeType=XML_ELEMENT_NODE are actually DOMElement
            // which has the methods {has|get}Attribute(...)
        } else {
            return null;
        }
    }
}