<?php

declare(strict_types=1);

namespace phpClub\ThreadImport;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Evenement\EventEmitterTrait;
use phpClub\Entity\File;
use phpClub\Entity\Thread;
use phpClub\FileStorage\FileStorageInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Filesystem\Exception\IOException;

class ThreadImporter
{
    use EventEmitterTrait;

    const EVENT_THREAD_SAVED = 'event.thread.saved';

    /**
     * @var FileStorageInterface
     */
    private $fileStorage;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var LastPostUpdater
     */
    private $lastPostUpdater;

    /**
     * @var RefLinkGenerator
     */
    private $refLinkManager;
    
    /**
     * @var CacheInterface
     */
    private $cache;

    public function __construct(
        FileStorageInterface $fileStorage,
        EntityManagerInterface $entityManager,
        LastPostUpdater $lastPostUpdater,
        RefLinkGenerator $refLinkManager,
        CacheInterface $cache
    ) {
        $this->fileStorage = $fileStorage;
        $this->entityManager = $entityManager;
        $this->lastPostUpdater = $lastPostUpdater;
        $this->refLinkManager = $refLinkManager;
        $this->cache = $cache;
    }

    /**
     * @param Thread[] $threads
     */
    public function import(array $threads): void
    {
        $this->cascadeRemoveThreads($threads);

        foreach ($threads as $thread) {
            $this->saveFilesFromThread($thread);
            $this->entityManager->persist($thread);
            $this->refLinkManager->insertChain($thread);
            $this->emit(self::EVENT_THREAD_SAVED, [$thread]);
            $this->entityManager->flush();
            $this->entityManager->clear();
        }

        $this->lastPostUpdater->updateLastPosts($threads);
        $this->cache->clear();
    }

    /**
     * @param Thread[] $threads
     * @return void
     */
    private function cascadeRemoveThreads(array $threads): void
    {
        /** @var Connection $connection */
        $connection = $this->entityManager->getConnection();

        $threadIds = array_map(function (Thread $thread) {
            return $thread->getId();
        }, $threads);

        $connection->executeQuery('DELETE FROM thread WHERE id IN (?)',
            [$threadIds],
            [Connection::PARAM_STR_ARRAY]
        );
    }

    private function saveFilesFromThread(Thread $thread): void
    {
        foreach ($thread->getPosts() as $post) {
            foreach ($post->getFiles() as $file) {
                try {
                    $file->updatePaths(
                        $this->fileStorage->put($file->getPath(), (string) $thread->getId()),
                        $this->fileStorage->put($file->getThumbPath(), $thread->getId() . '/thumb')
                    );
                    $this->updateFileSize($file);
                } catch (IOException $e) {
                    // Unable to download, skip
                }
            }
        }
    }

    private function updateFileSize(File $file): void
    {
        if (!$file->hasSize()) {
            // TODO: use FileStorageInterface::getResource() 
            $file->setSize((int) (filesize(__DIR__ . '/../../public/' . $file->getPath()) / 1024));
        }
    }
}
