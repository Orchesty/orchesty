<?php declare(strict_types=1);

namespace Tests;

use Nette\Utils\Strings;

/**
 * Class ConnectorTestCaseAbstract
 *
 * @package Tests
 */
abstract class ConnectorTestCaseAbstract extends DatabaseTestCaseAbstract
{

    /**
     * @return string
     */
    protected function getRequest(): string
    {
        $class     = Strings::substring(get_called_class(), strrpos(get_called_class(), '\\') + 1);
        $namespace = str_replace(
            '\\',
            '/',
            Strings::substring(str_replace([$class, 'Tests\\'], '', get_called_class()), 0, -1)
        );
        $path      = sprintf('%s/%s/data/%s', __DIR__, $namespace, str_replace('Test', '.json', $class));

        if (!file_exists($path)) {
            file_put_contents($path, '{"key": "Example content"}');
            $this->fail('Please change file content');
        }

        return file_get_contents($path);
    }

}