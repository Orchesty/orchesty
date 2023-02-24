<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps;

use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;

/**
 * Class AwsObjectConnectorAbstract
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps
 */
abstract class AwsObjectConnectorAbstract extends ConnectorAbstract
{

    protected const QUERY  = 'query';
    protected const RESULT = 'result';

    protected const BUCKET = 'Bucket';
    protected const KEY    = 'Key';
    protected const SOURCE = 'SourceFile';
    protected const TARGET = 'SaveAs';

    protected const NAME    = 'name';
    protected const CONTENT = 'content';

    /**
     * @var ApplicationInstallRepository $repository
     */
    protected ApplicationInstallRepository $repository;

    /**
     * @return string
     */
    abstract protected function getCustomName(): string;

    /**
     * @param mixed[] $parameters
     * @param mixed[] $content
     *
     * @throws ConnectorException
     */
    protected function checkParameters(array $parameters, array $content): void
    {
        foreach ($parameters as $parameter) {
            if (!isset($content[$parameter])) {
                throw new ConnectorException(
                    sprintf("Connector '%s': Required parameter '%s' is not provided!", $this->getName(), $parameter),
                );
            }
        }
    }

}
