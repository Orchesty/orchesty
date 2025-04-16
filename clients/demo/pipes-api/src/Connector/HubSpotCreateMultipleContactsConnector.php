<?php declare(strict_types=1);

namespace Demo\Connector;

/**
 * Class HubSpotCreateMultipleContactsConnector
 *
 * @package Demo\Connector
 */
final class HubSpotCreateMultipleContactsConnector extends HubSpotCreateContactAbstract
{

    public const string NAME = 'hub-spot.create-multiple-contacts';

    /**
     * @var string
     */
    protected string $contactUrl = 'contacts/v1/contact/batch/';

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::NAME;
    }

}
