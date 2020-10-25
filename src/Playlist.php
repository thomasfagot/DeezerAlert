<?php

namespace DeezerAlert;

class Playlist
{
    private $name;

    private $link;

    private $cover;

    private $trackCollection;

    public function __construct(string $name, string $cover, string $link, array $trackCollection)
    {
        $this->name = $name;
        $this->cover = $cover;
        $this->link = $link;
        $this->trackCollection = $trackCollection;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCover(): string
    {
        return $this->cover;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function getRedirectToAppLink(): string
    {
        return str_replace('https://www.deezer.com/', 'https://thomasfagot.me/deezer/', $this->link);
    }

    public function getTrackCollection(): array
    {
        return $this->trackCollection;
    }
}