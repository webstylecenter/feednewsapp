<?php

declare(strict_types=1);

namespace App\Tests\User;

use App\User\Dto\Request\UserRequest;
use App\User\Mapper\UserMapper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

final class UserMapperTest extends TestCase
{
    public function testToEntityHashesPasswordAndMapsFields(): void
    {
        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher
            ->expects(self::once())
            ->method('hash')
            ->with('plain-password')
            ->willReturn('hashed-password');

        $factory = $this->createMock(PasswordHasherFactoryInterface::class);
        $factory
            ->expects(self::once())
            ->method('getPasswordHasher')
            ->willReturn($hasher);

        $mapper = new UserMapper($factory);

        $request = new UserRequest(
            name: 'Test User',
            email: 'test@example.com',
            password: 'plain-password',
            avatar: 'https://example.com/avatar.png',
        );

        $entity = $mapper->toEntity($request);

        self::assertSame('test@example.com', $entity->getEmail());
        self::assertSame('Test User', $entity->getName());
        self::assertSame('hashed-password', $entity->getPassword());
        self::assertSame('https://example.com/avatar.png', $entity->getAvatar());
        self::assertTrue($entity->isEnabled());
        self::assertFalse($entity->isHideXFrameNotice());
    }
}
