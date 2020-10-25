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
     * @var string
     */
    private $deezerId;

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

    public function __construct(string $deezerId)
    {
        $this->deezerId = $deezerId;
        $this->savedData = file_exists(self::SAVE_FILE) ? json_decode(file_get_contents(self::SAVE_FILE), true) : [];
        $this->httpClient = HttpClient::create();
    }

    public function process(): array
    {
        $today = date('Y-m-d');
		$threeMonthsAgo = date('Y-m-d', strtotime('3 months ago'));

        foreach ($this->requestAll(self::DEEZER_ENDPOINT.'/user/'.$this->deezerId.'/artists') as $artist) {
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

        return $this->newContent;
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
}