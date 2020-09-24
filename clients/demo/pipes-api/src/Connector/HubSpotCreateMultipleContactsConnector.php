<?php declare(strict_types=1);

namespace Demo\Connector;

/**
 * Class HubSpotCreateMultipleContactsConnector
 *
 * @package Demo\Connector
 */
final class HubSpotCreateMultipleContactsConnector extends HubSpotCreateContactAbstract
{

    /**
     * @var string
     */
    protected string $contactUrl = 'contacts/v1/contact/batch/';

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'hub-spot.create-multiple-contacts';
    }

}
