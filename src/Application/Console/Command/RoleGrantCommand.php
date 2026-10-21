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

/** Add or take away permissions of a stored role; its holders see the change on their next request. */
#[AsCommand(name: 'role:grant', description: 'Add (--permission) or remove (--revoke) permissions of a role.')]
final class RoleGrantCommand extends BaseCommand
{
    #[InjectAsReadonly]
    protected RoleStore $roles;

    protected function configure(): void
    {
        $this->setName('role:grant')
            ->setDescription('Add (--permission) or remove (--revoke) permissions of a role.')
            ->addArgument('role', InputArgument::REQUIRED, 'Role name')
            ->addOption('permission', 'p', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'A permission slug to grant')
            ->addOption('revoke', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'A permission slug to take away')
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
        try {
            $permissions = $this->roles->changePermissions($role['id'], array_values((array) $input->getOption('permission')), array_values((array) $input->getOption('revoke')));
        } catch (\InvalidArgumentException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return Command::FAILURE;
        }
        $output->writeln(sprintf('<info>%s grants: %s</info>', $role['name'], $permissions === [] ? '(nothing)' : implode(', ', $permissions)));

        return Command::SUCCESS;
    }
}
