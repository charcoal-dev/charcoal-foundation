<?php
declare(strict_types=1);

namespace App\Interfaces\Engine\Scripts\Config;

use App\Shared\Core\Cli\AppAwareCliScript;
use App\Shared\Core\Cli\ScriptExecutionLogBinding;
use App\Shared\Exception\CliScriptException;
use Charcoal\Database\Exception\QueryExecuteException;

/**
 * Class ImportCountries
 * @package App\Interfaces\Engine\Scripts\Config
 */
class ImportCountries extends AppAwareCliScript
{
    /**
     * @return ScriptExecutionLogBinding
     */
    protected function declareExecutionLogging(): ScriptExecutionLogBinding
    {
        return new ScriptExecutionLogBinding(false);
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
        $earthCsvPath = $this->getAppBuild()->directories->storage->pathToChild("countries.csv");
        $this->inline(sprintf('Path: {cyan}%s{/} ... ', $earthCsvPath));
        if (!@is_file($earthCsvPath)) {
            $this->print('{red}Not Found{/}');
            throw new CliScriptException('Countries CSV files not found in path');
        }

        if (!@is_readable($earthCsvPath)) {
            $this->print('{red}Not Readable{/}');
            throw new CliScriptException('Countries CSV file is not readable');
        }

        $this->print("{green}OK{/}");

        $countriesCsv = file_get_contents($earthCsvPath);
        if (!$countriesCsv) {
            throw new CliScriptException('Failed to read countries CSV file');
        }

        $countriesCsv = preg_split('(\r\n|\n|\r)', trim($countriesCsv));
        $countriesCount = count($countriesCsv);
        $this->print("");
        $this->print(sprintf("Total Countries Found: {green}{invert}%s{/}", $countriesCount));
        // Todo: dump value to execution log

        $countriesOrm = $this->getAppBuild()->coreData->countries;
        $db = $countriesOrm->table->getDb();
        foreach ($countriesCsv as $country) {
            $country = explode(",", $country);
            if (!$country) {
                throw new CliScriptException('Failed to read a country line');
            }

            $saveCountryQuery = "INSERT INTO `%s` (`status`, `name`, `region`, `code`, `code_short`, `dial_code`) " .
                "VALUES (:status, :name, :region, :code, :codeShort, :dialCode) ON DUPLICATE KEY UPDATE `name`=:name, " .
                "`region`=:region, `code3`=:code3, `code2`=:code3, `dial_code`=:dialCode";
            $saveCountryData = [
                "status" => 0,
                "name" => $country[0],
                "region" => $country[5],
                "code3" => $country[2],
                "code2" => $country[1],
                "dialCode" => $country[6]
            ];

            $this->inline(sprintf('%s {cyan}%s{/} ... ', $saveCountryData["name"], $saveCountryData["code"]));
            try {
                $saveCountryQuery = $db->exec(sprintf($saveCountryQuery, $countriesOrm->table->name), $saveCountryData);
                // Todo: dump value to execution log
                $this->print("{green}SUCCESS{/}");
            } catch (QueryExecuteException) {
                // Todo: dump value to execution log
                $this->print("{red}FAIL{/}");
            }

            unset($country, $saveCountryQuery, $saveCountryData);
        }
    }
}