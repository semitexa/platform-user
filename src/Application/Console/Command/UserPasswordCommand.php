<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Console\Command;

use Semitexa\Core\Attribute\AsCommand;
use Semitexa\Core\Attribute\InjectAsReadonly;
use Semitexa\Core\Console\BaseCommand;
use Semitexa\Platform\User\Application\Db\MySQL\Repository\PlatformUserRepository;
use Semitexa\Platform\User\Application\Service\PasswordHasher;
use Semitexa\Platform\User\Domain\Contract\PlatformUserRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Set a new password. Also the way out of a lockout — a fresh password clears
 * the failure counter, so an operator never has to wait one out.
 */
#[AsCommand(
    name: 'user:password',
    description: 'Set a user password (also clears any lockout).',
)]
final class UserPasswordCommand extends BaseCommand
{
    #[InjectAsReadonly]
    protected PlatformUserRepositoryInterface $users;

    #[InjectAsReadonly]
    protected PasswordHasher $hasher;

    protected function configure(): void
    {
        $this->setName('user:password')
            ->setDescription('Set a user password (also clears any lockout).')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Whose password to set')
            ->addOption('tenant', null, InputOption::VALUE_REQUIRED, 'Tenant id, when the address exists for more than one', '')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'New password (prompted for when omitted)');
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

        $password = (string) $input->getOption('password');
        if ($password === '') {
            $helper = $this->getHelper('question');
            $question = new Question('New password: ');
            $question->setHidden(true);
            $question->setHiddenFallback(false);
            $password = (string) $helper->ask($input, $output, $question);
        }

        try {
            $hash = $this->hasher->hash($password);
        } catch (\InvalidArgumentException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return Command::INVALID;
        }

        $this->users->update($user->withPasswordHash($hash));
        $output->writeln(sprintf('<info>Password set for %s.</info>', $user->getEmail()));

        return Command::SUCCESS;
    }
}
