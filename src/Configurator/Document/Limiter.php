<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;

/**
 * Class Limiter
 *
 * @package Hanaboso\PipesFramework\Configurator\Document
 */
#[ODM\Document(collection: 'limiter')]
class Limiter
{

    use IdTrait;

    /**
     * @var bool
     */
    #[ODM\Field(type: 'bool')]
    private bool $prioritize;

    /**
     * @var LimiterMessage
     */
    #[ODM\EmbedOne(targetDocument: LimiterMessage::class)]
    private LimiterMessage $message;

    /**
     * @return bool
     */
    public function isPrioritize(): bool
    {
        return $this->prioritize;
    }

    /**
     * @return LimiterMessage
     */
    public function getMessage(): LimiterMessage
    {
        return $this->message;
    }

}
