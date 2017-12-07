<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 12/7/17
 * Time: 11:03 AM
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\FacebookLeads\Mapper;

use CleverConnectors\AppBundle\Model\CM\SubscriptionConnector\CustomerObject\CMSubscriber;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class FacebookCreatedLeadformMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\FacebookLeads\Mapper
 */
class FacebookCreatedLeadformMapper implements CustomNodeInterface
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data = json_decode($dto->getData(), TRUE);

        if (empty($data) || !array_key_exists('field_data', $data)) {
            return $this->setHeadersToStop($dto);
        }

        $subscriber = new CMSubscriber();
        $subscriber->setForeignId($data['id']);

        foreach ($data['field_data'] as $rec) {
            switch ($rec['name']) {
                case 'email':
                    $subscriber->setEmail($rec['values'][0]);
                    break;

                case 'first_name':
                    $subscriber->setFirstName($rec['values'][0]);
                    break;

                case 'last_name':
                    $subscriber->setLastName($rec['values'][0]);
                    break;

                case 'full_name':
                    $fullName = explode(' ', $rec['values'][0]);
                    $subscriber->setFirstName($fullName[0]);
                    $subscriber->setLastName($fullName[1]);
                    break;
            }
        }

        return $dto->setData(json_encode($subscriber->toArray()));
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    protected function setHeadersToStop(ProcessDto $dto): ProcessDto
    {
        $headers       = $dto->getHeaders();
        $key           = CMHeaders::createKey(CMHeaders::RESULT_CODE);
        $headers[$key] = 1003;
        $dto->setHeaders($headers);

        return $dto;
    }

}