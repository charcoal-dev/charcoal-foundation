<?php
declare(strict_types=1);

namespace App\Shared\Core;

use Charcoal\Events\Event;

/**
 * Class Events
 * @package App\Shared\Core
 */
class Events extends \Charcoal\App\Kernel\Events
{
    /**
     * Triggers when a system alert is raised internally
     * @return Event
     */
    public function onSystemAlert(): Event
    {
        return $this->on("app.system.alert");
    }
}