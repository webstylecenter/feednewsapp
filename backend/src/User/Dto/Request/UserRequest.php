<?php

declare(strict_types=1);

namespace App\User\Dto\Request;

use SensitiveParameter;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UserRequest
{
    #[Assert\NotBlank]
    #[Groups(['user:write'])]
    public string $name;

    #[Assert\NotBlank]
    #[Assert\Email]
    #[Groups(['user:write'])]
    public string $email;

    #[Assert\NotBlank]
    #[Groups(['user:write'])]
    public string $password;

    #[Groups(['user:write'])]
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
