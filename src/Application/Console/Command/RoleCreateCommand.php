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

/** A stored role and the permissions it grants, for semitexa/rbac. */
#[AsCommand(name: 'role:create', description: 'Create a role that grants permissions (--permission, repeatable).')]
final class RoleCreateCommand extends BaseCommand
{
    #[InjectAsReadonly]
    protected RoleStore $roles;

    protected function configure(): void
    {
        $this->setName('role:create')
            ->setDescription('Create a role that grants permissions (--permission, repeatable).')
            ->addArgument('name', InputArgument::REQUIRED, 'Role name: lower-case letters, digits and . _ -')
            ->addOption('permission', 'p', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'A permission slug the role grants, such as orders.manage')
            ->addOption('tenant', null, InputOption::VALUE_REQUIRED, 'The site the role belongs to: only its users can hold it, and it grants only there')
            ->addOption('shared', null, InputOption::VALUE_NONE, 'A role every site\'s users can hold');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Which one, said: a forgotten --tenant made a role every site's users
        // could be given.
        $tenant = trim((string) $input->getOption('tenant'));
        if ((bool) $input->getOption('shared') === ($tenant !== '')) {
            $output->writeln('<error>Say whose role it is: --tenant=<id> for a site\'s own, or --shared.</error>');

            return Command::FAILURE;
        }
        try {
            $this->roles->create($tenant, (string) $input->getArgument('name'), array_values((array) $input->getOption('permission')));
        } catch (\InvalidArgumentException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return Command::FAILURE;
        }
        $output->writeln(sprintf('<info>Role %s created.</info>', $input->getArgument('name')));

        return Command::SUCCESS;
    }
}
