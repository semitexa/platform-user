<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Handler\Request;

use Semitexa\Core\Attributes\AsPayloadHandler;
use Semitexa\Core\Attributes\InjectAsReadonly;
use Semitexa\Core\Auth\AuthContextInterface;
use Semitexa\Core\Contract\HandlerInterface;
use Semitexa\Core\Contract\PayloadInterface;
use Semitexa\Core\Contract\ResourceInterface;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Core\Response;
use Semitexa\Platform\User\Application\Payload\Request\LoginPagePayload;

#[AsPayloadHandler(payload: LoginPagePayload::class, resource: GenericResponse::class)]
final class LoginPageHandler implements HandlerInterface
{
    #[InjectAsReadonly]
    protected AuthContextInterface $auth;

    public function handle(PayloadInterface $payload, ResourceInterface $resource): ResourceInterface
    {
        if (!$this->auth->isGuest()) {
            return Response::redirect('/platform');
        }

        return Response::html(self::renderLoginPage());
    }

    private static function renderLoginPage(string $error = ''): string
    {
        $errorHtml = $error !== '' ? '<div style="background:#f38ba8;color:#1e1e2e;padding:10px 16px;border-radius:6px;margin-bottom:16px;font-size:14px;">' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</div>' : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Semitexa Platform</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, sans-serif; background: #1e1e2e; color: #cdd6f4; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { background: #313244; border-radius: 12px; padding: 40px; width: 100%; max-width: 400px; box-shadow: 0 8px 32px rgba(0,0,0,.4); }
        .login-card h1 { font-size: 22px; margin-bottom: 8px; color: #cdd6f4; }
        .login-card p { font-size: 14px; color: #a6adc8; margin-bottom: 24px; }
        .field { margin-bottom: 16px; }
        .field label { display: block; font-size: 13px; color: #a6adc8; margin-bottom: 6px; }
        .field input { width: 100%; padding: 10px 14px; border-radius: 6px; border: 1px solid #45475a; background: #1e1e2e; color: #cdd6f4; font-size: 15px; outline: none; transition: border-color .2s; }
        .field input:focus { border-color: #89b4fa; }
        .submit-btn { width: 100%; padding: 12px; border: none; border-radius: 6px; background: #89b4fa; color: #1e1e2e; font-size: 15px; font-weight: 600; cursor: pointer; transition: background .2s; }
        .submit-btn:hover { background: #74c7ec; }
        .submit-btn:disabled { opacity: .6; cursor: not-allowed; }
    </style>
</head>
<body>
    <div class="login-card">
        <h1>Semitexa Platform</h1>
        <p>Sign in to continue</p>
        <div id="error">{$errorHtml}</div>
        <form id="login-form">
            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" autocomplete="email" required autofocus>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" autocomplete="current-password" required>
            </div>
            <button type="submit" class="submit-btn" id="submit-btn">Sign in</button>
        </form>
    </div>
    <script>
    (function () {
        var form = document.getElementById('login-form');
        var errorDiv = document.getElementById('error');
        var btn = document.getElementById('submit-btn');

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            btn.disabled = true;
            btn.textContent = 'Signing in...';
            errorDiv.innerHTML = '';

            var email = document.getElementById('email').value;
            var password = document.getElementById('password').value;

            fetch('/api/platform/user/login', {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: email, password: password })
            })
            .then(function (r) { return r.json().then(function (d) { return { status: r.status, data: d }; }); })
            .then(function (res) {
                if (res.status === 200 && res.data.user) {
                    window.location.href = '/platform';
                } else {
                    errorDiv.innerHTML = '<div style="background:#f38ba8;color:#1e1e2e;padding:10px 16px;border-radius:6px;margin-bottom:16px;font-size:14px;">' + (res.data.error || 'Invalid credentials') + '</div>';
                    btn.disabled = false;
                    btn.textContent = 'Sign in';
                }
            })
            .catch(function () {
                errorDiv.innerHTML = '<div style="background:#f38ba8;color:#1e1e2e;padding:10px 16px;border-radius:6px;margin-bottom:16px;font-size:14px;">Connection error. Please try again.</div>';
                btn.disabled = false;
                btn.textContent = 'Sign in';
            });
        });
    })();
    </script>
</body>
</html>
HTML;
    }
}
