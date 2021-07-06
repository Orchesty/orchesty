#Breaking changes:

### Repeater
- interval změněn z millisec na sec
- current hops je počítán v bridge

### Batch
- kompletní přepsání - očekává pole dat a nový status code 1_010

### UserTask
- kompletní přepsání - userTask už není součástí sdk

### Odebrané hlavičky
- nodeName
- topologyName


# Topology generator
- fixnout id / node_id: odebrat písmena jména
- **splitter.amqprpc_limited** změnit na **worker.batch**
- **php-sdk:80** vypadá hardcoded
- batch sjednotit settings s http
- sjednotit workery -> worker.http... vyhodit http_limited etc... limiter když už, tak má být v settings