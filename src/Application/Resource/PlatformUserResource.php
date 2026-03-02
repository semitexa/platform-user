<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Resource;

use Semitexa\Orm\Adapter\MySqlType;
use Semitexa\Orm\Attribute\Column;
use Semitexa\Orm\Attribute\FromTable;
use Semitexa\Orm\Attribute\Index;
use Semitexa\Orm\Contract\DomainMappable;
use Semitexa\Orm\Trait\HasTimestamps;
use Semitexa\Orm\Trait\HasUuidV7;
use Semitexa\Platform\User\Domain\User;

#[FromTable(name: 'platform_users', mapTo: User::class)]
#[Index(columns: ['email'], unique: true, name: 'uniq_platform_users_email')]
class PlatformUserResource implements DomainMappable
{
    use HasUuidV7;
    use HasTimestamps;

    #[Column(type: MySqlType::Varchar, length: 255)]
    public string $email = '';

    #[Column(type: MySqlType::Varchar, length: 255)]
    public string $name = '';

    #[Column(type: MySqlType::Varchar, length: 255)]
    public string $password_hash = '';

    #[Column(type: MySqlType::Boolean)]
    public bool $is_active = true;

    public function toDomain(): User
    {
        return new User(
            id: $this->id,
            email: $this->email,
            name: $this->name,
            isActive: $this->is_active,
        );
    }

    public static function fromDomain(object $entity): static
    {
        assert($entity instanceof User);
        $r = new static();
        $r->id = $entity->id;
        $r->email = $entity->email;
        $r->name = $entity->name;
        $r->is_active = $entity->isActive;
        return $r;
    }
}
