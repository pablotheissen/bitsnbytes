<?php


namespace Bitsnbytes\Models\Entry;


use Bitsnbytes\Models\Tag\Tag;
use Bitsnbytes\Models\User\User;
use DateTime;
use DateTimeInterface;

class Entry
{
    public ?int $eid;
    public ?string $title;
    public ?string $slug;
    public ?string $url;
    public ?string $text;
    public ?DateTime $date;
    public ?User $user;
    public bool $private;
    /** @var array<Tag> */
    public array $tags;

    /**
     * Entry constructor.
     *
     * @param int|null      $eid
     * @param string|null   $title
     * @param string|null   $slug
     * @param string|null   $url
     * @param string|null   $text
     * @param DateTime|null $date
     * @param User|null     $user
     * @param bool          $private
     * @param array<Tag>    $tags
     */
    public function __construct(
        ?int $eid,
        ?string $title,
        ?string $slug,
        ?string $url,
        ?string $text,
        ?DateTime $date,
        ?User $user,
        bool $private,
        array $tags = []
    ) {
        $this->eid = $eid;
        $this->title = $title;
        $this->slug = $slug;
        $this->url = $url;
        $this->text = $text;
        $this->date = $date;
        $this->user = $user;
        $this->private = $private;
        $this->tags = $tags;
    }

    /**
     * @return array<string,array<array<int|string|null>|int|string>|bool|DateTime|int|string|null>
     */
    public function toArray(): array
    {
        $tags_array = [];
        array_walk(
            $this->tags,
            function (Tag $tag) use (&$tags_array): void {
                $tags_array[] = $tag->toArray();
            }
        );

        if($this->user instanceof User) {
            $user_array = $this->user->toArray();
        } else {
            $user_array = null;
        }

        return [
            'eid' => $this->eid,
            'title' => $this->title,
            'slug' => $this->slug,
            'url' => $this->url,
            'text' => $this->text,
            'date' => $this->date,
            'user' => $user_array,
            'private' => $this->private,
            'tags' => $tags_array,
        ];
    }

    public function __toString()
    {
        if ($this->date === null) {
            $date_formatted = '';
        } else {
            $date_formatted = $this->date->format(DateTimeInterface::ATOM);
        }

        return "Entry #" . $this->eid . ":" .
            "\nTitle: " . $this->title .
            "\nSlug: " . $this->slug .
            "\nURL: " . $this->url .
            "\nText: " . $this->text .
            "\nDate: " . $date_formatted;
    }
}
