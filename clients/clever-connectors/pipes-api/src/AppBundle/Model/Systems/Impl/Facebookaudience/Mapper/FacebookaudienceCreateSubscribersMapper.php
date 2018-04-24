<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Nette\Utils\Json;

/**
 * Class FacebookaudienceCreateSubscribersMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Mapper
 */
class FacebookaudienceCreateSubscribersMapper implements CustomNodeInterface
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data = Json::decode($dto->getData(), TRUE);

        if (!is_array($data) || empty($data)) {
            throw new CleverConnectorsException('Missing data', CleverConnectorsException::MISSING_DATA);
        }

        $hashedEmails = [];
        foreach ($data as $item) {
            if (array_key_exists(CleverFieldsEnum::EMAIL, $item)) {
                $hashedEmails[] = hash('sha256', $item[CleverFieldsEnum::EMAIL]);
            }
        }

        return $dto->setData(Json::encode([
            'payload' => [
                'schema' => 'EMAIL_SHA256',
                'data'   => $hashedEmails,
            ],
        ]));
    }

}