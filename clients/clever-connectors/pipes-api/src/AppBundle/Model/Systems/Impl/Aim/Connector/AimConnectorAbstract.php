<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Aim\Connector;

use CleverConnectors\AppBundle\Traits\LoggerTrait;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Psr\Log\LoggerAwareInterface;

abstract class AimConnectorAbstract implements ConnectorInterface, LoggerAwareInterface
{

    use LoggerTrait;

    /**
     * @var string
     */
    private $type;
    /**
     * @var string
     */
    private $url;

    /**
     * @param string $type
     * @param string $url
     */
    public function __construct(string $type, string $url)
    {
        $this->type = $type;
        $this->url  = $url;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException(
            'Aim has no support for Events!',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT
        );
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $headers = $dto->getHeaders();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'aim-' . $this->type;
    }

    /**
     * @param ProcessDto $dto
     */
    private function actionUpsert(ProcessDto $dto): void
    {

    }

    /**
     * @param ProcessDto $dto
     */
    private function actionDelete(ProcessDto $dto): void
    {

    }

}
