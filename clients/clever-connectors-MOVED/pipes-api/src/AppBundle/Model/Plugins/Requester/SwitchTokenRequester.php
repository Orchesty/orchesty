<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Plugins\Requester;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\PluginHeadersEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use CleverConnectors\AppBundle\Model\Requester\RequesterTrait;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;

/**
 * Class SwitchTokenRequester
 *
 * @package CleverConnectors\AppBundle\Model\Plugins\Requester
 */
class SwitchTokenRequester implements RequesterInterface
{

    use RequesterTrait;

    /**
     * @var RequestDto
     */
    private $dto;

    /**
     * SwitchTokenRequester constructor.
     *
     * @param RequestDto $dto
     */
    public function __construct(RequestDto $dto)
    {
        $this->dto = $dto;
    }

    /**
     * @param array $data
     *
     * @return RequestDto
     * @throws CurlException
     */
    public function getRequestDto(array $data): RequestDto
    {
        $headers  = $this->dto->getHeaders();
        $newToken = PluginHeadersEnum::get(PluginHeadersEnum::TOKEN, $headers);
        $body     = json_decode($data['body'], TRUE);
        $oldToken = $body['token'];

        $headers[PluginHeadersEnum::TOKEN] = $oldToken;
        $this->dto->setHeaders($headers);

        $body['token'] = $newToken;

        return $this->dto->setUri($data['uri'])->setBody(json_encode($body));
    }

    /**
     * @param ResponseDto   $responseDto
     * @param SystemInstall $systemInstall
     *
     * @return void
     * @throws CleverConnectorsException
     */
    public function processResponse(ResponseDto $responseDto, SystemInstall $systemInstall): void
    {
        if ($responseDto->getStatusCode() !== 200) {
            throw new CleverConnectorsException(
                'Request to plugin failed | Server is unavailable.',
                CleverConnectorsException::REQUEST_FAILED
            );
        }
    }

}