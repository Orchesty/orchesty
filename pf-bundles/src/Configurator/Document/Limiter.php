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
#[ODM\Index( // Used by Limiter (pkg/mongo/indices.go)
    keys: ['limitKey' => 'asc'],
    name: 'IK_limiter_limitKey',
)]
#[ODM\Index( // Used by Limiter (pkg/mongo/indices.go)
    keys: ['limitKey' => 'asc', 'allowedAt' => 'asc', 'inProcess' => 'asc', 'prioritize' => 'desc'],
    name: 'IK_limiter_limitKey_allowedAt_inProcess_prioritize',
)]
#[ODM\Index( // Used by Limiter (pkg/mongo/indices.go)
    keys: ['allowedAt' => 'asc', 'created' => 'asc'],
    name: 'IK_limiter_allowedAt_created',
)]
#[ODM\Index( // Used by Limiter (pkg/mongo/indices.go)
    keys: ['prioritize' => 'asc', 'created' => 'asc', 'message.headers.node-id' => 'asc', 'message.headers.node-name' => 'asc', 'message.headers.user' => 'asc', 'message.headers.topology-id' => 'asc', 'message.headers.application' => 'asc'],
    name: 'IK_limiter_prioritize_created_messageHeadersNodeId_messageHeadersNodeName_messageHeadersUser_messageHeadersTopologyId_messageHeadersApplication',
)]
#[ODM\Index( // Used by Limiter (pkg/mongo/indices.go)
    keys: ['message.headers.correlation-id' => 'asc', 'prioritize' => 'asc'],
    name: 'IK_limiter_messageHeadersCorrelationId_prioritize',
)]
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
