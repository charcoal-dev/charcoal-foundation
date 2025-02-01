<?php
declare(strict_types=1);

namespace App\Shared;

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
                //$plan->include("coreData", );
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