# Hanaboso/PipesFramework

Hanaboso Pipes Framework.

## Installation

1. Install via composer 

```bash
composer require hanaboso/pipes-framework
```

2. Register bundles in AppKernel

```php
public function registerBundles()
{
    $bundles = [
        ...
        new Hanaboso\PipesFramework\Commons\HbPFCommonsBundle\HbPFCommonsBundle(),
        ...
    ];
}
```

3. Add resources to config

```yml
## routing.yml
hbpf:
  resource: "@HbPFCommonsBundle/Controller/"
  type:     annotation
    
## config.yml
imports:
  - { resource: "@HbPFCommonsBundle/Resources/config/fos_rest.yml" }
  - { resource: "@HbPFCommonsBundle/Resources/config/sensio_framework_extra.yml" }
```

## Node implementation

1. Implement `NodeInterface`.

2. Register node as a service under the `hbpf.nodes.<nodeId>` key.

3. Make POST request to `/api/nodes/{nodeId}/process`.

## Error offsets
ServiceStorageException - 0x0100