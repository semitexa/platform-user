<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Console\Command;

use Semitexa\Core\Attribute\AsCommand;
use Semitexa\Core\Attribute\InjectAsReadonly;
use Semitexa\Core\Console\BaseCommand;
use Semitexa\Orm\Application\Service\Uuid7;
use Semitexa\Platform\User\Application\Db\MySQL\Repository\PlatformUserRepository;
use Semitexa\Platform\User\Application\Service\PasswordHasher;
use Semitexa\Platform\User\Domain\Contract\PlatformUserRepositoryInterface;
use Semitexa\Platform\User\Domain\Enum\UserRole;
use Semitexa\Platform\User\Domain\Model\PlatformUser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Create a sign-in account. The only supported way to make the first one — there
 * is deliberately no self-registration and no default password.
 */
#[AsCommand(
    name: 'user:add',
    description: 'Create a user who can sign in (email + password, bound to a tenant).',
)]
final class UserAddCommand extends BaseCommand
{
    #[InjectAsReadonly]
    protected PlatformUserRepositoryInterface $users;

    #[InjectAsReadonly]
    protected PasswordHasher $hasher;

    protected function configure(): void
    {
        $this->setName('user:add')
            ->setDescription('Create a user who can sign in (email + password, bound to a tenant).')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Sign-in address')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Password (prompted for when omitted, which keeps it out of the shell history)')
            ->addOption('tenant', null, InputOption::VALUE_REQUIRED, 'Tenant id this user administers; omit only for an owner', '')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Display name', '')
            ->addOption('role', null, InputOption::VALUE_REQUIRED, 'owner | admin | editor', UserRole::Admin->value);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = PlatformUserRepository::normalizeEmail((string) $input->getOption('email'));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $output->writeln('<error>A valid --email is required.</error>');

            return Command::INVALID;
        }

        $role = UserRole::tryFrom((string) $input->getOption('role'));
        if ($role === null) {
            $output->writeln('<error>--role must be one of: owner, admin, editor.</error>');

            return Command::INVALID;
        }

        $tenantId = trim((string) $input->getOption('tenant'));
        if ($tenantId === '' && $role !== UserRole::Owner) {
            $output->writeln('<error>--tenant is required for every role except owner.</error>');

            return Command::INVALID;
        }

        if ($this->users->findByEmail($email, $tenantId) !== null) {
            $output->writeln(sprintf('<error>%s already exists%s.</error>', $email, $tenantId !== '' ? ' for tenant ' . $tenantId : ''));

            return Command::FAILURE;
        }

        $password = (string) $input->getOption('password');
        if ($password === '') {
            $password = $this->askForPassword($input, $output);
        }

        try {
            $hash = $this->hasher->hash($password);
        } catch (\InvalidArgumentException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return Command::INVALID;
        }

        $this->users->save(new PlatformUser(
            id: Uuid7::generate(),
            email: $email,
            passwordHash: $hash,
            displayName: trim((string) $input->getOption('name')),
            tenantId: $tenantId,
            role: $role,
        ));

        $output->writeln(sprintf(
            '<info>Created %s as %s%s.</info>',
            $email,
            $role->value,
            $tenantId !== '' ? ' of ' . $tenantId : '',
        ));

        return Command::SUCCESS;
    }

    private function askForPassword(InputInterface $input, OutputInterface $output): string
    {
        $helper = $this->getHelper('question');
        $question = new Question('Password: ');
        $question->setHidden(true);
        $question->setHiddenFallback(false);

        return (string) $helper->ask($input, $output, $question);
    }
}
