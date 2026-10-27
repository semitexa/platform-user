<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Db\MySQL\Repository;

use Semitexa\Core\Attribute\InjectAsReadonly;
use Semitexa\Core\Attribute\SatisfiesRepositoryContract;
use Semitexa\Core\Auth\Event\UserDeleted;
use Semitexa\Core\Event\EventDispatcherInterface;
use Semitexa\Orm\Application\Service\Uuid7;
use Semitexa\Orm\OrmManager;
use Semitexa\Orm\Query\Direction;
use Semitexa\Orm\Query\Operator;
use Semitexa\Orm\Repository\DomainRepository;
use Semitexa\Platform\User\Application\Db\MySQL\Model\PlatformUserResourceModel;
use Semitexa\Platform\User\Domain\Contract\FailedLoginCounterInterface;
use Semitexa\Platform\User\Domain\Contract\PlatformUserEmailLookupInterface;
use Semitexa\Platform\User\Domain\Contract\PlatformUserRepositoryInterface;
use Semitexa\Platform\User\Domain\Enum\UserStatus;
use Semitexa\Platform\User\Domain\Model\PlatformUser;

#[SatisfiesRepositoryContract(of: PlatformUserRepositoryInterface::class)]
final class PlatformUserRepository implements PlatformUserRepositoryInterface, FailedLoginCounterInterface, PlatformUserEmailLookupInterface
{
    #[InjectAsReadonly]
    protected OrmManager $orm;

    #[InjectAsReadonly(optional: true)]
    protected EventDispatcherInterface $events;

    private ?DomainRepository $repository = null;

    public function findById(string $id): ?PlatformUser
    {
        /** @var PlatformUser|null */
        return $this->repository()->findById($id);
    }

    public function findByEmail(string $email, ?string $tenantId = null): ?PlatformUser
    {
        $matches = $this->matching($email, $tenantId);

        // Without a tenant to disambiguate, one email registered against two
        // sites has no single right answer. Refusing beats signing them into
        // whichever row happens to sort first.
        return count($matches) === 1 ? $matches[0] : null;
    }

    public function findAllByEmail(string $email): array
    {
        return $this->matching($email, null);
    }

    /** @return list<PlatformUser> */
    private function matching(string $email, ?string $tenantId): array
    {
        $email = self::normalizeEmail($email);
        if ($email === '') {
            return [];
        }

        $query = $this->repository()->query()
            ->where(PlatformUserResourceModel::column('email'), Operator::Equals, $email);

        if ($tenantId !== null) {
            $query->where(PlatformUserResourceModel::column('tenantId'), Operator::Equals, $tenantId);
        }

        /** @var list<PlatformUser> */
        return $query
            ->orderBy(PlatformUserResourceModel::column('createdAt'), Direction::Asc)
            ->fetchAllAs(PlatformUser::class, $this->orm()->getMapperRegistry());
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

    public function reserveLoginAttempt(PlatformUser $user, int $maxAttempts): bool
    {
        // Atomic statements, none reading the row first: from the row as
        // read, fifty guesses sent at once all started from the same count and
        // were all checked. Portable: no reliance on how a dialect orders the
        // assignments of one UPDATE.
        $adapter = $this->orm()->getQueryAdapter();
        $now = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $id = $this->rowId($user);

        // A lockout that ran out starts the count again: one more miss after
        // it used to lock again for the full window, so one request every
        // fifteen minutes kept anybody locked out for good.
        $adapter->execute(
            'UPDATE platform_user SET failed_attempts = 0, locked_until = NULL WHERE id = :id AND locked_until IS NOT NULL AND locked_until <= :now',
            ['id' => $id, 'now' => $now],
        );

        return $adapter->execute(
            'UPDATE platform_user SET failed_attempts = failed_attempts + 1 WHERE id = :id AND locked_until IS NULL AND failed_attempts < :max',
            ['id' => $id, 'max' => max(1, $maxAttempts)],
        )->rowCount === 1;
    }

    public function lockIfExhausted(PlatformUser $user, int $maxAttempts, int $lockSeconds): ?PlatformUser
    {
        $this->orm()->getQueryAdapter()->execute(
            'UPDATE platform_user SET locked_until = :until WHERE id = :id AND failed_attempts >= :max AND locked_until IS NULL',
            [
                'id' => $this->rowId($user),
                'until' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->add(new \DateInterval('PT' . max(1, $lockSeconds) . 'S'))->format('Y-m-d H:i:s'),
                'max' => max(1, $maxAttempts),
            ],
        );

        return $this->findById($user->getId());
    }

    private function rowId(PlatformUser $user): string
    {
        return strlen($user->getId()) === 36 && str_contains($user->getId(), '-') ? Uuid7::toBytes($user->getId()) : $user->getId();
    }

    public function delete(string $id): void
    {
        $user = $this->findById($id);
        if ($user === null) {
            return;
        }

        $this->repository()->delete($user);
        // In the caller's transaction: a Sync listener removes what it keeps
        // for the user with the row (settings, feature flag overrides).
        if (isset($this->events)) {
            $this->events->dispatch(new UserDeleted($user->getId(), $user->getTenantId()));
        }
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
