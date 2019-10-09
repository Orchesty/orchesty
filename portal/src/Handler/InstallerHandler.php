<?php declare(strict_types=1);

namespace Hanaboso\Portal\Handler;

use Exception;
use Hanaboso\Portal\Model\Installer\DataTransport;
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
     * @param array $data
     *
     * @return string
     */
    public function getInstaller(array $data): string
    {
        try {
            $dto = new DataTransport($data['first'], $data['second'], $data['third']);

            return $this->installer->createInstaller($dto);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

}
