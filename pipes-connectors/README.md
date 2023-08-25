# PHP Orchesty SDK

## How to use ?
- Install package `orchesty/php-sdk` and follow the SDK readme
- Install package `orchesty/php-connectors`
- Add nodes and application from repository:
```
# config/services.yaml
parameters:
    node_services_dirs:
        - '%kernel.project_dir%/vendor/orchesty/php-connectors/src/Resources/config/'
    applications:
        - '%kernel.project_dir%/vendor/orchesty/php-connectors/src/Resources/config/'
```

## How to develop
1. Run `make init` for start dev environment
2. Tests can be run by `make test` or `make fasttest`