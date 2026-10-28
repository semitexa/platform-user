<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Semitexa\Mail\Domain\Contract\MailServiceInterface;
use Semitexa\Mail\Domain\Enum\MailDispatchStatus;
use Semitexa\Mail\Domain\Model\MailDispatchResult;
use Semitexa\Mail\Domain\Model\MailEnvelope;
use Semitexa\Mail\Domain\Model\MailSendOptions;
use Semitexa\Platform\User\Application\Service\MailPasswordResetMailer;
use Semitexa\Platform\User\Domain\Model\PlatformUser;

/**
 * The reset link by mail, out of the box: the project wrote the mailer
 * itself, every time.
 */
final class MailPasswordResetMailerTest extends TestCase
{
    /** @var list<MailEnvelope> */
    private array $sent = [];

    #[Test]
    public function the_link_goes_to_the_configured_address_with_the_token_in_it(): void
    {
        $mailer = $this->mailer('https://museum.example/reset-password?token={token}');

        $mailer->sendResetLink(self::user(), 'a+b/c=', new \DateTimeImmutable('+60 minutes'));

        self::assertCount(1, $this->sent);
        self::assertSame('ada@museum.test', $this->sent[0]->to[0]->email);
        self::assertSame('museum', $this->sent[0]->tenantId);
        self::assertStringContainsString('https://museum.example/reset-password?token=a%2Bb%2Fc%3D', (string) $this->sent[0]->textBody, 'the token, URL-encoded');
        self::assertStringContainsString('60 minutes', (string) $this->sent[0]->textBody);
    }

    #[Test]
    public function without_an_address_with_a_token_place_it_refuses(): void
    {
        foreach (['', 'https://museum.example/reset', '/reset?token={token}', 'javascript:alert(1)//{token}'] as $template) {
            $mailer = $this->mailer($template);
            self::assertFalse($mailer->isConfigured(), $template);
            try {
                $mailer->sendResetLink(self::user(), 't', new \DateTimeImmutable('+1 hour'));
                self::fail("sent with '{$template}'");
            } catch (\LogicException) {
                $this->addToAssertionCount(1);
            }
        }
        self::assertSame([], $this->sent);
    }

    /** MailService answers a failure with a status: unseen, a token was issued and nothing sent, unlogged. */
    #[Test]
    public function a_mail_that_was_not_sent_is_an_error_the_service_logs(): void
    {
        $mailer = $this->mailer('https://museum.example/reset?token={token}', MailDispatchStatus::Failed);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not sent');

        $mailer->sendResetLink(self::user(), 'secret-token', new \DateTimeImmutable('+1 hour'));
    }

    private function mailer(string $template, MailDispatchStatus $status = MailDispatchStatus::Queued): MailPasswordResetMailer
    {
        $sent = &$this->sent;
        $mailer = new MailPasswordResetMailer();
        (new \ReflectionProperty(MailPasswordResetMailer::class, 'resetUrl'))->setValue($mailer, $template);
        (new \ReflectionProperty(MailPasswordResetMailer::class, 'mail'))->setValue($mailer, new class ($sent, $status) implements MailServiceInterface {
            /** @param list<MailEnvelope> $sent */
            public function __construct(private array &$sent, private MailDispatchStatus $status) {}
            public function send(MailEnvelope $envelope, ?MailSendOptions $options = null): MailDispatchResult
            {
                $this->sent[] = $envelope;

                return new MailDispatchResult($this->status, errorCode: 'config_error', errorMessage: 'no mailer');
            }
        });

        return $mailer;
    }

    private static function user(): PlatformUser
    {
        return new PlatformUser(id: '0192f6a0-0000-7000-8000-00000000000a', email: 'ada@museum.test', passwordHash: 'x', displayName: 'Ada', tenantId: 'museum');
    }
}
