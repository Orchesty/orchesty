# Release Notes

## 2.1.10

### Changes

- Frontend: Added the option to change service port
- Backend: Added the option to change service port
- StartingPoint: Added the option to change service port

- Helm: added Valkey as a optional dependency

## 2.1.9

### Changes

- Worker: Added the option to add ports for sidecar containers

## 2.1.8

### Changes

- Worker: Added the option to add sidecar containers
- Worker: Added the option to add livenessProbe and readinessProbe

## 2.1.7

### Changes

- Helm: Added the option to set resource quota for namespace
- Helm: Added the option to set timezone
- Worker: Added the option to set resources for each worker separately
- Worker: Added the option to set replicas for each worker separately

## 2.1.6

### Changes

- Helm: updated resource limit for Backend

## 2.1.5

### Changes

- Helm: added global orchestyVersion variable

## 2.1.4

### Changes

- MarketplaceUI: added support for Custom branding
- TopologyAPI: added support for extra specs

## 2.1.3

### Changes

- Backend, Frontend, StartingPoint, MarketplaceUI: added support for Service.NodePort

## 2.1.2

### Changes

- Backend: removed prefix from TOPOLOGY_API_DSN value

## 2.1.1

### Changes

- Worker: removed prefix from service name

## 2.1.0

### Changes

- Worker: removed deprecated config for set up a worker

## 2.0.3

### Changes

- Worker-api: added metricsDb env
- Backend: added limiter env
- Limiter - service: removed deprecated port (3333)
- Added support for multiple workers

## 2.0.2

### Changes

- FluentD: added correct port number for TCP
- Updated default resources config

## 2.0.1

### Changes

- Chart bug fixes

## 2.0.0

### Breaking changes:

- Changed default Orchesty version to new major version 2.0.0

### Changes

- Added `worker-api` service and deployment
- Added configuration option `global.worker_api_url` 
