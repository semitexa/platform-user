<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\WmApp;

use Semitexa\Platform\Wm\Application\Attribute\AsWmApp;

#[AsWmApp(id: 'user-create', title: 'Create User', entryUrl: '/platform/users/create', icon: '➕', desktop: false)]
final class UserCreateWmApp {}
