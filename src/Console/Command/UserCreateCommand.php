<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Console\Command;

use Semitexa\Core\Attributes\AsCommand;
use Semitexa\Core\Console\Command\BaseCommand;
use Semitexa\Orm\OrmManager;
use Semitexa\Platform\User\Application\Resource\PlatformUserRepository;
use Semitexa\Platform\User\Application\Resource\PlatformUserResource;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'user:create', description: 'Create a new platform user')]
class UserCreateCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->setName('user:create')
            ->setDescription('Create a new platform user')
            ->addArgument('email', InputArgument::OPTIONAL, 'User email')
            ->addArgument('password', InputArgument::OPTIONAL, 'User password')
            ->addArgument('name', InputArgument::OPTIONAL, 'User name')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force creation even if user exists');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');

        $io->title('Create Platform User');

        $email = $input->getArgument('email');
        if (!$email) {
            $question = new Question('Enter email: ');
            $question->setValidator(function ($answer) {
                if (empty($answer) || !filter_var($answer, FILTER_VALIDATE_EMAIL)) {
                    throw new \RuntimeException('Please enter a valid email address');
                }
                return $answer;
            });
            $email = $helper->ask($input, $output, $question);
        }

        $password = $input->getArgument('password');
        if (!$password) {
            $question = new Question('Enter password: ');
            $question->setHidden(true);
            $question->setValidator(function ($answer) {
                if (empty($answer) || strlen($answer) < 8) {
                    throw new \RuntimeException('Password must be at least 8 characters long');
                }
                return $answer;
            });
            $password = $helper->ask($input, $output, $question);
        }

        $name = $input->getArgument('name');
        if (empty($name)) {
            $question = new Question('Enter name (optional): ', '');
            $name = $helper->ask($input, $output, $question);
        }

        try {
            return OrmManager::run(function (OrmManager $orm) use ($input, $output, $io, $helper, $email, $password, $name) {
                $repo = new PlatformUserRepository($orm->getAdapter());

                $existing = $repo->findByEmail($email);
                if ($existing !== null) {
                    if (!$input->getOption('force')) {
                        $io->error("User with email '{$email}' already exists. Use --force to overwrite.");
                        return Command::FAILURE;
                    }

                    $question = new ConfirmationQuestion(
                        "User '{$email}' already exists. Overwrite? [y/N]: ",
                        false
                    );
                    if (!$helper->ask($input, $output, $question)) {
                        $io->info('Cancelled.');
                        return Command::SUCCESS;
                    }

                    $existing->name = $name ?: $existing->name;
                    $existing->password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $repo->save($existing);

                    $io->success("User '{$email}' updated successfully!");
                    return Command::SUCCESS;
                }

                $user = new PlatformUserResource();
                $user->email = $email;
                $user->name = $name ?: '';
                $user->password_hash = password_hash($password, PASSWORD_DEFAULT);
                $user->is_active = true;

                $repo->save($user);

                $io->success("User '{$email}' created successfully!");
                $io->table(
                    ['Field', 'Value'],
                    [
                        ['ID', $user->id],
                        ['Email', $user->email],
                        ['Name', $user->name ?: '(empty)'],
                    ]
                );

                return Command::SUCCESS;
            });
        } catch (\Throwable $e) {
            $io->error('Failed to create user: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
