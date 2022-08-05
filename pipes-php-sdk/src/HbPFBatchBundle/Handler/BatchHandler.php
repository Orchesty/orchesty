<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFBatchBundle\Handler;

use Hanaboso\CommonsBundle\Process\BatchProcessDto;
use Hanaboso\PipesPhpSdk\Batch\Exception\BatchException;
use Hanaboso\PipesPhpSdk\Batch\Model\BatchManager;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\HbPFBatchBundle\Loader\BatchLoader;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class BatchHandler
 *
 * @package Hanaboso\PipesPhpSdk\HbPFBatchBundle\Handler
 */
final class BatchHandler
{

    /**
     * BatchHandler constructor.
     *
     * @param BatchManager $connManager
     * @param BatchLoader  $loader
     */
    function __construct(private BatchManager $connManager, private BatchLoader $loader)
    {
    }

    /**
     * @param string $id
     *
     * @return void
     * @throws BatchException
     */
    public function processTest(string $id): void
    {
        $this->loader->getBatch($id);
    }

    /**
     * @param string  $id
     * @param Request $request
     *
     * @return BatchProcessDto
     * @throws BatchException
     * @throws ConnectorException
     */
    public function processAction(string $id, Request $request): BatchProcessDto
    {
        $conn = $this->loader->getBatch($id);

        return $this->connManager->processAction($conn, $request);
    }

    /**
     * @return mixed[]
     */
    public function getBeaches(): array
    {
        return $this->loader->getAllBeaches();
    }

}
