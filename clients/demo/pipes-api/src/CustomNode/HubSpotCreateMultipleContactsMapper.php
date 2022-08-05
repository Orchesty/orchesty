<?php declare(strict_types=1);

namespace Demo\CustomNode;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\CustomNode\CommonNodeAbstract;
use Hanaboso\Utils\String\Json;
use Hanaboso\Utils\System\PipesHeaders;

/**
 * Class HubSpotCreateMultipleContactsMapper
 *
 * @package Demo\CustomNode
 */
final class HubSpotCreateMultipleContactsMapper extends CommonNodeAbstract
{

    public const NAME = 'hub-spot.create-multiple-contacts-mapper';

    /**
     * @return string
     */
    function getName(): string
    {
        return self::NAME;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $data      = Json::decode($dto->getData());
        $pipesUser = $dto->getHeader(PipesHeaders::USER, '');
        $pipesUser = is_array($pipesUser) ? reset($pipesUser) : $pipesUser;
        $body      = [];

        foreach ($data as $user) {
            if (!isset($user['name'], $user['email'], $user['phone'])) {
                throw new ConnectorException('Some data is missing. Keys [name, email, phone] is required.');
            }

            $name   = explode(' ', $user['name']);
            $body[] = [
                'email'      => $user['email'],
                'properties' => [
                    [
                        'property' => 'firstname',
                        'value'    => sprintf('%s-%s', $pipesUser, $name[0]),
                    ],
                    [
                        'property' => 'lastname',
                        'value'    => $name[1] ?? '',
                    ],
                    [
                        'property' => 'phone',
                        'value'    => $user['phone'],
                    ],
                ],
            ];
        }

        return $dto->setJsonData($body);
    }

}
