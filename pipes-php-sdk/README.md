# PHP Orchesty SDK

## How to use ?
- Install package `orchesty/php-sdk`
- Register bundles from PHP SDK into your Symfony Application:
```
# config/Bundles.php
 
  ...
  HbPFApplicationBundle::class     => ['all' => TRUE],
  HbPFCommonsBundle::class         => ['all' => TRUE],
  HbPFConnectorBundle::class       => ['all' => TRUE],
  HbPFConnectorsBundle::class      => ['all' => TRUE],
  HbPFCustomNodeBundle::class      => ['all' => TRUE],
  ...
```
- Register routes from PHP SDK into your Symfony Application:
```
# config/routes/routing.yaml

...
hb_pf_applications:
    resource: "@HbPFApplicationBundle/Controller"
    type: annotation

hb_pf_connector:
    resource: "@HbPFConnectorBundle/Controller"
    type: annotation

hb_pf_custom_node:
    resource: "@HbPFCustomNodeBundle/Controller"
    type: annotation

hb_pf_batch:
    resource: "@HbPFBatchBundle/Controller"
    type: annotation
```
- Add parameters where will be your nodes and application registered:
```
# config/services.yaml
parameters:
    node_services_dirs:
        - '%kernel.project_dir%/config'
    applications:
        - '%kernel.project_dir%/config'
```
- Add required environment variables:
```
Example values:
BACKEND_DSN: 'http://127.0.0.10:8080'
STARTING_POINT_DSN: 'http://starting-point:8080'
WORKER_API_HOST: 'http://worker-api:8000'
ORCHESTY_API_KEY: 'ThisIsNotSoSecretApiKey'
```

## How to develop
1. Run `make init` for start dev environment
2. Tests can be run by `make test` or `make fasttest`