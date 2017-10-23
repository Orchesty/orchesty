CmStream
===============================

### Local dev + test
* make init
* make test

### Config
```
guzzle_client_factory:
    class: CmStream\GuzzleClientFactory
    
subscriber:
    class: CmStream\Subcriber("%guzzle_client_factory%")
```