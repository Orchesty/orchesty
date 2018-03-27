<?php
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 3/26/18
 * Time: 4:34 PM
 */

namespace Demo\CustomNode;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Utils\PipesHeaders;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

class IdnesConnector implements CustomNodeInterface
{

    /**
     * @var CurlManager
     */
    private $curlManager;

    /**
     * GoogleConnector constructor.
     *
     * @param CurlManager $curlManager
     */
    public function __construct(CurlManager $curlManager)
    {
        $this->curlManager = $curlManager;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $requestDto = new RequestDto('GET', new Uri('https://www.idnes.cz/'));
        $requestDto->setDebugInfo(PipesHeaders::debugInfo($dto->getHeaders()));

        $this->curlManager->send($requestDto);

        return $dto;
    }

}