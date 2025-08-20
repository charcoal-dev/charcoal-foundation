<?php
declare(strict_types=1);

namespace App\Shared;

/**
 * The enum includes constants for foundational services such as
 * storage, communication, and utility systems, as well as placeholder values.
 */
enum AppBindings
{
    /** @ Foundation App */
    case coreData;
    case http;
    case mailer;
    case engine;

    /** @ Placeholder */
    case ethereal;
}