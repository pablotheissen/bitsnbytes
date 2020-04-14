<?php

declare(strict_types=1);

namespace Bitsbytes\Models\Tag;


class Tag
{
    public ?int $tid;
    public ?string $slug;
    public ?string $title;

    public function __construct(?int $tid, string $slug, string $title)
    {
        $this->tid = $tid;
        $this->slug = $slug;
        $this->title = $title;
    }

    /**
     * @return array<int|string|null>
     */
    public function toArray(): array
    {
        return [
            'tid' => $this->tid,
            'slug' => $this->slug,
            'title' => $this->title,
        ];
    }

    public function __toString()
    {
        return "Tag #" . $this->tid . ":" .
            "\nSlug: " . $this->slug .
            "\nTitle: " . $this->title;
    }
}
