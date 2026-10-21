<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Console\Command;

use Semitexa\Core\Attribute\AsCommand;
use Semitexa\Core\Attribute\InjectAsReadonly;
use Semitexa\Core\Console\BaseCommand;
use Semitexa\Platform\User\Application\Db\MySQL\Repository\PlatformUserRepository;
use Semitexa\Platform\User\Application\Service\RoleStore;
use Semitexa\Platform\User\Domain\Contract\PlatformUserRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Give a user a stored role (--remove takes it back). A tenant's own role goes
 * only to that tenant's users: a role of one site held by another site's
 * account would grant across sites.
 */
#[AsCommand(name: 'role:assign', description: 'Give a user a role (--remove takes it back).')]
final class RoleAssignCommand extends BaseCommand
{
    #[InjectAsReadonly]
    protected RoleStore $roles;

    #[InjectAsReadonly]
    protected PlatformUserRepositoryInterface $users;

    protected function configure(): void
    {
        $this->setName('role:assign')
            ->setDescription('Give a user a role (--remove takes it back).')
            ->addArgument('role', InputArgument::REQUIRED, 'Role name')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'The user\'s address')
            ->addOption('tenant', null, InputOption::VALUE_REQUIRED, 'The user\'s tenant, when the address exists for more than one', '')
            ->addOption('shared', null, InputOption::VALUE_NONE, 'The shared role of that name, not the user\'s site\'s own')
            ->addOption('remove', null, InputOption::VALUE_NONE, 'Take the role away instead');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tenant = (string) $input->getOption('tenant');
        $user = $this->users->findByEmail(PlatformUserRepository::normalizeEmail((string) $input->getOption('email')), $tenant === '' ? null : $tenant);
        if ($user === null) {
            $output->writeln('<error>No single account matches that address. Pass --tenant when the same address exists for more than one site.</error>');

            return Command::FAILURE;
        }
        $name = (string) $input->getArgument('role');
        // The user's site's own role first, then the shared one of that name;
        // --shared names the shared one when both exist.
        $role = (bool) $input->getOption('shared')
            ? $this->roles->find('', $name)
            : $this->roles->find($user->getTenantId(), $name) ?? $this->roles->find('', $name);
        if ($role === null) {
            $output->writeln("<error>No role {$name} for this user's tenant and no shared one. role:list shows them.</error>");

            return Command::FAILURE;
        }

        if ((bool) $input->getOption('remove')) {
            $done = $this->roles->unassign($user->getId(), $role['id']);
            $output->writeln($done ? "<info>{$user->getEmail()} no longer holds {$name}.</info>" : "{$user->getEmail()} did not hold {$name}.");

            return Command::SUCCESS;
        }
        $done = $this->roles->assign($user->getId(), $role['id']);
        $output->writeln($done ? "<info>{$user->getEmail()} now holds {$name}.</info>" : "{$user->getEmail()} already holds {$name}.");

        return Command::SUCCESS;
    }
}
