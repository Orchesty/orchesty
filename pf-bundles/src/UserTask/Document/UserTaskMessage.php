<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\UserTask\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class UserTaskMessage
 *
 * @package Hanaboso\PipesFramework\UserTask\Document
 */
#[ODM\EmbeddedDocument]
class UserTaskMessage
{

    public const string BODY    = 'body';
    public const string HEADERS = 'headers';

    public const array PROTECTED_HEADERS = ['identity', 'reply-id', 'reply-to', 'source'];

    public const array HIDDEN_HEADERS = ['reply-id', 'reply-to'];

    /**
     * @var string
     */
    #[ODM\Field(type: 'string')]
    private string $body = '';

    /**
     * @var mixed[]
     */
    #[ODM\Field(type: 'hash')]
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
    public function setBody(string $body): self
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
    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * @param mixed[] $data
     *
     * @return UserTaskMessage
     */
    public function fromArray(array $data): self
    {
        if (array_key_exists(self::BODY, $data)) {
            $this->body = $data[self::BODY];
        }
        if (array_key_exists(self::HEADERS, $data)) {
            $protected     = array_flip(self::PROTECTED_HEADERS);
            $this->headers = [
                ...array_diff_key((array) $data[self::HEADERS], $protected),
                ...array_intersect_key($this->headers, $protected),
            ];
        }

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function toPublicArray(): array
    {
        return [
            self::BODY    => $this->body,
            self::HEADERS => array_diff_key($this->headers, array_flip(self::HIDDEN_HEADERS)),
        ];
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
