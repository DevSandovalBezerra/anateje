<?php

declare(strict_types=1);

namespace Anateje\MembersModule;

use Anateje\Contracts\Route;
use Anateje\Contracts\RouteProvider;
use Anateje\MembersModule\Http\DeleteMemberHandler;
use Anateje\MembersModule\Http\GetMemberHandler;
use Anateje\MembersModule\Http\ListMembersHandler;
use Anateje\MembersModule\Http\SaveMemberHandler;

final class Module implements RouteProvider
{
    public function routes(): array
    {
        return [
            new Route('GET', '/api/v2/members', ListMembersHandler::class),
            new Route('GET', '/api/v2/members/{id:\d+}', GetMemberHandler::class),
            new Route('POST', '/api/v2/members', SaveMemberHandler::class),
            new Route('POST', '/api/v2/members/delete', DeleteMemberHandler::class),
        ];
    }
}

