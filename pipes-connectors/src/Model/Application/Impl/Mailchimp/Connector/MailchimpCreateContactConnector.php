<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\Connector;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\MailchimpApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\Utils\Exception\PipesFrameworkException;

/**
 * Class MailchimpCreateContactConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\Connector
 */
final class MailchimpCreateContactConnector extends ConnectorAbstract
{

    public const string NAME = 'mailchimp_create_contact';

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ApplicationInstallException
     * @throws ConnectorException
     * @throws CurlException
     * @throws PipesFrameworkException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $applicationInstall = $this->getApplicationInstallFromProcess($dto);
        $apiEndpoint        = $applicationInstall->getSettings()[MailchimpApplication::API_KEYPOINT];

        $response = $this->getSender()->send(
            $this->getApplication()->getRequestDto(
                $dto,
                $applicationInstall,
                CurlManager::METHOD_POST,
                sprintf(
                    '%s/3.0/lists/%s/members/',
                    $apiEndpoint,
                    $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][MailchimpApplication::AUDIENCE_ID],
                ),
                $dto->getData(),
            ),
        );

        $json = $response->getJsonBody();

        unset($json['type'], $json['detail'], $json['instance']);

        $statusCode = $response->getStatusCode();
        $this->evaluateStatusCode($statusCode, $dto);

        return $dto->setJsonData($json);
    }

}
