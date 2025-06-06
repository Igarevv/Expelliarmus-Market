<?php

namespace Modules\Brand\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Modules\Manager\Models\Manager;
use Modules\User\Users\Contracts\UserInterface;
use Modules\User\Users\Enums\RolesEnum;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class BrandPolicy
{
    use HandlesAuthorization;

    public function before(UserInterface|Manager|null $user, string $ability): ?true
    {
        if ($user instanceof Manager && $user->isSuperManager()) {
            return true;
        }

        return null;
    }

    public function view(?Manager $manager): Response
    {
        return $manager?->hasPermissionTo('show_brands', RolesEnum::MANAGER->toString())
            ? $this->allow()
            : throw new AccessDeniedHttpException('Access denied.');
    }

    public function manage(?Manager $manager): Response
    {
        return $manager?->hasPermissionTo('manage_brands', RolesEnum::MANAGER->toString())
            ? $this->allow()
            : throw new AccessDeniedHttpException('Access denied.');
    }
}
