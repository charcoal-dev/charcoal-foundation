<?php
declare(strict_types=1);

namespace App\Shared;

use App\Shared\Foundation\CoreData\CoreData;
use App\Shared\Foundation\CoreData\CoreDataModule;
use App\Shared\Foundation\Engine\EngineModule;
use App\Shared\Foundation\Http\Http;
use App\Shared\Foundation\Http\HttpModule;
use App\Shared\Foundation\Mailer\Mailer;
use App\Shared\Foundation\Mailer\MailerModule;
use Charcoal\App\Kernel\Build\AppBuildEnum;
use Charcoal\App\Kernel\Build\AppBuildPartial;
use Charcoal\App\Kernel\Build\BuildPlan;

/**
 * Class BuildContext
 * @package App\Shared
 */
enum BuildContext: string implements AppBuildEnum
{
    case GLOBAL = "global";
    case TESTS = "tests";

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->value;
    }

    /**
     * @param AppBuildPartial $app
     * @return BuildPlan
     */
    public function getBuildPlan(AppBuildPartial $app): BuildPlan
    {
        return match ($this) {
            default => new BuildPlan(function (BuildPlan $plan) use ($app) {
                # CoreData Module
                $plan->include("coreData", new CoreDataModule($app, [
                    CoreData::BFC,
                    CoreData::COUNTRIES,
                    CoreData::DB_BACKUPS,
                    CoreData::OBJECT_STORE,
                    CoreData::SYSTEM_ALERTS
                ]));

                # HTTP Module
                $plan->include("http", new HttpModule($app, [
                    Http::INTERFACE_LOG,
                    Http::PROXY_SERVERS,
                    Http::CALL_LOG
                ]));

                # Mailer Module
                $plan->include("mailer", new MailerModule($app, [
                    Mailer::BACKLOG
                ]));

                # Engine Module
                $plan->include("engine", new EngineModule($app));
            })
        };
    }

    /**
     * @return bool
     */
    public function setErrorHandlers(): bool
    {
        return match ($this) {
            self::TESTS => false,
            default => true,
        };
    }
}