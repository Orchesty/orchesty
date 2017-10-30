<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 25.10.17
 * Time: 16:02
 */

namespace CleverConnectors\AppBundle\Model\Requester;

use CleverConnectors\AppBundle\Document\SystemInstall;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;

/**
 * Interface RequesterInterface
 */
interface RequesterInterface
{

<<<<<<< f9762458354e140a163cc0f6960f05b0694ad1a7
    public const OBJECT = 'object';
=======
    public const OBJECT      = 'object';
    public const WEBHOOK_URL = 'webhook_url';
    public const WEBHOOK_ID  = 'webhook_id';
>>>>>>> Webhooks: refactored

    /**
     * @param array $data
     *
     * @return RequestDto
     */
    public function getRequestDto(array $data): RequestDto;

    /**
     * @param ResponseDto   $responseDto
     * @param SystemInstall $systemInstall
     *
     * @return mixed
     */
    public function processResponse(ResponseDto $responseDto, SystemInstall $systemInstall);

}