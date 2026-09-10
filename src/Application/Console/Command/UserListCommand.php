<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Console\Command;

use Semitexa\Core\Attribute\AsCommand;
use Semitexa\Core\Attribute\InjectAsReadonly;
use Semitexa\Core\Console\BaseCommand;
use Semitexa\Platform\User\Domain\Contract\PlatformUserRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'user:list',
    description: 'List sign-in accounts, with their tenant, role and lock state.',
)]
final class UserListCommand extends BaseCommand
{
    #[InjectAsReadonly]
    protected PlatformUserRepositoryInterface $users;

    protected function configure(): void
    {
        $this->setName('user:list')
            ->setDescription('List sign-in accounts, with their tenant, role and lock state.')
            ->addOption('tenant', null, InputOption::VALUE_REQUIRED, 'Only this tenant')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Include disabled accounts');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tenant = $input->getOption('tenant');
        $users = $this->users->findAll(
            $tenant === null ? null : (string) $tenant,
            (bool) $input->getOption('all'),
        );

        if ($users === []) {
            $output->writeln('No users.');

            return Command::SUCCESS;
        }

        $table = new Table($output);
        // 'password' rather than a wider table: an operator who issued a
        // password needs to see, at a glance, which accounts still owe a change
        // — otherwise the flag is set and then forgotten, which is the same as
        // not setting it.
        $table->setHeaders(['email', 'tenant', 'role', 'status', 'password', 'last login', 'locked until']);

        foreach ($users as $user) {
            $table->addRow([
                $user->getEmail(),
                $user->getTenantId() !== '' ? $user->getTenantId() : '—',
                $user->getRole()->value,
                $user->getStatus()->value,
                $user->isPasswordIssuedByOperator() ? 'must change' : 'theirs',
                $user->getLastLoginAt()?->format('Y-m-d H:i') ?? 'never',
                $user->isLocked() ? (string) $user->getLockedUntil()?->format('Y-m-d H:i') : '—',
            ]);
        }

        $table->render();

        return Command::SUCCESS;
    }
}
