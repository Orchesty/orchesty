<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Connector;

use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use DateTime;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Nette\Utils\Arrays;

/**
 * Class ZohoUpdatedContactConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Connector
 */
class ZohoUpdatedContactConnector extends ZohoCronConnectorAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'zoho-updated-contact-connector';
    }

    /**
     * @param array $data
     *
     * @return bool
     * @throws SystemException
     */
    protected function isEmpty(array $data): bool
    {
        parent::isEmpty($data);

        return array_key_exists('nodata', $data['response']);
    }

    /**
     * @param mixed $data
     * @param int   $i
     *
     * @return SuccessMessage
     * @throws SystemException
     */
    protected function createSuccessMessage($data, int $i): SuccessMessage
    {
        if (array_key_exists('result', $data['response'])
            && array_key_exists('Contacts', $data['response']['result'])
            && array_key_exists('row', $data['response']['result']['Contacts'])
        ) {
            $successMessage = new SuccessMessage($i);
            $data           = $data['response']['result']['Contacts']['row'];
            if (!Arrays::isList($data)) {
                $data = [$data];
            }

            $successMessage->setData(json_encode($data));
            unset($data);

            return $successMessage;
        } else {
            throw new SystemException(
                'Bad response data for ZOHO update request.',
                SystemException::MISSING_RESPONSE_DATA
            );
        }
    }

    /**
     * @param RequestDto    $dto
     * @param int           $page
     * @param DateTime|null $from
     *
     * @return Uri
     */
    protected function getUri(RequestDto $dto, int $page, ?DateTime $from = NULL): Uri
    {
        $i = $page * self::ITEMS_PER_PAGE;

        return new Uri(sprintf(urldecode($dto->getUri(TRUE)) . '&fromIndex=%s&toIndex=%s%s',
            'getRecords',
            $i - self::ITEMS_PER_PAGE,
            $i - 1,
            $this->formatTime($from)
        ));
    }

}