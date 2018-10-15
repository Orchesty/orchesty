<?php declare(strict_types=1);

namespace Tests;

use Nette\DI\Container;
use PHPUnit\Framework\TestCase;

/**
 * Class ContainerTestCaseAbstract
 *
 * @package Test
 */
abstract class ContainerTestCaseAbstract extends TestCase
{

    /**
     * @var Container
     */
    protected $container;

    /**
     * ContainerTestCaseAbstract constructor.
     *
     * @param string $name
     * @param array  $data
     * @param string $dataName
     */
    public function __construct($name = NULL, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->container = require __DIR__ . '/../../app/bootstrapCli.php';
    }

    /**
     * @param string $fileName
     * @param string $prefix
     *
     * @return string
     */
    protected function getData(string $fileName, string $prefix = ''): string
    {
        $class     = substr(get_called_class(), strrpos(get_called_class(), '\\') + 1);
        $namespace = str_replace(
            '\\',
            '/',
            substr(str_replace([$class, 'Tests\\'], '', get_called_class()), 0, -1)
        );
        if (empty($prefix)) {
            $path = sprintf('%s/%s/data/%s', __DIR__, $namespace, $fileName);
        } else {
            $path = sprintf('%s/%s/%s%s', __DIR__, $namespace, $prefix, $fileName);
        }

        if (!file_exists($path)) {
            $this->fail('File does not exist.');
        }

        return file_get_contents($path);
    }

}