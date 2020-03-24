<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\PipesFramework\Configurator\Document\Sdk;
use Hanaboso\PipesFramework\Configurator\Model\SdkManager;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\System\ControllerUtils;

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
    private SdkManager $manager;

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
     * @return mixed[]
     */
    public function getAll(): array
    {
        return ['items' => array_map(static fn(Sdk $sdk): array => $sdk->toArray(), $this->manager->getAll())];
    }

    /**
     * @param string $id
     *
     * @return mixed[]
     * @throws DocumentNotFoundException
     */
    public function getOne(string $id): array
    {
        return $this->get($id)->toArray();
    }

    /**
     * @param mixed[] $data
     *
     * @return mixed[]
     * @throws PipesFrameworkException
     * @throws MongoDBException
     */
    public function create(array $data): array
    {
        ControllerUtils::checkParameters([Sdk::KEY, Sdk::VALUE], $data);

        return $this->manager->create($data)->toArray();
    }

    /**
     * @param string  $id
     * @param mixed[] $data
     *
     * @return mixed[]
     * @throws DocumentNotFoundException
     * @throws MongoDBException
     */
    public function update(string $id, array $data): array
    {
        return $this->manager->update($this->get($id), $data)->toArray();
    }

    /**
     * @param string $id
     *
     * @return mixed[]
     * @throws DocumentNotFoundException
     * @throws MongoDBException
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
