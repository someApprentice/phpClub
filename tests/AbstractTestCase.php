<?php

declare(strict_types=1);

namespace Tests;

use phpClub\FileStorage\FileStorageInterface;
use Doctrine\ORM\EntityManager;
use phpClub\ThreadImport\{RefLinkGenerator, ThreadImporter, LastPostUpdater};
use phpClub\ThreadParser\DvachThreadParser;
use PHPUnit\Framework\TestCase;
use phpClub\Entity\{File, Post, Thread};
use Psr\SimpleCache\CacheInterface;
use Slim\Container;

abstract class AbstractTestCase extends TestCase
{
    private static $container;
    
    public function createThread($id): Thread
    {
        return new Thread($id);
    }

    public function createPost($id, Thread $thread = null): Post
    {
        return new Post(
            $id,
            'title ' . $id,
            'author ' . $id,
            new \DateTimeImmutable(),
            'text ' . $id,
            $thread ?: $this->createThread($id)
        );
    }

    public function createFile(int $id): File
    {
        return new File(
            __DIR__ . '/FileStorage/1.png',
            __DIR__ . '/FileStorage/2.png',
            $this->createPost($id),
            100,
            200
        );
    }

    public function getContainer(): Container
    {
        if (!self::$container) {
            self::$container = require_once __DIR__ . '/../src/Bootstrap.php';
        }
        
        return self::$container;
    }

    public function importThreadToDb(string $pathToHtml)
    {
        /** @var DvachThreadParser $parser */
        $parser = $this->getContainer()->get(DvachThreadParser::class);
        $thread = $parser->extractThread(file_get_contents($pathToHtml));
        
        // TODO: use resource
        $fileStorage = new class implements FileStorageInterface {
            public function put(string $path, string $directory): string
            {
                return __DIR__ . '/FileStorage/1.png';
            }

            public function isFileExist(string $path, string $directory): bool
            {
                return false;
            }
        };

        $importer = new ThreadImporter(
            $fileStorage,
            $this->getContainer()->get(EntityManager::class),
            $this->createMock(LastPostUpdater::class),
            $this->createMock(RefLinkGenerator::class),
            $this->createMock(CacheInterface::class)
        );

        $importer->import([$thread]);
    }
}