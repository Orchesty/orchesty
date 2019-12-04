<?php declare(strict_types=1);

namespace Demo\CustomNode;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\CustomNode\CustomNodeAbstract;

/**
 * Class IdnesConnector
 *
 * @package Demo\CustomNode
 */
class IdnesConnector extends CustomNodeAbstract
{

    /**
     * @var CurlManager
     */
    private $curlManager;

    /**
     * IdnesConnector constructor.
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
     * @throws CurlException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $requestDto = new RequestDto('GET', new Uri('https://www.idnes.cz/'));
        $requestDto->setDebugInfo($dto);

        $this->curlManager->send($requestDto);

        return $dto;
    }

}
