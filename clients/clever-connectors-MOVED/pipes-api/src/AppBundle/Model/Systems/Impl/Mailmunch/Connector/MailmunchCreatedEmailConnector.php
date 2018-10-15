<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Mailmunch\Connector;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use function GuzzleHttp\Psr7\parse_query;

/**
 * Class MailmunchCreatedEmailConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Mailmunch\Connector
 */
class MailmunchCreatedEmailConnector implements ConnectorInterface
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'mailmunch-created-email-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        $arr = parse_query($dto->getData(), TRUE);
        if (!is_array($arr) || empty($arr)) {
            throw new CleverConnectorsException(
                'Empty data.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        return $dto->setData(json_encode($arr));
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException('Mailmunch has not implemented "processAction" function.');
    }

}