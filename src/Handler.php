<?php

namespace DeezerAlert;

use RuntimeException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use function md5;
use function substr;

final class Handler
{
    private const DEEZER_ENDPOINT = 'https://api.deezer.com';
    private const SAVE_FILE = __DIR__.'/../resources/saved_data.json';
    private const DEEZER_QUOTA_PER_X_SECONDS = 50;
    private const DEEZER_QUOTA_SECONDS = 5;

    /**
     * @var array
     */
    private $savedData;

    /**
     * @var array
     */
    private $newContent = [];

    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @var int
     */
    private $requestCounter = 0;

    /**
     * @var string
     */
    private $accessToken;

    public function __construct(string $accessToken)
    {
        $this->accessToken = $accessToken;
        $this->savedData = file_exists(self::SAVE_FILE) ? json_decode(file_get_contents(self::SAVE_FILE), true) : [];
        $this->httpClient = HttpClient::create();
    }

    public function process(): ?Playlist
    {
        $playlist = null;
        if ($newContent = $this->getNewContent()) {
            $playlist = $this->createPlaylist($newContent);
        }

        return $playlist;
    }

    private function requestAll(string $url): array
    {
        $data = [];
        $nextUrl = $url;

        do {
            $this->handleRequestQuota();

            $response = $this->httpClient->request('GET', $nextUrl)->toArray();

            if (!isset($response['data'])) {
                throw new RuntimeException('Unexpected response from Deezer API.');
            }

            foreach ($response['data'] as $item) {
                $data[] = $item;
            }

            $nextUrl = $response['next'] ?? null;
        } while ($nextUrl);

        return $data;
    }

    private function handleRequestQuota(): void
    {
        if (0 === ++$this->requestCounter % self::DEEZER_QUOTA_PER_X_SECONDS) {
            sleep(self::DEEZER_QUOTA_SECONDS + 1);
        }
    }

    private function getNewContent(): array
    {
        $newContent = [];
        $today = date('Y-m-d');
        $threeMonthsAgo = date('Y-m-d', strtotime('3 months ago'));

        foreach ($this->requestAll(self::DEEZER_ENDPOINT.'/user/me/artists?access_token='.$this->accessToken) as $artist) {
            $artistHash = substr(md5($artist['name']), 0, 7);

            if (!isset($this->savedData[$artistHash])) {
                $checkNeWContent = false;
                $this->savedData[$artistHash] = [
                    'nb_album' => $artist['nb_album'],
                    'albums' => [],
                ];
            } else {
                $checkNeWContent = true;
            }

            if (!$this->savedData[$artistHash]['albums'] || $artist['nb_album'] - $this->savedData[$artistHash]['nb_album'] > 0) {
                foreach ($this->requestAll(self::DEEZER_ENDPOINT.'/artist/'.$artist['id'].'/albums') as $album) {
                    $albumHash = substr(md5($album['title']), 0, 7);

                    if (!in_array($albumHash, $this->savedData[$artistHash]['albums'], true)) {
                        $this->savedData[$artistHash]['albums'][] = $albumHash;

                        if ($checkNeWContent && $album['release_date'] <= $today && $album['release_date'] >= $threeMonthsAgo) {
                            $album['artist'] = $artist;
                            $this->newContent[] = $album;
                        }
                    }
                }
            }
        }

        file_put_contents(self::SAVE_FILE, json_encode($this->savedData));

        return $newContent;
    }

    private function createPlaylist(array $newContent): Playlist
    {
        $playlistName = 'Sorties du '.date('d/m/Y');

        $this->handleRequestQuota();
        $playlistId = $this->httpClient->request(
                'POST',
                self::DEEZER_ENDPOINT.'/user/me/playlists'
                .'?access_token='.$this->accessToken
                .'&title='.$playlistName
            )->toArray()['id'] ?? null;

        if (!$playlistId) {
            throw new RuntimeException('Count not create playlist');
        }

        $trackCollection = [];
        foreach ($newContent as $album) {
            foreach ($album['tracks']['data'] as $track) {
                $trackCollection[] = $track;
            }
        }

        $this->handleRequestQuota();
        $response = $this->httpClient->request(
            'POST',
            self::DEEZER_ENDPOINT.'/playlist/'.$playlistId.'/tracks'
            .'?access_token='.$this->accessToken
            .'&songs='.implode(',', array_column($trackCollection, 'id'))
        )->getContent();

        if ('true' !== $response) {
            throw new RuntimeException('Could not add tracks to playlist');
        }

        return new Playlist(
            $playlistName,
            $this->getPlaylistCover($playlistId),
            'https://www.deezer.com/fr/playlist/'.$playlistId,
            $trackCollection
        );
    }

    private function getPlaylistCover(int $playlistId): string
    {
        $this->handleRequestQuota();
        return $this->httpClient->request(
            'GET',
            self::DEEZER_ENDPOINT.'/playlist/'.$playlistId
                .'?access_token='.$this->accessToken
            )->toArray()['picture_medium'] ?? '';
    }
}
