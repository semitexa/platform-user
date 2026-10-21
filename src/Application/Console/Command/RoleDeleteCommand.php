<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Console\Command;

use Semitexa\Core\Attribute\AsCommand;
use Semitexa\Core\Attribute\InjectAsReadonly;
use Semitexa\Core\Console\BaseCommand;
use Semitexa\Platform\User\Application\Service\RoleStore;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/** Delete a stored role; every user holding it loses what it granted. */
#[AsCommand(name: 'role:delete', description: 'Delete a role and take it from everyone who holds it.')]
final class RoleDeleteCommand extends BaseCommand
{
    #[InjectAsReadonly]
    protected RoleStore $roles;

    protected function configure(): void
    {
        $this->setName('role:delete')
            ->setDescription('Delete a role and take it from everyone who holds it.')
            ->addArgument('role', InputArgument::REQUIRED, 'Role name')
            ->addOption('tenant', null, InputOption::VALUE_REQUIRED, 'The tenant the role belongs to')
            ->addOption('shared', null, InputOption::VALUE_NONE, 'The shared role (every site\'s)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Which one, said: a forgotten --tenant changed every site's shared role.
        $tenant = $input->getOption('tenant');
        if ((bool) $input->getOption('shared') === (is_string($tenant) && $tenant !== '')) {
            $output->writeln('<error>Say which role: --tenant=<id> for a site\'s own, or --shared.</error>');

            return Command::FAILURE;
        }
        $role = $this->roles->find(is_string($tenant) ? $tenant : '', (string) $input->getArgument('role'));
        if ($role === null) {
            $output->writeln('<error>No such role. role:list shows them; pass --tenant for a tenant\'s own role.</error>');

            return Command::FAILURE;
        }
        $this->roles->delete($role['id']);
        $output->writeln("<info>Role {$role['name']} deleted.</info>");

        return Command::SUCCESS;
    }
}
