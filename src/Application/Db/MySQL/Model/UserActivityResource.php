<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Db\MySQL\Model;

use Semitexa\Orm\Adapter\MySqlType;
use Semitexa\Orm\Attribute\Column;
use Semitexa\Orm\Attribute\FromTable;
use Semitexa\Orm\Attribute\Index;
use Semitexa\Orm\Attribute\TenantScoped;
use Semitexa\Orm\Contract\DomainMappable;
use Semitexa\Orm\Trait\HasUuidV7;
use Semitexa\Platform\User\Domain\Model\UserActivity;

#[FromTable(name: 'platform_user_activity', mapTo: UserActivity::class)]
#[TenantScoped(strategy: 'same_storage')]
#[Index(columns: ['user_id'], name: 'idx_platform_user_activity_user')]
class UserActivityResource implements DomainMappable
{
    use HasUuidV7;

    #[Column(type: MySqlType::Varchar, length: 64, nullable: true)]
    public ?string $tenant_id = null;

    #[Column(type: MySqlType::Binary, length: 16)]
    public string $user_id = '';

    #[Column(type: MySqlType::Varchar, length: 64)]
    public string $action = '';

    #[Column(type: MySqlType::Varchar, length: 45, nullable: true)]
    public ?string $ip_address = null;

    #[Column(type: MySqlType::Varchar, length: 512, nullable: true)]
    public ?string $user_agent = null;

    #[Column(type: MySqlType::Datetime)]
    public ?\DateTimeImmutable $created_at = null;

    public function toDomain(): UserActivity
    {
        return new UserActivity(
            id: $this->id,
            userId: $this->user_id,
            action: $this->action,
            ipAddress: $this->ip_address,
            userAgent: $this->user_agent,
            createdAt: $this->created_at,
        );
    }

    public static function fromDomain(object $entity): static
    {
        assert($entity instanceof UserActivity);
        $r = new static();
        $r->id = $entity->id;
        $r->user_id = $entity->userId;
        $r->action = $entity->action;
        $r->ip_address = $entity->ipAddress;
        $r->user_agent = $entity->userAgent;
        $r->created_at = $entity->createdAt;
        return $r;
    }
}
