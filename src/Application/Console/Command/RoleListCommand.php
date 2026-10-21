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
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/** The stored roles and what they grant, or the roles one user holds (--email). */
#[AsCommand(name: 'role:list', description: 'List roles and their permissions (--email: the roles one user holds).')]
final class RoleListCommand extends BaseCommand
{
    #[InjectAsReadonly]
    protected RoleStore $roles;

    #[InjectAsReadonly]
    protected PlatformUserRepositoryInterface $users;

    protected function configure(): void
    {
        $this->setName('role:list')
            ->setDescription('List roles and their permissions (--email: the roles one user holds).')
            ->addOption('tenant', null, InputOption::VALUE_REQUIRED, 'This tenant\'s roles and the shared ones')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'The roles this address holds');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tenant = $input->getOption('tenant');
        $email = $input->getOption('email');
        if (is_string($email) && $email !== '') {
            $user = $this->users->findByEmail(PlatformUserRepository::normalizeEmail($email), is_string($tenant) && $tenant !== '' ? $tenant : null);
            if ($user === null) {
                $output->writeln('<error>No single account matches that address. Pass --tenant when the same address exists for more than one site.</error>');

                return Command::FAILURE;
            }
            $roles = $this->roles->rolesOf($user->getId());
        } else {
            $roles = $this->roles->all(is_string($tenant) ? $tenant : null);
        }

        if ($roles === []) {
            $output->writeln('No roles.');

            return Command::SUCCESS;
        }
        (new Table($output))
            ->setHeaders(['role', 'tenant', 'permissions'])
            ->setRows(array_map(static fn (array $r): array => [$r['name'], $r['tenant_id'] === '' ? '(shared)' : $r['tenant_id'], implode(', ', $r['permissions'])], $roles))
            ->render();

        return Command::SUCCESS;
    }
}
