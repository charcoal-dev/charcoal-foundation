<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Interfaces\Engine\Scripts\Config;

use App\Shared\Core\Cli\DomainScriptBase;
use App\Shared\Core\Cli\LogPolicy;
use App\Shared\Exceptions\CliScriptException;
use Charcoal\Base\Support\Helpers\ObjectHelper;
use Charcoal\Database\Exceptions\QueryExecuteException;
use Charcoal\Filesystem\Exceptions\FilesystemException;

/**
 * Class ImportCountries
 * @package App\Interfaces\Engine\Scripts\Config
 * @api
 */
class ImportCountries extends DomainScriptBase
{
    /**
     * @return LogPolicy
     */
    protected function declareExecutionLogging(): LogPolicy
    {
        return new LogPolicy(false);
    }

    /**
     * @return void
     */
    protected function onConstructHook(): void
    {
        $this->config->displayAppClassBanner = false;
        $this->config->displayScriptName = false;
    }

    /**
     * @return void
     * @throws CliScriptException
     */
    protected function execScript(): void
    {
        // Read Data File
        $this->print('Looking for {yellow}{b}countries.csv{/} file...');

        try {
            $earthCsvPath = $this->getAppBuild()->paths->storage->join("./countries.csv");
            $this->inline(sprintf('Path: {cyan}%s{/} ... ', $earthCsvPath->path));
            $earthCsvPath = $earthCsvPath->isFile()->node();
            $this->print("{green}OK{/}");
            $countriesCsv = $earthCsvPath->read();
        } catch (FilesystemException $e) {
            $this->print("{red}" . ObjectHelper::baseClassName($e) . "{/}");
            throw new CliScriptException($e->getMessage(), previous: $e);
        }

        $countriesCsv = preg_split('(\r\n|\n|\r)', trim($countriesCsv));
        $countriesCount = count($countriesCsv);
        $this->print("");
        $this->print(sprintf("Total Countries Found: {green}{invert}%s{/}", $countriesCount));

        $countriesOrm = $this->getAppBuild()->coreData->countries;
        $db = $countriesOrm->table->getDb();
        foreach ($countriesCsv as $country) {
            $country = explode(",", $country);
            if (!$country) {
                throw new CliScriptException('Failed to read a country line');
            }

            $saveCountryQuery = "INSERT INTO `%s` (`status`, `name`, `region`, `code3`, `code2`, `dial_code`) " .
                "VALUES (:status, :name, :region, :code3, :code2, :dialCode) ON DUPLICATE KEY UPDATE `name`=:name, " .
                "`region`=:region, `code3`=:code3, `code2`=:code2, `dial_code`=:dialCode";
            $saveCountryData = [
                "status" => 0,
                "name" => $country[0],
                "region" => $country[5],
                "code3" => $country[2],
                "code2" => $country[1],
                "dialCode" => $country[6]
            ];

            $this->inline(sprintf('%s {cyan}%s{/} ... ', $saveCountryData["name"], $saveCountryData["code2"]));
            try {
                $saveCountryQuery = $db->exec(sprintf($saveCountryQuery, $countriesOrm->table->name), $saveCountryData);
                $this->print("{green}SUCCESS{/}");
            } catch (QueryExecuteException) {
                $this->print("{red}FAIL{/}");
            }

            unset($country, $saveCountryQuery, $saveCountryData);
        }
    }
}