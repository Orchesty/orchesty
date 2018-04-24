<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\Mapper;

use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class ZendeskUpdateUserMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\Mapper
 */
class ZendeskUpdateUserMapper implements CustomNodeInterface
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data  = json_decode($dto->getData(), TRUE);
        $field = CMHeaders::get(CMHeaders::CM_EVENT_TYPE, $dto->getHeaders()) ?? '';

        $contact = [
            'user' => [
                'user_fields' => [
                    CleverCustomKeysEnum::getFromType($field) => TRUE,
                ],
            ],
        ];

        return $dto->setData(json_encode([
            'id'   => $data[CleverFieldsEnum::FOREIGN_ID],
            'body' => json_encode($contact),
        ]));
    }

}