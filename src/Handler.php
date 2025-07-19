<?php

namespace DeezerAlert;

use Generator;
use RuntimeException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Retry\GenericRetryStrategy;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use function count;
use function md5;
use function substr;

final class Handler
{
    private const string DEEZER_ENDPOINT = 'https://api.deezer.com';
    private const string SAVE_FILE = __DIR__.'/../resources/saved_data.json';
    private const int DEEZER_QUOTA_PER_X_SECONDS = 50;
    private const int DEEZER_QUOTA_SECONDS = 5;

    private array $savedData;

    private HttpClientInterface $httpClient;

    private int $requestCounter = 0;

    public function __construct(private readonly string $accessToken)
    {
        $this->savedData = file_exists(self::SAVE_FILE) ? json_decode(file_get_contents(self::SAVE_FILE), true, 512, JSON_THROW_ON_ERROR) : [];
        $httpClient = HttpClient::create([
            'base_uri' => self::DEEZER_ENDPOINT,
            'timeout' => 10,
            'max_duration' => 10,
            'max_redirects' => 3,
        ]);
        $this->httpClient = new RetryableHttpClient(
            $httpClient,
            new GenericRetryStrategy(
                array_merge(GenericRetryStrategy::DEFAULT_RETRY_STATUS_CODES, [403]),
                self::DEEZER_QUOTA_SECONDS * 1000,
                2,
                self::DEEZER_QUOTA_SECONDS * 3 * 1000,
            )
        );
    }

    public function process(): void
    {
        if ($newContent = $this->getNewContent()) {
            $this->createPlaylist($newContent);
        }
        file_put_contents(self::SAVE_FILE, json_encode($this->savedData, JSON_THROW_ON_ERROR));
    }

    private function requestAll(string $url): Generator
    {
        $nextUrl = $url;

        do {
            $this->handleRequestQuota();

            $response = $this->httpClient->request('GET', $nextUrl)->toArray();

            if (!isset($response['data'])) {
                throw new RuntimeException(sprintf('Unexpected response from Deezer API after %d request : %s', $this->requestCounter ?? 0, $response['error']['message'] ?? '?'));
            }

            foreach ($response['data'] as $item) {
                yield $item;
            }

            $nextUrl = $response['next'] ?? null;
        } while ($nextUrl);
    }

    private function handleRequestQuota(): void
    {
        if ($this->requestCounter > 0 && 0 === $this->requestCounter % (self::DEEZER_QUOTA_PER_X_SECONDS - 1)) {
            sleep(self::DEEZER_QUOTA_SECONDS + 1);
        }
        ++$this->requestCounter;
    }

    private function getNewContent(): array
    {
//        return $this->mockNewContent();
        $newContent = [];
        $today = date('Y-m-d');
        $knownArtistMaximumDate = date('Y-m-d', strtotime('3 months ago'));
        $newArtistMaximumDate = date('Y-m-d', strtotime('6 days ago'));

        foreach ($this->requestAll('/user/me/artists?access_token='.$this->accessToken) as $artist) {
            $artistHash = substr(md5($artist['name']), 0, 7);

            if (!isset($this->savedData[$artistHash])) {
                // Recently added artist
                $this->savedData[$artistHash] = [];
                $maximumDate = $newArtistMaximumDate;
            } else {
                $maximumDate = $knownArtistMaximumDate;
            }

            if ($artist['nb_album'] > count($this->savedData[$artistHash])) {
                foreach ($this->requestAll('/artist/'.$artist['id'].'/albums') as $album) {
                    $albumHash = substr(md5($album['title']), 0, 7);

                    if ($album['release_date'] <= $today && !in_array($albumHash, $this->savedData[$artistHash], true)) {
                        $this->savedData[$artistHash][] = $albumHash;

                        if ($album['release_date'] >= $maximumDate) {
                            $newContent[] = $album;
                        }
                    }
                }
            }
        }

        return $newContent;
    }

    private function createPlaylist(array $newContent): void
    {
        $playlistName = 'Sorties du '.date('d/m/Y');

        $this->handleRequestQuota();
        $playlistId = $this->httpClient->request(
            'POST',
            '/user/me/playlists'
            .'?access_token='.$this->accessToken
            .'&title='.$playlistName
        )->toArray()['id'] ?? null;

        if (!$playlistId) {
            throw new RuntimeException('Count not create playlist');
        }

        $trackCollection = [];
        foreach ($newContent as $album) {
            foreach ($this->requestAll('/album/'.$album['id'].'/tracks') as $track) {
                $trackCollection[] = $track;
            }
        }

        $this->handleRequestQuota();
        $response = $this->httpClient->request(
            'POST',
            '/playlist/'.$playlistId.'/tracks'
            .'?access_token='.$this->accessToken
            .'&songs='.implode(',', array_unique(array_column($trackCollection, 'id')))
        )->getContent();

        if ('true' !== strtoupper($response)) {
            throw new RuntimeException('Could not add tracks to playlist : '.$response);
        }
    }

    private function mockNewContent(): array
    {
        return [
            json_decode('{
                "id": "302127",
                "title": "Discovery",
                "upc": "724384960650",
                "link": "https://www.deezer.com/album/302127",
                "share": "https://www.deezer.com/album/302127?utm_source=deezer&utm_content=album-302127&utm_term=0_1603889105&utm_medium=web",
                "cover": "https://api.deezer.com/album/302127/image",
                "cover_small": "https://cdns-images.dzcdn.net/images/cover/2e018122cb56986277102d2041a592c8/56x56-000000-80-0-0.jpg",
                "cover_medium": "https://cdns-images.dzcdn.net/images/cover/2e018122cb56986277102d2041a592c8/250x250-000000-80-0-0.jpg",
                "cover_big": "https://cdns-images.dzcdn.net/images/cover/2e018122cb56986277102d2041a592c8/500x500-000000-80-0-0.jpg",
                "cover_xl": "https://cdns-images.dzcdn.net/images/cover/2e018122cb56986277102d2041a592c8/1000x1000-000000-80-0-0.jpg",
                "md5_image": "2e018122cb56986277102d2041a592c8",
                "genre_id": 113,
                "label": "Parlophone (France)",
                "nb_tracks": 14,
                "duration": 3660,
                "fans": 218102,
                "rating": 0,
                "release_date": "2001-03-07",
                "record_type": "album",
                "available": true,
                "tracklist": "https://api.deezer.com/album/302127/tracks",
                "explicit_lyrics": false,
                "explicit_content_lyrics": 7,
                "explicit_content_cover": 0,
                "type": "album"
                }', true, 512, JSON_THROW_ON_ERROR)
        ];
    }
}
