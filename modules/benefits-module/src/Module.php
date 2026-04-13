<?php

declare(strict_types=1);

namespace Anateje\BenefitsModule;

use Anateje\BenefitsModule\Http\DeleteBenefitHandler;
use Anateje\BenefitsModule\Http\ListBenefitsHandler;
use Anateje\BenefitsModule\Http\SaveBenefitHandler;
use Anateje\Contracts\Route;
use Anateje\Contracts\RouteProvider;

final class Module implements RouteProvider
{
    public function routes(): array
    {
        return [
            new Route('GET', '/api/v2/benefits', ListBenefitsHandler::class),
            new Route('POST', '/api/v2/benefits', SaveBenefitHandler::class),
            new Route('POST', '/api/v2/benefits/delete', DeleteBenefitHandler::class),
        ];
    }
}

