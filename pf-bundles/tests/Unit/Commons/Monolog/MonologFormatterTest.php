<?php declare(strict_types=1);

namespace Tests\Unit\Commons\Monolog;

use Hanaboso\PipesFramework\Commons\Monolog\MonologFormatter;
use Hanaboso\PipesFramework\User\Model\User\UserManagerException;
use PHPUnit\Framework\TestCase;

/**
 * Class MonologFormatterTest
 *
 * @package Tests\Unit\Commons\Monolog
 */
class MonologFormatterTest extends TestCase
{

    /**
     *
     */
    public function testFormatException(): void
    {
        $exception = new UserManagerException('Email does not exists!', UserManagerException::USER_EMAIL_NOT_EXISTS);
        $expected  = 'Hanaboso\PipesFramework\User\Model\User\UserManagerException 1201: Email does not exists!';
        $this->assertEquals($expected, MonologFormatter::formatException($exception));
    }

    /**
     *
     */
    public function testFormatString(): void
    {
        $this->assertEquals('String :)', MonologFormatter::formatString('String :)'));
    }

}