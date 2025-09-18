<?php

declare(strict_types=1);

namespace App\User\Dto\Response;

use DateTimeImmutable;

final readonly class UserCollectionResponse
{
    public int $id;
    public string $name;
    public bool $enabled;
    public bool $isAdmin;
    public ?string $avatar;
    public ?DateTimeImmutable $lastLogin;

    public function __construct(
        int $id,
        string $name,
        bool $enabled,
        bool $isAdmin,
        ?string $avatar,
        ?DateTimeImmutable $lastLogin
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->enabled = $enabled;
        $this->isAdmin = $isAdmin;
        $this->avatar = $avatar;
        $this->lastLogin = $lastLogin;
    }
}
