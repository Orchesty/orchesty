<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift\Connector;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift\RedshiftApplication;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use PgSql\Result;
use Throwable;

/**
 * Class RedshiftExecuteQueryConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift\Connector
 */
final class RedshiftExecuteQueryConnector extends RedshiftObjectConnectorAbstract
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
        $content = $dto->getJsonData();
        $this->checkParameters([self::QUERY], $content);

        $applicationInstall = $this->getApplicationInstallFromProcess($dto);
        /** @var RedshiftApplication $application */
        $application = $this->getApplication();
        $connection  = $application->getConnection($applicationInstall);

        try {
            /** @var resource $c */
            $c = $connection;
            /** @var Result $result */
            $result = pg_query($c, $content[self::QUERY]);
        } catch (Throwable){
            throw new ConnectorException(sprintf("Connector 'redshift-query': %s", pg_last_error($connection)));
        }

        if (!pg_fetch_row($result)) {
            return $dto->setJsonData([self::RESULT => pg_affected_rows($result)]);
        }

        return $dto->setJsonData([self::RESULT => pg_fetch_row($result)]);
    }

    /**
     * @return string
     */
    protected function getCustomName(): string
    {
        return 'query';
    }

}
