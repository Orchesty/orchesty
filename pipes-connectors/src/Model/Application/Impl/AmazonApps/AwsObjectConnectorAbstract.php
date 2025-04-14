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

    protected const string QUERY  = 'query';
    protected const string RESULT = 'result';

    protected const string BUCKET = 'Bucket';
    protected const string KEY    = 'Key';
    protected const string SOURCE = 'SourceFile';
    protected const string TARGET = 'SaveAs';

    protected const string NAME    = 'name';
    protected const string CONTENT = 'content';

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
