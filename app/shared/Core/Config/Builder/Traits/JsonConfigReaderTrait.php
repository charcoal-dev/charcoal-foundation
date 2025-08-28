<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Core\Config\Builder\Traits;

use Charcoal\Filesystem\Exceptions\FilesystemException;
use Charcoal\Filesystem\Exceptions\PathNotFoundException;
use Charcoal\Filesystem\Exceptions\PathTypeException;
use Charcoal\Filesystem\Exceptions\PermissionException;
use Charcoal\Filesystem\Path\DirectoryPath;

/**
 * This trait is designed to handle configuration files structured in JSON
 * format, and resolve "imports" by aggregating configuration data
 * based on external references defined within the files.
 */
trait JsonConfigReaderTrait
{
    /**
     * @param DirectoryPath $configDirectory
     * @return array
     */
    protected function readCharcoalJsonConfig(DirectoryPath $configDirectory): array
    {
        $charcoal = $this->readJsonFile($configDirectory);
        $charcoal = $charcoal["charcoal"] ?: null;
        if (!isset($charcoal) || !is_array($charcoal)) {
            throw new \RuntimeException("Configuration file is missing the 'charcoal' node");
        }

        // Resolve imports
        $imports = $charcoal["\$imports"] ?? null;
        if (is_array($imports) && $imports) {
            foreach ($imports as $import) {
                if (!is_string($import) || !$import) {
                    continue;
                }

                $configPath = array_filter(explode(".", preg_replace("/[\[\]]/", ".", $import)));
                if (count($configPath) < 2) {
                    continue;
                }

                unset($configPath[1]);
                $configData = $this->readJsonFile($configDirectory, $configPath);
                $configNode = array_pop($configPath);
                if (isset($configData[$configNode]) && is_array($configData[$configNode])) {
                    $charcoal[$configNode] = $configData[$configNode];
                }
            }
        }

        unset($charcoal["\$imports"]);
        return $charcoal;
    }

    /**
     * @param DirectoryPath $configDirectory
     * @param array $node
     * @return array
     * @internal
     */
    private function readJsonFile(DirectoryPath $configDirectory, array $node = []): array
    {
        $node = $node ?: ["charcoal"];
        $prefix = implode("[", $node) . str_repeat("]", max(0, count($node) - 1));
        unset($node[0]);

        try {
            $filePath = match ($node) {
                [] => $configDirectory->join("charcoal.json"),
                default => $configDirectory->join(implode("/", $node) . ".json"),
            };

            return json_decode(trim($filePath->isFile()->node()->read()), true,
                flags: JSON_THROW_ON_ERROR);
        } catch (PathNotFoundException|PathTypeException $e) {
            throw new \RuntimeException("Configuration could not be resolved: " . $prefix, previous: $e);
        } catch (PermissionException $e) {
            throw new \RuntimeException("Configuration file not readable: " . $prefix, previous: $e);
        } catch (FilesystemException $e) {
            throw new \RuntimeException("Cannot read configuration file: " . $prefix, previous: $e);
        } catch (\JsonException $e) {
            throw new \RuntimeException("Failed to decode configuration node: " . $prefix, previous: $e);
        }
    }
}