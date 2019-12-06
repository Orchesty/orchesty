<?php declare(strict_types=1);

namespace Hanaboso\Portal\Handler;

use Hanaboso\Portal\Model\Installer\DataTransport;
use Hanaboso\Portal\Model\Installer\Exception\InstallerException;
use Hanaboso\Portal\Model\Installer\Installer;

/**
 * Class InstallerHandler
 *
 * @package Hanaboso\Portal\Handler
 */
class InstallerHandler
{

    /**
     * @var Installer
     */
    private $installer;

    /**
     * InstallerHandler constructor.
     *
     * @param Installer $installer
     */
    public function __construct(Installer $installer)
    {
        $this->installer = $installer;
    }

    /**
     * @param mixed[] $data
     *
     * @return string
     * @throws InstallerException
     */
    public function getInstaller(array $data): string
    {
        $dto = new DataTransport($data['logs'], $data['metrics'], (bool) ($data['database'] ?? FALSE));

        return $this->installer->generate($dto);
    }

}
