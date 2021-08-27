<?php
declare(strict_types=1);

namespace App\Smtp;

use Ramsey\Uuid\Uuid;

class Attachment
{
    private string $id;

    public function __construct(private ?string $filename, private string $content, private string $type)
    {
        $this->id = Uuid::uuid4()->toString();
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
