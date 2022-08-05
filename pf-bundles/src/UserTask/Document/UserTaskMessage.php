<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\UserTask\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class UserTaskMessage
 *
 * @package Hanaboso\PipesFramework\UserTask\Document
 *
 * @ODM\EmbeddedDocument()
 */
class UserTaskMessage
{

    public const BODY    = 'body';
    public const HEADERS = 'headers';

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $body = '';

    /**
     * @var mixed[]
     *
     * @ODM\Field(type="hash")
     */
    private array $headers = [];

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @param string $body
     *
     * @return UserTaskMessage
     */
    public function setBody(string $body): UserTaskMessage
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param mixed[] $headers
     *
     * @return UserTaskMessage
     */
    public function setHeaders(array $headers): UserTaskMessage
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * @param mixed[] $data
     *
     * @return UserTaskMessage
     */
    public function fromArray(array $data): UserTaskMessage
    {
        if (array_key_exists(self::BODY, $data)) {
            $this->body = $data[self::BODY];
        }
        if (array_key_exists(self::HEADERS, $data)) {
            $this->headers = $data[self::HEADERS];
        }

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return [
            self::BODY    => $this->body,
            self::HEADERS => $this->headers,
        ];
    }

}
