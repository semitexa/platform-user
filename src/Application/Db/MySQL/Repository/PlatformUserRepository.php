<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Db\MySQL\Repository;

use Semitexa\Core\Attribute\InjectAsReadonly;
use Semitexa\Core\Attribute\SatisfiesRepositoryContract;
use Semitexa\Orm\OrmManager;
use Semitexa\Orm\Query\Direction;
use Semitexa\Orm\Query\Operator;
use Semitexa\Orm\Repository\DomainRepository;
use Semitexa\Platform\User\Application\Db\MySQL\Model\PlatformUserResourceModel;
use Semitexa\Platform\User\Domain\Contract\PlatformUserRepositoryInterface;
use Semitexa\Platform\User\Domain\Enum\UserStatus;
use Semitexa\Platform\User\Domain\Model\PlatformUser;

#[SatisfiesRepositoryContract(of: PlatformUserRepositoryInterface::class)]
final class PlatformUserRepository implements PlatformUserRepositoryInterface
{
    #[InjectAsReadonly]
    protected OrmManager $orm;

    private ?DomainRepository $repository = null;

    public function findById(string $id): ?PlatformUser
    {
        /** @var PlatformUser|null */
        return $this->repository()->findById($id);
    }

    public function findByEmail(string $email, ?string $tenantId = null): ?PlatformUser
    {
        $email = self::normalizeEmail($email);
        if ($email === '') {
            return null;
        }

        $query = $this->repository()->query()
            ->where(PlatformUserResourceModel::column('email'), Operator::Equals, $email);

        if ($tenantId !== null) {
            $query->where(PlatformUserResourceModel::column('tenantId'), Operator::Equals, $tenantId);
        }

        /** @var list<PlatformUser> $matches */
        $matches = $query
            ->orderBy(PlatformUserResourceModel::column('createdAt'), Direction::Asc)
            ->fetchAllAs(PlatformUser::class, $this->orm()->getMapperRegistry());

        // Without a tenant to disambiguate, one email registered against two
        // sites has no single right answer. Refusing beats signing them into
        // whichever row happens to sort first.
        return count($matches) === 1 ? $matches[0] : null;
    }

    public function findAll(?string $tenantId = null, bool $includeDisabled = false): array
    {
        $query = $this->repository()->query();

        if ($tenantId !== null) {
            $query->where(PlatformUserResourceModel::column('tenantId'), Operator::Equals, $tenantId);
        }

        if (!$includeDisabled) {
            $query->where(PlatformUserResourceModel::column('status'), Operator::Equals, UserStatus::Active->value);
        }

        /** @var list<PlatformUser> $users */
        $users = $query
            ->orderBy(PlatformUserResourceModel::column('email'), Direction::Asc)
            ->fetchAllAs(PlatformUser::class, $this->orm()->getMapperRegistry());

        return $users;
    }

    public function save(PlatformUser $user): void
    {
        $this->repository()->insert($user);
    }

    public function update(PlatformUser $user): void
    {
        $this->repository()->update($user);
    }

    public function delete(string $id): void
    {
        $user = $this->findById($id);
        if ($user === null) {
            return;
        }

        $this->repository()->delete($user);
    }

    /**
     * Addresses are compared, stored and indexed in one shape, so "Admin@x.ua"
     * and "admin@x.ua" can never become two accounts.
     */
    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    private function repository(): DomainRepository
    {
        return $this->repository ??= $this->orm()->repository(
            PlatformUserResourceModel::class,
            PlatformUser::class,
        );
    }

    private function orm(): OrmManager
    {
        return $this->orm ?? throw new \RuntimeException('OrmManager not injected into ' . self::class . '.');
    }
}
