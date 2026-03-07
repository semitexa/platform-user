<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\WmApp;

use Semitexa\Platform\Wm\Application\Attribute\AsWmApp;

#[AsWmApp(id: 'user-profile', title: 'User Profile', entryUrl: '/platform/users/view', icon: '👤', desktop: false)]
final class UserProfileWmApp {}
