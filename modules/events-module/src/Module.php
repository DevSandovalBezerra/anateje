<?php

declare(strict_types=1);

namespace Anateje\EventsModule;

use FastRoute\RouteCollector;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Anateje\EventsModule\Http\Handlers\ListEventsHandler;
use Anateje\EventsModule\Http\Handlers\DetailEventHandler;
use Anateje\EventsModule\Http\Handlers\RegisterEventHandler;
use Anateje\EventsModule\Http\Handlers\CancelEventHandler;
use Anateje\EventsModule\Http\Handlers\AdminListEventsHandler;
use Anateje\EventsModule\Http\Handlers\AdminSaveEventHandler;
use Anateje\EventsModule\Http\Handlers\AdminDeleteEventHandler;
use Anateje\EventsModule\Http\Handlers\AdminBulkStatusEventsHandler;
use Anateje\EventsModule\Http\Handlers\AdminRegistrationsEventHandler;
use Anateje\EventsModule\Http\Handlers\AdminCheckinEventHandler;
use Anateje\EventsModule\Http\Handlers\AdminRegistrationStatusEventHandler;
use Anateje\EventsModule\Http\Handlers\AdminPromoteWaitlistEventHandler;
use Anateje\EventsModule\Http\Handlers\AdminExportCsvEventHandler;

class Module
{
    public static function registerRoutes(RouteCollector $r): void
    {
        $r->addGroup('/events', function (RouteCollector $r) {
            // Public/Member actions
            $r->get('', ListEventsHandler::class);
            $r->get('/{id:\d+}', DetailEventHandler::class);
            $r->post('/register', RegisterEventHandler::class);
            $r->post('/cancel', CancelEventHandler::class);

            // Admin actions
            $r->addGroup('/admin', function (RouteCollector $r) {
                $r->get('', AdminListEventsHandler::class);
                $r->post('/save', AdminSaveEventHandler::class);
                $r->post('/delete', AdminDeleteEventHandler::class);
                $r->post('/bulk-status', AdminBulkStatusEventsHandler::class);
                $r->get('/{id:\d+}/registrations', AdminRegistrationsEventHandler::class);
                $r->post('/checkin', AdminCheckinEventHandler::class);
                $r->post('/registration-status', AdminRegistrationStatusEventHandler::class);
                $r->post('/promote-waitlist', AdminPromoteWaitlistEventHandler::class);
                $r->get('/{id:\d+}/export-csv', AdminExportCsvEventHandler::class);
            });
        });
    }

    public static function registerDependencies(ContainerInterface $container): void
    {
        // Handlers Registration
        $factory = $container->get(Psr17Factory::class);
        $db = $container->get(\Anateje\Contracts\DbConnection::class);
        $auth = $container->get(\Anateje\Contracts\AuthContextProvider::class);
        $permission = $container->get(\Anateje\Contracts\PermissionChecker::class);
        $audit = $container->get(\Anateje\Contracts\AuditLogger::class);

        $container->set(ListEventsHandler::class, fn() => new ListEventsHandler($factory, $db, $auth));
        $container->set(DetailEventHandler::class, fn() => new DetailEventHandler($factory, $db, $auth));
        $container->set(RegisterEventHandler::class, fn() => new RegisterEventHandler($factory, $db, $auth));
        $container->set(CancelEventHandler::class, fn() => new CancelEventHandler($factory, $db, $auth));

        $container->set(AdminListEventsHandler::class, fn() => new AdminListEventsHandler($factory, $db, $auth, $permission));
        $container->set(AdminSaveEventHandler::class, fn() => new AdminSaveEventHandler($factory, $db, $auth, $permission, $audit));
        $container->set(AdminDeleteEventHandler::class, fn() => new AdminDeleteEventHandler($factory, $db, $auth, $permission, $audit));
        $container->set(AdminBulkStatusEventsHandler::class, fn() => new AdminBulkStatusEventsHandler($factory, $db, $auth, $permission, $audit));
        $container->set(AdminRegistrationsEventHandler::class, fn() => new AdminRegistrationsEventHandler($factory, $db, $auth, $permission));
        $container->set(AdminCheckinEventHandler::class, fn() => new AdminCheckinEventHandler($factory, $db, $auth, $permission, $audit));
        $container->set(AdminRegistrationStatusEventHandler::class, fn() => new AdminRegistrationStatusEventHandler($factory, $db, $auth, $permission, $audit));
        $container->set(AdminPromoteWaitlistEventHandler::class, fn() => new AdminPromoteWaitlistEventHandler($factory, $db, $auth, $permission, $audit));
        $container->set(AdminExportCsvEventHandler::class, fn() => new AdminExportCsvEventHandler($factory, $db, $auth, $permission));
    }
}
