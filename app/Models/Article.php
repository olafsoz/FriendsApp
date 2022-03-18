<?php

namespace App\Models;

class Article
{
    private string $title;
    private string $description;
    private string $createdAt;
    private int $userId;
    private ?int $id;

    public function __construct(string $title, string $description, string $createdAt, int $userId, ?int $id = null)
    {
        $this->title = $title;
        $this->description = $description;
        $this->createdAt = $createdAt;
        $this->userId = $userId;
        $this->id = $id;
    }
    public function getTitle(): string
    {
        return $this->title;
    }
    public function getDescription(): string
    {
        return $this->description;
    }
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }
    public function getUserId()
    {
        return $this->userId;
    }
    public function getId(): int
    {
        return $this->id;
    }
}