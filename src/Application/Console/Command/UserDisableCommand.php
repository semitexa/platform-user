<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Console\Command;

use Semitexa\Core\Attribute\AsCommand;
use Semitexa\Core\Attribute\InjectAsReadonly;
use Semitexa\Core\Console\BaseCommand;
use Semitexa\Platform\User\Application\Db\MySQL\Repository\PlatformUserRepository;
use Semitexa\Platform\User\Domain\Contract\PlatformUserRepositoryInterface;
use Semitexa\Platform\User\Domain\Enum\UserStatus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Revoke (or restore) access without losing who did what: the row stays, so the
 * audit trail still resolves to a name.
 */
#[AsCommand(
    name: 'user:disable',
    description: 'Disable a user account (--enable restores it).',
)]
final class UserDisableCommand extends BaseCommand
{
    #[InjectAsReadonly]
    protected PlatformUserRepositoryInterface $users;

    protected function configure(): void
    {
        $this->setName('user:disable')
            ->setDescription('Disable a user account (--enable restores it).')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Whose access to change')
            ->addOption('tenant', null, InputOption::VALUE_REQUIRED, 'Tenant id, when the address exists for more than one', '')
            ->addOption('enable', null, InputOption::VALUE_NONE, 'Restore access instead of revoking it');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = PlatformUserRepository::normalizeEmail((string) $input->getOption('email'));
        $tenant = (string) $input->getOption('tenant');

        $user = $this->users->findByEmail($email, $tenant === '' ? null : $tenant);
        if ($user === null) {
            $output->writeln('<error>No single account matches that address. Pass --tenant when the same address administers more than one site.</error>');

            return Command::FAILURE;
        }

        $enable = (bool) $input->getOption('enable');
        $this->users->update($user->withStatus($enable ? UserStatus::Active : UserStatus::Disabled));

        $output->writeln(sprintf(
            '<info>%s is now %s.</info>',
            $user->getEmail(),
            $enable ? 'active' : 'disabled',
        ));

        return Command::SUCCESS;
    }
}
