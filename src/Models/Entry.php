<?php


namespace Bitsbytes\Models;


use DateTime;

class Entry
{
    public ?int $eid;
    public ?string $title;
    public ?string $slug;
    public ?string $url;
    public ?string $text;
    public ?DateTime $date;

    public function __construct(?int $eid, ?string $title, ?string $slug, ?string $url, ?string $text, ?DateTime $date)
    {
        $this->eid = $eid;
        $this->title = $title;
        $this->slug = $slug;
        $this->url = $url;
        $this->text = $text;
        $this->date = $date;
    }

    /**
     * @return array<int|string|DateTime|null>
     */
    public function toArray(): array
    {
        return [
            'eid' => $this->eid,
            'title' => $this->title,
            'slug' => $this->slug,
            'url' => $this->url,
            'text' => $this->text,
            'date' => $this->date,
        ];
    }

    public function __toString()
    {
        if ($this->date === null) {
            $date_formatted = '';
        } else {
            $date_formatted = $this->date->format(\DateTimeInterface::ATOM);
        }

        return "Entry " . $this->eid . ":" .
            "\nTitle: " . $this->title .
            "\nSlug: " . $this->slug .
            "\nURL: " . $this->url .
            "\nText: " . $this->text .
            "\nDate: " . $date_formatted;
    }
}