<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Core\Config\Builder\Traits;

use Charcoal\Yaml\Parser;

/**
 * Provides functionality for reading and parsing YAML configuration files.
 * @api
 */
trait YamlConfigFilesTrait
{
    /**
     * @param string $configFilepath
     * @return array
     * @throws \Charcoal\Yaml\Exception\YamlParseException
     */
    final protected function readYamlConfigFiles(string $configFilepath): array
    {
        return (new Parser(evaluateBooleans: true, evaluateNulls: true))->getParsed($configFilepath);
    }
}