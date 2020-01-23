<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift\Connector;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift\RedshiftApplication;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Throwable;

/**
 * Class RedshiftExecuteQueryConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift\Connector
 */
class RedshiftExecuteQueryConnector extends RedshiftObjectConnectorAbstract
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ApplicationInstallException
     * @throws ConnectorException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $content = $this->getJsonContent($dto);
        $this->checkParameters([self::QUERY], $content);

        $applicationInstall = $this->getApplicationInstall($dto);
        /** @var RedshiftApplication $application */
        $application = $this->getApplication();
        $connection  = $application->getConnection($applicationInstall);

        try {
            /** @var Resource $result */
            $result = pg_query($connection, $content[self::QUERY]);
        } catch (Throwable $e) {
            throw $this->createException(pg_last_error($connection));
        }

        if (!pg_fetch_row($result)) {
            return $this->setJsonContent($dto, [self::RESULT => pg_affected_rows($result)]);
        }

        return $this->setJsonContent($dto, [self::RESULT => pg_fetch_row($result)]);
    }

    /**
     * @return string
     */
    protected function getCustomId(): string
    {
        return 'query';
    }

}
