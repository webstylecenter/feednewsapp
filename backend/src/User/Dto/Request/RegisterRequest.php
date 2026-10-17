<?php

declare(strict_types=1);

namespace App\User\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class RegisterRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $name = '';

    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public string $email = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 8, max: 4096)]
    public string $password = '';

    #[Assert\Blank(message: 'Registration rejected.')]
    public string $address = '';

    #[Assert\IsTrue(message: 'You must agree to the privacy policy.')]
    public bool $gdpr = false;
}
