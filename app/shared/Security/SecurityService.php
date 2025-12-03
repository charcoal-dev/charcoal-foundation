<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Security;

use App\Shared\CharcoalApp;
use App\Shared\Security\BruteForceControl\BruteForceControl;
use Charcoal\App\Kernel\AbstractApp;

/**
 * Represents the security service used for handling security-related operations within the application.
 * @property CharcoalApp $app
 */
final readonly class SecurityService extends \Charcoal\App\Kernel\Security\SecurityService
{
    public BruteForceControl $bruteForceControl;

    public function __construct()
    {
        parent::__construct();
        $this->bruteForceControl = new BruteForceControl();
    }

    /**
     * @param AbstractApp $app
     * @return void
     */
    public function bootstrap(AbstractApp $app): void
    {
        parent::bootstrap($app);
        $this->bruteForceControl->bootstrap($this);
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);
        $this->bruteForceControl = new BruteForceControl();
    }
}