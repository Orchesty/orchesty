<?php declare(strict_types=1);

namespace Tests;

/**
 * Class DatabaseTestCaseAbstract
 *
 * @package Tests
 */
abstract class DatabaseTestCaseAbstract extends KernelTestCaseAbstract
{

    use TestCaseTrait;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase();
    }

}
