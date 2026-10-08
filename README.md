# Semitexa Platform User

`semitexa/platform-user`

The identity store behind the `semitexa/auth` contracts: people who sign in, their password credentials, roles and tenant binding, and the lockout that follows repeated failed sign-ins.

## Install

Not included by the installer. Add it to an existing project from the project root:

```bash
docker compose run --rm --no-deps --user "$(id -u):$(id -g)" app composer require semitexa/platform-user
bin/semitexa server:restart
bin/semitexa orm:sync
```

`semitexa/os` already requires it.

## What it provides

- The `platform_user` table, the `PlatformUser` domain model and `PlatformUserRepositoryInterface`.
- `PlatformUserProvider`, bound to `Semitexa\Auth\Domain\Contract\UserProviderInterface`, and `UserAuthenticator` (password check, lockout).
- Roles `owner`, `admin`, `editor`.
- Lockout settings: `PLATFORM_USER_MAX_ATTEMPTS` (default 5) and `PLATFORM_USER_LOCK_SECONDS` (default 900).
- Console commands:
  - `bin/semitexa user:add --email=… [--tenant=…] [--role=owner|admin|editor] [--name=…]` (`--tenant` is required for every role except owner; the password is prompted for when omitted);
  - `bin/semitexa user:list [--tenant=…] [--all]`;
  - `bin/semitexa user:password --email=…` (also clears a lockout);
  - `bin/semitexa user:disable --email=… [--enable]`.

## Documentation

Commands: https://semitexa.com/docs/reference/commands-user

## License

MIT, see [LICENSE](LICENSE).
