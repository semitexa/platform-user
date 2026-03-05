<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\App;

use Semitexa\Platform\Wm\Application\Attribute\AsWmApp;

#[AsWmApp(id: 'user-management', title: 'Users', entryUrl: '/platform/users', icon: '👥', permission: 'users.list')]
final class UserManagementWmApp {}
