<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    #[DataProvider('accountStates')]
    public function test_only_approved_renters_can_rent(string $role, ?string $status, bool $expected): void
    {
        $user = (new User)->forceFill(['role' => $role, 'approval_status' => $status]);

        $this->assertSame($expected, $user->canRent());
    }

    public static function accountStates(): array
    {
        return [
            'UT-13 approved renter' => ['user', 'approved', true],
            'UT-13 pending renter' => ['user', 'pending', false],
            'UT-13 rejected renter' => ['user', 'rejected', false],
            'UT-13 admin without approval status' => ['admin', null, false],
            'UT-13 admin with approved status' => ['admin', 'approved', false],
        ];
    }
}
