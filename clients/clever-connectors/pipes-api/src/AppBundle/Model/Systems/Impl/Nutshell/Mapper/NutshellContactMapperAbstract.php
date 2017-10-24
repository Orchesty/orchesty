<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Nutshell\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriptionConnector\CustomerObject\CMSubscriber;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Utils\PipesHeaders;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Nette\Utils\Json;
use Nette\Utils\Strings;

/**
 * Class NutshellContactMapperAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Nutshell\Mapper
 */
abstract class NutshellContactMapperAbstract implements CustomNodeInterface
{

    protected const CREATE = 'create';
    protected const UPDATE = 'update';
    protected const DELETE = 'delete';

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data = Json::decode($dto->getData(), TRUE);

        if (!isset($data['payloads'][0]['emails'][0]['value'])) {
            throw new CleverConnectorsException(
                'Missing required email field in data.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $subscriber = new CMSubscriber();
        $subscriber->setEmail($data['payloads'][0]['emails'][0]['value']);

        if (isset($data['payloads'][0]['name'])) {
            $name     = $data['payloads'][0]['name'];
            $position = strrpos($name, ' ');

            if ($position !== FALSE) {
                $subscriber
                    ->setFirstName(Strings::substring($name, 0, $position))
                    ->setLastName(Strings::substring($name, $position + 1));
            } else {
                $subscriber->setLastName($name);
            }

        }

        if (isset($data['payloads'][0]['id'])) {
            $subscriber->setForeignId(explode('-', $data['payloads'][0]['id'])[0]);
        }

        return $dto->setData(Json::encode($subscriber->toArray()));
    }

    /**
     * @param ProcessDto $dto
     * @param string     $action
     *
     * @return ProcessDto
     */
    protected function getNeededAction(ProcessDto $dto, string $action): ProcessDto
    {
        $data = Json::decode($dto->getData(), TRUE);

        if ($data['events'][0]['payloadType'] !== 'contacts' || $data['events'][0]['action'] !== $action) {
            return $dto->setHeaders(array_merge($dto->getHeaders(), [
                PipesHeaders::createKey(PipesHeaders::RESULT_CODE)    => 1003,
                PipesHeaders::createKey(PipesHeaders::RESULT_STATUS)  => 'DO_NOT_CONTINUE',
                PipesHeaders::createKey(PipesHeaders::RESULT_MESSAGE) => sprintf(
                    'Data does not contains contact %s event',
                    $action
                ),
                PipesHeaders::createKey(PipesHeaders::RESULT_DETAIL)  => '',
            ]));
        }

        return $dto;
    }

}