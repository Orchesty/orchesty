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
 * Class MailchimpTagContactConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\Connector
 */
final class MailchimpTagContactConnector extends ConnectorAbstract
{

    public const NAME = 'mailchimp_tag_contact';

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
                    '%s/3.0/lists/%s/segments/%s/members',
                    $apiEndpoint,
                    $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][MailchimpApplication::AUDIENCE_ID],
                    $applicationInstall->getSettings()[MailchimpApplication::SEGMENT_ID],
                ),
                $dto->getData(),
            ),
        );

        $json       = $response->getJsonBody();
        $statusCode = $response->getStatusCode();
        $this->evaluateStatusCode($statusCode, $dto);

        return $dto->setJsonData($json);
    }

}
