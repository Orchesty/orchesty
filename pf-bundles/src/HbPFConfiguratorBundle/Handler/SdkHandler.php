<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;
use Hanaboso\CommonsBundle\Utils\ControllerUtils;
use Hanaboso\PipesFramework\Configurator\Document\Sdk;
use Hanaboso\PipesFramework\Configurator\Model\SdkManager;

/**
 * Class SdkHandler
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler
 */
final class SdkHandler
{

    /**
     * @var SdkManager
     */
    private $manager;

    /**
     * SdkHandler constructor.
     *
     * @param SdkManager $manager
     */
    public function __construct(SdkManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @return array
     */
    public function getAll(): array
    {
        return [
            'items' => array_map(
                function (Sdk $sdk): array {
                    return $sdk->toArray();
                },
                $this->manager->getAll()
            ),
        ];
    }

    /**
     * @param string $id
     *
     * @return array
     * @throws DocumentNotFoundException
     */
    public function getOne(string $id): array
    {
        return $this->get($id)->toArray();
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws PipesFrameworkException
     */
    public function create(array $data): array
    {
        ControllerUtils::checkParameters([Sdk::KEY, Sdk::VALUE], $data);

        return $this->manager->create($data)->toArray();
    }

    /**
     * @param string $id
     * @param array  $data
     *
     * @return array
     * @throws DocumentNotFoundException
     */
    public function update(string $id, array $data): array
    {
        return $this->manager->update($this->get($id), $data)->toArray();
    }

    /**
     * @param string $id
     *
     * @return array
     * @throws DocumentNotFoundException
     */
    public function delete(string $id): array
    {
        return $this->manager->delete($this->get($id))->toArray();
    }

    /**
     * @param string $id
     *
     * @return Sdk
     * @throws DocumentNotFoundException
     */
    private function get(string $id): Sdk
    {
        return $this->manager->getOne($id);
    }

}
