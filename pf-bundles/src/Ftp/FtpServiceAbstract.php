<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Ftp;

use Hanaboso\PipesFramework\Ftp\Adapter\FtpAdapterInterface;

/**
 * Class FtpServiceAbstract
 *
 * @package Hanaboso\PipesFramework\Ftp
 */
abstract class FtpServiceAbstract implements FtpServiceInterface
{

    /**
     * @var FtpAdapterInterface
     */
    protected $adapter;

    /**
     * FtpServiceAbstract constructor.
     *
     * @param FtpAdapterInterface $adapter
     */
    public function __construct(FtpAdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

}