<?php

declare(strict_types=1);

namespace phpClub\Entity;

/**
* @Entity(repositoryClass="phpClub\Repository\FileRepository")
**/
class File
{
    /** @Id @Column(type="integer") @GeneratedValue **/
    private $id;

    /** @Column(type="string", nullable=true) **/
    private $relativePath;

    /** @Column(type="string", nullable=true) **/
    private $thumbnailRelativePath;

    /** @Column(type="string", nullable=true) **/
    private $remoteUrl;

    /** @Column(type="string", nullable=true) **/
    private $thumbnailRemoteUrl;

    /** @Column(type="integer", nullable=true) **/
    private $size;

    /** @Column(type="integer") **/
    private $width;

    /** @Column(type="integer") **/
    private $height;

    /**
     * @ManyToOne(targetEntity="phpClub\Entity\Post", inversedBy="files")
     * @JoinColumn(nullable=false)
     **/
    private $post;

    public static function create(
        string $path,
        string $thumbnailPath,
        int $width,
        int $height,
        Post $post,
        int $size = 0
    ): self {
        $file = new self();

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            $file->remoteUrl = $path;
            $file->thumbnailRemoteUrl = $thumbnailPath;
        } else {
            $file->relativePath = $path;
            $file->thumbnailRelativePath = $thumbnailPath;
        }

        $file->width = $width;
        $file->height = $height;
        $file->post = $post;
        $file->size = $size;
        
        return $file;
    }

    public function isRemote(): bool
    {
        return $this->remoteUrl !== null;
    }

    public function changeRemoteUrl(string $remoteUrl, string $thumbnailRemoteUrl)
    {
        $this->remoteUrl = $remoteUrl;
        $this->thumbnailRemoteUrl = $thumbnailRemoteUrl;
    }

    public function getRelativePath() 
    {
        return $this->relativePath;
    }

    public function getThumbnailRelativePath() 
    {
        return $this->thumbnailRelativePath;
    }

    public function getRemoteUrl()
    {
        return $this->remoteUrl;
    }

    public function getThumbnailRemoteUrl()
    {
        return $this->thumbnailRemoteUrl;
    }

    public function getPost(): Post
    {
        return $this->post;
    }

    public function getSize()
    {
        return $this->size;
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function setSize($size)
    {
        $this->size = $size;
    }
}
