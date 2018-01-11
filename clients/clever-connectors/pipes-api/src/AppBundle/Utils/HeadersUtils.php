<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Utils;

use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;

/**
 * Class HeadersUtils
 *
 * @package CleverConnectors\AppBundle\Utils
 */
class HeadersUtils
{

    private const STOP  = 1003;
    private const LIMIT = 1004;

    /**
     * @param ProcessDto  $dto
     * @param null|string $message
     *
     * @return ProcessDto
     */
    public static function setStopHeaderToDto(ProcessDto $dto, ?string $message = NULL): ProcessDto
    {
        return self::setResultCodeToDto($dto, self::STOP, $message);
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public static function setLimitHeaderToDto(ProcessDto $dto): ProcessDto
    {
        return self::setResultCodeToDto($dto, self::LIMIT);
    }

    /**
     * @param SuccessMessage $successMessage
     *
     * @return SuccessMessage
     */
    public static function setLimitHeaderToMessage(SuccessMessage $successMessage): SuccessMessage
    {
        $successMessage->addHeader(self::getResultCodeKey(), strval(self::LIMIT));

        return $successMessage;
    }

    /**
     * @param ProcessDto  $dto
     * @param int         $code
     * @param null|string $message
     *
     * @return ProcessDto
     */
    private static function setResultCodeToDto(ProcessDto $dto, int $code, ?string $message = NULL): ProcessDto
    {
        $headers                           = $dto->getHeaders();
        $headers[self::getResultCodeKey()] = $code;

        if ($message) {
            $headers[self::getMessageKey()] = $message;
        }

        $dto->setHeaders($headers);

        return $dto;
    }

    /**
     * @return string
     */
    private static function getResultCodeKey(): string
    {
        return CMHeaders::createKey(CMHeaders::RESULT_CODE);
    }

    /**
     * @return string
     */
    private static function getMessageKey(): string
    {
        return CMHeaders::createKey(CMHeaders::RESULT_MESSAGE);
    }

}