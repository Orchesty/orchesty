---
layout: main.hbs
collection: documentation
name: Jak vytvořit aplikaci s autorizací OAuth 2
parent: Tutoriály
level: 2
index: 30

lunr: true
tags: oauth2 application aplikace
lang: cs
---

V tomto návodu se naučíme vytvořit integraci se službou, která vyžaduje autorizaci protokolem OAuth 2.0, což je dnes asi nejpoužívanější způsob autorizace ke službám typu SaaS. Protokoly OAuth verze 1 i 2 vyžadují pro ověření uživatele přihlášení k účtu uživatelem pomocí GUI. Aplikace využívající OAuth 1 i 2 proto vytváří v PIPES Admin rozhraní i kompletní formulář pro autorizaci, včetně přesměrování na přihlašovací formulář integrované služby. Pro náš návod jsme zvolili cloudovou službu HubSpot, do které budeme importovat kontakty získané konektorem, který jsme se naučili v rámci [návodu na budování konektoru pro REST API](/docs/cs/tutorials/basic-connector). 

## Co budeme potřebovat?
- Nainstalované PIPES na svém localhostu pro vytvoření nového konektoru. Instalaci můžete provést pomocí návodu [Instalace a spuštění PIPES](/docs/cs/installation).
- Připravenou službu s implementovaným balíčkem SDK, registrovanou v PIPES pro přímou integraci. Pokud službu ještě nemáte, podívejte se na kapitolu [Jak nastavit vlastní službu s využitím SDK pro přímou integraci s PIPES](/docs/cs/tutorials/sdk-settings/).
- Konektor na získání testovacích dat připravený v rámci návodu [Jak vytvořit konektor pro volání REST API](/docs/cs/tutorials/basic-connector  "Jak vytvořit konektor pro volání REST API").
- Doporučujeme nastudovat návod [Jak vytvořit aplikaci s basic autentizací](/docs/cs/tutorials/basic-application).

## Příprava aplikace
Vytvoříme třídu aplikace, která bude rozšiřovat abstrakci OAuth2ApplicationAbstract.

``` PHP 1

use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract;

final class HubSpotApplication extends OAuth2ApplicationAbstract
{

    ... required methods from interface
}
```

Následující kroky budou stejné, jako v případě Basic aplikace. Tentokrát musíme formulářem předat vše potřebné pro získání autorizačního tokenu. V případě aplikace HubSpot to vypadá následovně:

``` PHP 2

use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationInterface;

...

public function getSettingsForm(): Form
{
    $form = new Form();
    $form
        ->addField(new Field(Field::TEXT, OAuth2ApplicationInterface::CLIENT_ID, 'Client Id', NULL, TRUE))
        ->addField(new Field(Field::PASSWORD, OAuth2ApplicationInterface::CLIENT_SECRET, 'Client Secret', TRUE))
        ->addField(new Field(Field::TEXT, self::APP_ID, 'Application Id', NULL, TRUE));

    return $form;
}
```

``` infoBlock
Pokud jsme jako klíče fieldů použili konstanty <b>OAuth2ApplicationInterface::CLIENT_ID</b> a <b>OAuth2ApplicationInterface::CLIENT_SECRET</b>, není potřeba přetěžovat metodu <b>isAuthorized</b>.
Jak tuto metodu přetížit je popsáno v kapitole <a href="/docs/cs/tutorials/basic-application/">Jak vytvořit aplikaci s basic autentizací</a>.
```

Vytvoříme metodu pro sestavení požadavku:

``` PHP 3

public function getRequestDto(ApplicationInstall $applicationInstall, string $method, ?string $url = NULL, ?string $data = NULL): RequestDto
{
    $request = new RequestDto($method, $this->getUri($url ?? self::BASE_URL));
    $request->setHeaders(
        [
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => sprintf('Bearer %s', $this->getAccessToken($applicationInstall)),
        ]
    );

    if (isset($data)) {
        $request->setBody($data);
    }

    return $request;
}
```

Pro získání access tokenu protokolu OAuth 2.0 budeme potřebovat ještě následující metody. První metodou je getAuthUrl která vrací endpoint HubSpotu pro zahájení autorizace.

``` PHP 4

public function getAuthUrl(): string
{
    return 'https://app.hubspot.com/oauth/authorize';
}
```

Dále potřebujeme metodu getTokenUrl, která vrací endpoint pro získání samotného access tokenu.

``` PHP 5

public function getTokenUrl(): string
{
    return 'https://api.hubapi.com/oauth/v1/token';
}
```

Nakonec zaregistrujeme aplikaci jako službu:

``` YAML 6

# ./config/application/application.yaml
services:
    hbpf.application.hub-spot:
        class: Pipes\PhpSdk\Application\HubSpotApplication
        arguments:
            - '@hbpf.providers.oauth2_provider'
```

Tím máme aplikaci připravenou. Můžeme se přihlásit do uživatelského rozhraní PIPES Admin, kde bychom měli nalézt naši novou aplikaci v nabídce **Appstore**.
![](/uploads/scr_oauth2/1_hubspot_app_install1.png "HubSpot application")

Nyní aplikaci nainstalujeme a vyzkoušíme si autorizaci pomocí OAuth 2.0. V detailu aplikace klikneme na tlačítko **Instalovat**. Do zobrazeného formuláře vložíme autorizační údaje k našemu účtu v HubSpot. Tyto údaje získáme po přihlášení do služby v záložce **Apps** a vybereme aplikaci.

![](/uploads/scr_oauth2/2_hubspot_apps.png "Hubspot -> Apps")

Poté přejdeme na záložku **Auth**, kde uvidíme potřebné údaje: **App ID**, **Client ID** a **Client Secret**.
![](/uploads/scr_oauth2/2_hubspot_auth.png "Hubspot -> Apps -> Auth")

## Vytvoření konektoru

``` infoBlock
Doporučujeme nejprve prostudovat návod <a href="/docs/cs/tutorials/basic-connector">Jak vytvořit konektor pro volání REST API</a>.
```

Nejdřív tedy vytvoříme třídu konektoru. Konektor bude vytvářet nové kontakty v aplikaci HubSpot. Využijeme proto endpoint, který umožňuje vytvořit v aplikaci HubSpot kontakty dávkově, viz [dokumentace HubSpot API](https://developers.HubSpot.com/docs/methods/contacts/batch_create_or_update). Třídu nazveme **HubSpotCreateMultipleContactsConnector**.

``` PHP 7

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessEventNotSupportedTrait;

final class HubSpotCreateMultipleContactsConnector extends ConnectorAbstract
{

    use ProcessEventNotSupportedTrait;
    
    private CurlManager $sender;
    
    private ApplicationInstallRepository $repository;
    
    public function __construct(DocumentManager $dm, CurlManager $sender)
    {
        $this->sender     = $sender;
        $this->repository = $dm->getRepository(ApplicationInstall::class);
    }
    
    ... required methods from interface
}
```

Doplníme identifikátor konektoru.
``` PHP 8

public function getId(): string
{
    return 'hub-spot.create-multiple-contacts';
}
```

Nyní zpracujeme data získaná procesem a vytvoříme požadavek.

``` infoBlock
Mapování dat přímo v konektoru není nejlepším řešením. Takový postup omezuje znovupoužitelnost konektoru a kód se v případě opakovaného volání vykonává víckrát, než je nutné. Tato ukázka je pouze ilustrativní. Správné řešení si ukážeme níže.
```

``` PHP 9

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\Utils\String\Json;
use Pipes\PhpSdk\Application\HubSpotApplication;

...

public function processAction(ProcessDto $dto): ProcessDto
{
    $data = $this->getJsonContent($dto);
    $body = [];
    
    foreach ($data as $user) {
        if (!isset($user['name'], $user['email'], $user['phone'])) {
            throw new ConnectorException('Some data is missing. Keys [name, email, phone] is required.');
        }

        $name   = explode(' ', $user['name']);
        $body[] = [
            'email'      => $user['email'],
            'properties' => [
                [
                    'property' => 'firstname',
                    'value'    => $name[0],
                ],
                [
                    'property' => 'lastname',
                    'value'    => $name[1] ?? '',
                ],
                [
                    'property' => 'phone',
                    'value'    => $user['phone'],
                ],
            ],
        ];
    }

    $response = $this->sender->send(
            $this->getApplication()->getRequestDto(
                $applicationInstall,
                CurlManager::METHOD_POST,
                sprintf('%s/contacts/v1/contact/batch/', HubspotApplication::BASE_URL),
                Json::encode($body)
            )
        );
}
```

Zbývá ještě zpracovat odpověď.

``` PHP 10

$message  = $response->getJsonBody()['validationResults'][0]['message'] ?? NULL;
            $this->evaluateStatusCode($response->getStatusCode(), $dto, $message);

            $dto->setData($response->getBody());
```

Celý konektor pak bude vypadat následovně:

``` PHP 11

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessEventNotSupportedTrait;
use Hanaboso\Utils\String\Json;
use Pipes\PhpSdk\Application\HubSpotApplication;

final class HubSpotCreateMultipleContactsConnector extends ConnectorAbstract
{

    use ProcessEventNotSupportedTrait;

    private CurlManager $sender;

    private ApplicationInstallRepository $repository;

    public function __construct(DocumentManager $dm, CurlManager $sender)
    {
        $this->sender     = $sender;
        $this->repository = $dm->getRepository(ApplicationInstall::class);
    }

    public function getId(): string
    {
        return 'hub-spot.create-multiple-contacts';
    }

    public function processAction(ProcessDto $dto): ProcessDto
    {
        $applicationInstall = $this->repository->findUsersAppDefaultHeaders($dto);
        $data               = $this->getJsonContent($dto);
        $body = [];
        
        foreach ($data as $user) {
            if (!isset($user['name'], $user['email'], $user['phone'])) {
                throw new ConnectorException('Some data is missing. Keys [name, email, phone] is required.');
            }

            $name   = explode(' ', $user['name']);
            $body[] = [
                'email'      => $user['email'],
                'properties' => [
                    [
                        'property' => 'firstname',
                        'value'    => $name[0],
                    ],
                    [
                        'property' => 'lastname',
                        'value'    => $name[1] ?? '',
                    ],
                    [
                        'property' => 'phone',
                        'value'    => $user['phone'],
                    ],
                ],
            ];
        }

        try {
            $response = $this->sender->send(
                $this->getApplication()->getRequestDto(
                    $applicationInstall,
                    CurlManager::METHOD_POST,
                    sprintf('%s/contacts/v1/contact', HubspotApplication::BASE_URL),
                    Json::encode($body)
                )
            );
            $message  = $response->getJsonBody()['validationResults'][0]['message'] ?? NULL;
            $this->evaluateStatusCode($response->getStatusCode(), $dto, $message);

            $dto->setData($response->getBody());
        } catch (CurlException | ConnectorException $e) {
            throw new OnRepeatException($dto, $e->getMessage(), $e->getCode(), $e);
        }

        return $dto;
    }
}
```

A nesmíme zapomenout zaregistrovat službu konektoru. Službu pojmenujeme **hub-spot.create-multiple-contacts**.

``` YAML 12

# ./config/connector/connector.yaml
service:
    hbpf.connector.hub-spot.create-multiple-contacts:
        class: Pipes\PhpSdk\Connector\HubSpot\HubSpotCreateMultipleContactsConnector
        arguments:
            - '@doctrine_mongodb.odm.default_document_manager'
            - '@hbpf.transport.curl_manager'
        calls:
            - ['setApplication', ['@hbpf.application.hub-spot']]
```

## Vytvoření integračního procesu

Přistoupíme k sestavení jednoduchého procesu, který přenese data kontaktů mezi dvěma cloudovými službami. V admin rozhraní vytvoříme nový proces pomocí **File -> New topology**. Na canvas vložíme Start event a propojíme ho s naším dříve vytvořeným konektorem pro získání testovacích kontaktů. Na canvas tedy přetáhneme z tollbaru prvek Connector a v pravém side baru mu nastavíme script test_users_connector.

![](/uploads/scr_oauth2/3_newtopo_bpmn_select.png "HubSpot - getUser connector")

Do procesu zapojíme další prvek Connector a vybereme mu script našeho konektoru **hub-spot.create-multiple-contacts**.

![](/uploads/scr_oauth2/4_hubspot_topology_bpmn.png "HubSpot - create multiple contact connector")

Uložíme a proces publikujeme. Tím máme hotový jednoduchý integrační proces a můžeme vyzkoušet jeho funkčnost. Přejdeme do záložky metrik procesu a spustíme instanci tlačítkem **Start** v bloku Start eventu. Náš proces neočekává žádná data, necháme tedy pole pro data prázdné a potvrdíme spuštění.

![](/uploads/scr_oauth2/5_run_node_script_newtopo.png "Spuštění procesu")

Pokud jsme vše provedli správně, v našem HubSpot účtu vidíme nově naimportované kontakty.

![](/uploads/scr_oauth2/6_hubspot_contacts.png "Kontakty v Hubspot")

## Optimalizace procesu
Ukázali jsme si, jak vytvořit jednoduchý konektor pro uložení dávky kontaktů do služby HubSpot. Vysvětlili jsme si také, že mapování dat přímo v konektoru není optimální z několika důvodů. V případě opakovaného volání, které může nastat při neúspěšném volání a využití Repeateru, se takový kód vykonává zbytečně víckrát. Navíc konektor ztrácí svou znovupoužitelnost. Mnohem lepší je použít mapování dat v samostatném scriptu. Vytvoříme tedy novou třídu, kterou pojmenujeme například **HubSpotCreateContactMapper**. Vytvořit nový mapper je opravdu jednoduché.

``` PHP 13

use Hanaboso\PipesPhpSdk\CustomNode\CustomNodeAbstract;

final class HubSpotCreateMultipleContactsMapper extends CustomNodeAbstract
{

    ... required methods from interface
}
```
 

Nám stačí vložit kód, kterým jsme předtím mapovali přímo v konektoru a předat výstup do procesu.

``` PHP 14

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\Utils\String\Json;

...

public function process(ProcessDto $dto): ProcessDto
{
    $data = Json::decode($dto->getData());
    $body = [];

    foreach ($data as $user) {
        if (!isset($user['name'], $user['email'], $user['phone'])) {
            throw new ConnectorException('Some data is missing. Keys [name, email, phone] is required.');
        }

        $name   = explode(' ', $user['name']);
        $body[] = [
            'email'      => $user['email'],
            'properties' => [
                [
                    'property' => 'firstname',
                    'value'    => $name[0],
                ],
                [
                    'property' => 'lastname',
                    'value'    => $name[1] ?? '',
                ],
                [
                    'property' => 'phone',
                    'value'    => $user['phone'],
                ],
            ],
        ];
    }

    return $dto->setData(Json::encode($body));
}
```

Zaregistrujeme službu mapperu.

``` YAML 15

# ./config/custom_node/custom_node.yaml
services:
    hbpf.custom_node.hub-spot.create-multiple-contact-mapper:
        class: Pipes\PhpSdk\Mapper\HubSpot\HubSpotCreateMultipleContactsMapper
```

A ještě musíme upravit původní kód konektoru. Metoda processAction pak bude vypadat následovně:

``` PHP 16

public function processAction(ProcessDto $dto): ProcessDto
{
    $applicationInstall = $this->repository->findUsersAppDefaultHeaders($dto);
    $body               = $this->getJsonContent($dto);

    try {
        $response = $this->sender->send(
            $this->getApplication()->getRequestDto(
                $applicationInstall,
                CurlManager::METHOD_POST,
                sprintf('%s/contacts/v1/contact/batch/', HubspotApplication::BASE_URL),
                Json::encode($body)
            )
        );
        $message  = $response->getJsonBody()['validationResults'][0]['message'] ?? NULL;
        $this->evaluateStatusCode($response->getStatusCode(), $dto, $message);

        $dto->setData($response->getBody());
    } catch (CurlException | ConnectorException $e) {
        throw new OnRepeatException($dto, $e->getMessage(), $e->getCode(), $e);
    }

    return $dto;
}
```

Nyní se přihlásíme do Admin aplikace a přepneme se do našeho procesu. Z toolbaru přetáhneme prvek Custom script a vložíme ho mezi naše dva konektory.

![](/uploads/scr_oauth2/7_hubspot_mapper.png "HubSpot - optimalizace procesu")

Update procesu vždy vytváří jeho novou verzi. Nemusíme se tedy bát neotestovaného provozu. Je třeba ještě přepnout novou verzi na aktivní.

Aktivní novou verzi můžeme nyní vyzkoušet. Tím máme vytvořený jednoduchý proces, který importuje dávku stažených dat do SaaS aplikace HubSpot. Endpoint pro vložení dávky kontaktů v HubSpot je nespornou výhodou jejich API. Bohužel takový komfort nebývá v REST API cloudových služeb samozřejmostí. 
V příštím návodu se proto naučíme, [jak vytvořit aplikaci, která využívá autorizaci OAuth1](/docs/cs/tutorials/oauth1-application). Pokud se chcete raději naučit, [jak používat Batch splitter v integračním procesu](/docs/cs/tutorials/batch-splitter), můžete tuto kapitolu přeskočit a vrátit se k ní, až budete OAuth1 potřebovat.
