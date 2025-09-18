<?php

declare(strict_types=1);

namespace App\User\Dto\Request;

use SensitiveParameter;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UserRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 180)]
    #[Groups(['user:write'])]
    public string $name;

    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 254)]
    #[Groups(['user:write'])]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 8, max: 4096)]
    #[Assert\NotCompromisedPassword]
    #[Groups(['user:write'])]
    public string $password;

    #[Groups(['user:write'])]
    #[Assert\Url(protocols: ['http','https'])]
    #[Assert\Length(max: 2048)]
    public ?string $avatar;

    public function __construct(
        string $name,
        string $email,
        #[SensitiveParameter] string $password,
        ?string $avatar
    ) {
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->avatar = $avatar;
    }
}
