<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Semitexa\Core\Tenant\DefaultTenantContext;
use Semitexa\Core\Tenant\Layer\TenantLayerInterface;
use Semitexa\Core\Tenant\Layer\TenantLayerValueInterface;
use Semitexa\Core\Tenant\TenantContextInterface;
use Semitexa\Platform\User\Domain\Enum\UserRole;
use Semitexa\Platform\User\Domain\Model\PlatformUser;
use Semitexa\Platform\User\Domain\Service\SiteAccess;

final class SiteAccessTest extends TestCase
{
    #[Test]
    public function a_site_s_user_acts_on_that_site_only(): void
    {
        $admin = self::user(UserRole::Admin, 'museum');

        self::assertTrue(SiteAccess::allows($admin, self::site('museum')));
        self::assertFalse(SiteAccess::allows($admin, self::site('clinic')));
        self::assertFalse(SiteAccess::allows($admin, new DefaultTenantContext()), 'the root site is not filtered by tenant');
        self::assertTrue(SiteAccess::allows($admin, null), 'no tenancy: the one site there is');
    }

    /** By role: an owner made with a site before user:add refused one lost the root site's console. */
    #[Test]
    public function an_owner_acts_anywhere_with_or_without_a_site(): void
    {
        foreach (['', 'museum'] as $site) {
            $owner = self::user(UserRole::Owner, $site);
            self::assertTrue(SiteAccess::allows($owner, new DefaultTenantContext()));
            self::assertTrue(SiteAccess::allows($owner, self::site('clinic')));
        }
    }

    #[Test]
    public function a_non_owner_without_a_site_acts_nowhere_under_tenancy(): void
    {
        $misconfigured = self::user(UserRole::Editor, '');

        self::assertFalse(SiteAccess::allows($misconfigured, self::site('museum')));
        self::assertFalse(SiteAccess::allows($misconfigured, new DefaultTenantContext()));
    }

    private static function user(UserRole $role, string $site): PlatformUser
    {
        return new PlatformUser(id: 'u1', email: 'a@example.test', passwordHash: 'x', tenantId: $site, role: $role);
    }

    private static function site(string $id): TenantContextInterface
    {
        return new class ($id) implements TenantContextInterface {
            public function __construct(private string $id) {}
            public function getTenantId(): string { return $this->id; }
            public function isDefault(): bool { return false; }
            public function getLayer(TenantLayerInterface $layer): ?TenantLayerValueInterface { return null; }
            public function hasLayer(TenantLayerInterface $layer): bool { return false; }
        };
    }
}
