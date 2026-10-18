<?php

declare(strict_types=1);

namespace App\Tests\User;

use App\User\Entity\User;
use App\User\Mapper\UserMapper;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\Uid\Uuid;

final class UserMapperTest extends TestCase
{
    public function testMapsUserToAdminApiResponses(): void
    {
        $user = new User(
            email: 'test@example.com',
            name: 'Test User',
            password: 'hashed-password',
            hideXFrameNotice: true,
            enabled: true,
            avatar: 'https://example.com/avatar.png',
        );

        $uuid = Uuid::v4();

        $user->setIsAdmin(true);
        $user->setLastLogin(new DateTimeImmutable('2026-10-17 12:00:00'));

        $id = new ReflectionProperty(User::class, 'uuid');
        $id->setValue($user, $uuid);

        $mapper = new UserMapper();
        $collection = $mapper->toCollectionResponse($user);
        $item = $mapper->toItemResponse($user);

        self::assertSame($uuid->toString(), $collection->uuid);
        self::assertSame('Test User', $collection->name);
        self::assertTrue($collection->isAdmin);

        self::assertSame($uuid->toString(), $item->uuid);
        self::assertSame('test@example.com', $item->email);
        self::assertTrue($item->hideXFrameNotice);
        self::assertContains('ROLE_ADMIN', $item->roles);
    }
}
