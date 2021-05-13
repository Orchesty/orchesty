---
layout: main.hbs
collection: documentation
name: Jak vytvořit aplikaci s basic autentizací
parent: Tutoriály
level: 2
index: 20

lunr: true
tags: basic application aplikace
lang: cs
---


V minulém návodu jsme se naučili vytvořit jednoduchý konektor pro volání bez autorizace. Konektory bez autorizace nebo s Basic autorizací nevyžadují závislost na aplikaci. Tento návod nám ukáže, jak vytvořit vlastní aplikaci, která může zajistit autorizaci a nastavení HTTP hlaviček pro sadu konektorů. Zároveň zprostředkuje formulář pro vložení autorizačního tokenu a dalších uživatelských nastavení. Pro tento návod se připojíme ke cloudové službě Sandgrid.

## Co budeme potřebovat?
- Nainstalované PIPES na svém localhostu pro vytvoření nového konektoru. Instalaci můžete provést pomocí návodu [Instalace a spuštění PIPES](/docs/cs/installation).
- Připravenou službu s implementovaným balíčkem SDK, registrovanou v PIPES pro přímou integraci. Pokud službu ještě nemáte, podívejte se na kapitolu [Jak použít vlastní službu s využitím SDK pro přímou integraci s PIPES](/docs/cs/tutorials/sdk-settings/).


## Vytvoření aplikace s Basic autorizací
Vytvoříme třídu aplikace. 

``` PHP 1

use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;

final class SendGridApplication extends BasicApplicationAbstract
{
 
    ... required methods from interface
}
```

## Aplikaci je potřeba definovat následující vlastnosti:

### Application Key

Aplikaci je potřeba definovat tzv. klíč. S tímto klíčem je aplikace zaregistrovaná v Dependency kontejneru.

``` PHP 2

public function getKey(): string 
{
    return 'send-grid';
}
```

### Application Name a Description

Tyto atributy slouží pouze k zobrazování v AppStor UI.

``` PHP 3

public function getName(): string 
{
    return 'SendGrid Application';
}

public function getDescription(): string 
{
    return 'Send Email With Confidence.';
}
```

Každá aplikace vytváří objekt do dokumentové databáze, kam můžeme ukládat atributy potřebné především k zajištění autorizace a další uživatelská nastavení, pokud jsou potřebná. Užitečné jsou například limity API uživatelského účtu volané služby. Abychom tato data mohli vkládat, poskytuje aplikace metodu pro vytvoření formuláře, který se následně zobrazí v detailu aplikace v uživatelském rozhraní. Tento formulář nyní vytvoříme.

``` PHP 4

use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;

...

public function getSettingsForm(): Form 
{
    $form  = new Form();
    $field = new Field(Field::TEXT, 'api_key', 'Api key', NULL, TRUE);

    return $form->addField($field);
}
```

V dalším kroku napíšeme metodu, která připraví data pro sestavení požadavku v konektorech. V této metodě vložíme do požadavku vše, co js společné všem endpointům API integrované služby, tedy nastavení hlaviček a autorizace. V konektorech už pak budeme řešit pouze URL a metodu konkrétního endpointu.

``` PHP 5

use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;

...

public function getRequestDto( ApplicationInstall $applicationInstall, string $method, ?string $url = NULL, ?string $data = NULL): RequestDto 
{
    if (!$this->isAuthorized($applicationInstall)) {
        throw new ApplicationInstallException('Application SendGrid is not authorized!');
    }

    $settings = $applicationInstall->getSettings();
    $token    = $settings[ApplicationInterface::AUTHORIZATION_SETTINGS][self::API_KEY];
    $dto      = new RequestDto(
        $method,
        new Uri($url ?? self::BASE_URL),
        ['Content-Type' => 'application/json', 'Authorization' => sprintf('Bearer %s', $token)]
    );

    if ($data) {
        $dto->setBody($data);
    }

    return $dto;
}
```

Můžete si všimnout, že jsme záměrně použili metodu `isAuthorized`, která vrací true/false podle toho, zda-li uživatel uložil formulář definovaný v metodě `getSettingsForm`.
Tato metoda je připravená v `BasicApplicationAbstract`, ale je možné ji přetížit podle vlastní potřeby. V abstrakci je kontrola buď na `user/password`, nebo `token`.

``` PHP 6

public function isAuthorized(ApplicationInstall $applicationInstall): bool 
{
    return isset($applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_SETTINGS]['api_key']);
}
```

Posledním krokem je registrace aplikace jako služby.

``` YAML 7

# ./config/application/application.yaml
services:
    hbpf.application.send-grid:
        class: Pipes\PhpSdk\Application\SendGridApplication
```

## Vytvoření konektoru aplikace
Nyní vytvoříme samotný konektor, který nám umožní odeslat e-mail pomocí aplikace Sendgrid. Vytvoření konektoru jsme si podrobněji popsali v [předchozím návodu](/docs/cs/tutorials/basic-connector). Dnes si proto ukážeme hlavně použití aplikace. Nejprve vytvoříme třídu konektoru, které předáme naší aplikaci.

``` PHP 8

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessEventNotSupportedTrait;

final class SendGridSendEmailConnector extends ConnectorAbstract
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

Konektor, stejně jako aplikace, vyžaduje definovat Id služby. K tomu slouží metoda `getId`.

``` PHP 9

public function getId(): string 
{
    return 'send-grid.send-email';
}
```


Nyní sestavíme požadavek. Konektor bude očekávat data v následujícím tvaru:

``` JSON 10

{
    "email": "noreply@johndoe.com",
    "name": "John Doe",
    "subject": "Hello, World!"
}
```

Pro sestavení požadavku využijeme aplikaci, která nám zajistí nastavení potřebných HTTP hlaviček včetně autorizace. Zadáme tedy pouze URL našeho požadavku a z předaných dat sestavíme body. 

``` PHP 11

use Hanaboso\Utils\String\Json;

...

$url     = sprintf('%s/mail/send', SendGridApplication::BASE_URL);
$request = $this->getApplication()
    ->getRequestDto($applicationInstall, CurlManager::METHOD_POST, $url, Json::encode($body))
    ->setDebugInfo($dto);

```

Celý konektor pak bude vypadat následovně:

``` PHP 12

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessEventNotSupportedTrait;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\String\Json;
use Pipes\PhpSdk\Application\SendGridApplication;

final class SendGridSendEmailConnector extends ConnectorAbstract
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
        return 'send-grid.send-email';
    }

    public function processAction(ProcessDto $dto): ProcessDto
    {
        $applicationInstall = $this->repository->findUsersAppDefaultHeaders($dto);
        $data               = $this->getJsonContent($dto);
        if (!isset($data['email'], $data['name'], $data['subject'])) {
            throw new ConnectorException('Some data is missing. Keys [email, name, subject] is required.');
        }

        $body = [
            'personalizations' => [
                [
                    'to'      => [
                        [
                            'email' => $data['email'],
                            'name'  => $data['name'],
                        ],
                    ],
                    'subject' => $data['subject'],
                ],
            ],
            'from'             => [
                'email' => 'noreply@johndoe.com',
                'name'  => 'John Doe',
            ],
            'reply_to'         => [
                'email' => 'noreply@johndoe.com',
                'name'  => 'John Doe',
            ],
            'template_id'      => '1',
        ];

        $url     = sprintf('%s/mail/send', SendGridApplication::BASE_URL);
        $request = $this->getApplication()
            ->getRequestDto($applicationInstall, CurlManager::METHOD_POST, $url, Json::encode($body))
            ->setDebugInfo($dto);

        try {
            $response = $this->sender->send($request);

            if (!$this->evaluateStatusCode($response->getStatusCode(), $dto)) {
                return $dto;
            }
        } catch (CurlException|PipesFrameworkException $e) {
            throw new ConnectorException($e->getMessage(), $e->getCode(), $e);
        }

        return $dto->setData($response->getBody());
    }
}
```

Na závěr nezapomeneme třídu konektoru zaregistrovat jako službu.

``` YAML 13

# ./config/connector/connector.yaml
service:
    hbpf.connector.send-grid.send-email:
        class: Pipes\PhpSdk\Connector\SendGrid\SendGridSendEmailConnector
        arguments:
            - '@doctrine_mongodb.odm.default_document_manager'
            - '@hbpf.transport.curl_manager'
        calls:
            - ['setApplication', ['@hbpf.application.send-grid']]
```

Pro detailní popis třídy konektoru doporučujeme prostudovat předchozí návod [Jak vytvořit konektor pro volání REST API](/docs/cs/tutorials/basic-connector).

## Použití v procesu
Přihlásíme se do uživatelského prostředí PIPES. Pokud jsme postupovali správně, v Appstore PIPES nyní uvidíme naši novou aplikaci. Tu je nutné nejprve nainstalovat.

![](/uploads/scr_sendmail/1_select_app.png "Appstore s novou aplikací")

Po kliknutí na tlačítko "Instalovat" se zobrazí náš formulář pro vložení autorizačního tokenu.

![](/uploads/scr_sendmail/2_sendmail_app_install.png "Appstore - detail aplikace")
![](/uploads/scr_sendmail/2_sendmail_app_install2.png "Autorizační formulář aplikace")

Po uložení formuláře máme aplikaci připravenou k použití. Nyní vytvoříme nový proces. Na canvas přetáhneme start event a za něj připojíme connector action. 

![](/uploads/scr_sendmail/3_sendmail_topology_bpmn_chart.png "Sestavení topologie")


Vybereme připravený script konektoru.

![](/uploads/scr_sendmail/4_sendmail_topology_bpmn_name.png "Script konektoru")

Tím je proces hotový. Můžeme ho tedy publikovat a vyzkoušet, zda naše nová aplikace opravdu odešle e-mail pomocí služby Sandgrid. Přepneme se do záložky s metrikami procesu a v bloku start eventu stiskneme tlačítko "Spustit". Do otevřeného pole zadáme následující data ve formátu JSON. Pro otestování zadáme vlastní e-mailovou adresu.

![](/uploads/scr_sendmail/5_run_node_script.png "Spuštění procesu")

A to je vše. Výsledek zkontrolujeme v e-mailové schránce.



Nyní už víte, jak vytvořit vlastní aplikaci a rozšířit tak Appstore PIPES o vlastní řešení. V příštím manuálu si ukážeme, [jak vytvářet autoriaci s OAuth2](/docs/cs/tutorials/oauth2-application). 