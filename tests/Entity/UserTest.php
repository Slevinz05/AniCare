<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    #[DataProvider('directoryListingProvider')]
    public function testIsListedInDirectory(string $accountType, array $roles, bool $directoryVisible, bool $expected): void
    {
        $user = (new User())
            ->setAccountType($accountType)
            ->setRoles($roles)
            ->setDirectoryVisible($directoryVisible);

        self::assertSame($expected, $user->isListedInDirectory());
    }

    public static function directoryListingProvider(): iterable
    {
        yield 'PRO, option activée' => ['PRO', ['ROLE_PRO'], true, true];
        yield 'PRO + particulier (BOTH), option activée' => ['BOTH', ['ROLE_PRO'], true, true];
        yield 'PRO, option désactivée' => ['PRO', ['ROLE_PRO'], false, false];
        yield 'PRO + particulier (BOTH), option désactivée' => ['BOTH', ['ROLE_PRO'], false, false];
        yield 'particulier, option activée' => ['OWNER', ['ROLE_USER'], true, false];
    }
}
