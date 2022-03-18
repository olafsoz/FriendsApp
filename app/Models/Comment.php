<?php

namespace App\Models;

class Comment
{
    private int $articleId;
    private string $usersName;
    private string $text;
    private string $createdAt;
    private ?int $id;

    public function __construct(int $articleId, string $usersName, string $text, string $createdAt, ?int $id = null)
    {
        $this->articleId = $articleId;
        $this->usersName = $usersName;
        $this->text = $text;
        $this->createdAt = $createdAt;
        $this->id = $id;
    }

    public function getArticleId(): int
    {
        return $this->articleId;
    }

    public function getUsersName(): string
    {
        return $this->usersName;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getId(): int
    {
        return $this->id;
    }
}