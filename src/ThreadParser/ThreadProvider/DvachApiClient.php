<?php

declare(strict_types=1);

namespace phpClub\ThreadParser\ThreadProvider;

use phpClub\Entity\{Thread, Post, File};
use GuzzleHttp\Client;

class DvachApiClient
{
    const BASE_URL = 'https://2ch.hk';

    // Fixes 2ch.hk API poor naming
    const POST_AUTHOR = 'name';
    const POST_TITLE = 'subject';
    const POST_TEXT = 'comment';
    const THREAD_TITLE = 'subject';

    /**
     * @var Client
     */
    private $client;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return Thread[]
     */
    public function getAlivePhpThreads(): array
    {
        $responseBody = $this->client->get(self::BASE_URL . '/pr/catalog.json')->getBody();
        $responseJson = \GuzzleHttp\json_decode($responseBody, $assoc = true);
        $threads = $responseJson['threads'];

        $phpThreadsArray = array_filter($threads, [$this, 'looksLikePhpThread']);
        $phpThreads      = array_map([$this, 'extractThread'], $phpThreadsArray);

        return $phpThreads;
    }

    /**
     * @param array $threadArray
     * @return bool
     */
    private function looksLikePhpThread(array $threadArray): bool
    {
        return !!preg_match('/Клуб.*PHP/ui', $threadArray[self::THREAD_TITLE]);
    }

    /**
     * @param array $phpThread
     * @return Thread
     * @throws \Exception
     */
    private function extractThread(array $phpThread): Thread
    {
        $threadId = $phpThread['num'];
        $threadUrl = self::BASE_URL . "/pr/res/{$threadId}.json";

        $responseBody = $this->client->get($threadUrl)->getBody();
        $responseJson = \GuzzleHttp\json_decode($responseBody, $assoc = true);

        $postsArray = $responseJson['threads'][0]['posts'] ?? null;

        if ($postsArray === null) {
            throw new \Exception('2ch.hk API has changed, path threads[0].posts is not exists');
        }

        $thread = new Thread($threadId);

        foreach ($postsArray as $postArray) {
            $thread->addPost($this->extractPost($postArray, $thread));
        }

        return $thread;
    }

    /**
     * @param array $postArray
     * @param Thread $thread
     * @return Post
     */
    private function extractPost(array $postArray, Thread $thread): Post
    {
        $post = new Post(
            $postArray['num'],
            $postArray[self::POST_TITLE],
            $postArray[self::POST_AUTHOR],
            (new \DateTimeImmutable())->setTimestamp($postArray['timestamp']),
            $postArray[self::POST_TEXT],
            $thread
        );

        foreach ($postArray['files'] as $fileArray) {
            $post->addFile($this->extractFile($fileArray, $post));
        }
        
        return $post;
    }

    /**
     * @param array $fileArray
     * @param Post $post
     * @return File
     */
    private function extractFile(array $fileArray, Post $post): File
    {
        return new File(
            self::BASE_URL . $fileArray['path'],
            self::BASE_URL . $fileArray['thumbnail'],
            $post,
            $fileArray['height'],
            $fileArray['width'],
            $fileArray['size'],
            $fileArray['fullname'] ?? $fileArray['name']
        );
    }
}