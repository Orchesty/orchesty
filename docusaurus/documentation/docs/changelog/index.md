# Changelog

## 2.0.1
- minor bug and security fixes

## 2.0.0

### Environment variable changes
- Limiter
  - **LOGSTASH_HOST** & **LOGSTASH_PORT** replaced by **UDP_LOGGER_URL**
- StartingPoint
  - **RABBIT_COUNTER_QUEUE_DURABLE** & **RABBIT_QUEUE_DURABLE** replaced by **RABBITMQ_DSN** 
  - removed **ORCHESTY_API_KEY**
- PhpSdk
  - removed **BACKEND_DSN**
  - removed **STARTING_POINT_DSN**
  - removed **RABBITMQ_DSN**
- NodeSdk
  - added **WORKER_API_HOST**
  - removed **UDP_LOGGER_DSN**
  - removed **METRICS_DSN**
  - removed **MONGODB_DSN**
- MultiCounter
  - **RABBIT_HOST** replaced by **RABBITMQ_DSN**
- Backend
  - removed **ORCHESTY_API_KEY**
- CronApi
  - removed **ORCHESTY_API_KEY**

### Sdk
- No longer has required database dependency -> db access has been replaced by WorkerApi  
(MongoClient is still existing but no longer created & registered into container by default)  
- Changed CurlSender result code  

Method send(..., codes) accepts `codes` with different setting, if not specified, given range is considered
success.  
For more concrete settings, you can provide an object containing **success**, **repeat** (will try again later),
**stopAndFailed** (sends message into trash as failed) ranges.

Example ranges:
```
  - '200-300' - left inclusive
  - [200, 201] - specific codes
  - '>=400' - allowed prefixes '>=', '<=', '>', '<'
  - 200 - single number
  - [200, '>=400'] - mix of different types in an array
```

Sdk provides a set of pre-defined settings:  
**repeatOnErrorRanges** (repeats on any error )  
**continueOnNotFoundRanges** (consider 404 as success)  
**continueOnErrorRanges** (consider any 4xx as success)  
and a **defaultRanges** (repeats only 408 & 5xx):  
```
{ 
    success: '<300',
    stopAndFail: ['300-408', '409-500'],
    repeat: [408, '>=500'],
}
```

### UI
- Communication between services (Sdk, Limiter, StartingPoint, WorkerApi, Bridge, ...)
is now protected by ApiToken.  
Token can be managed via UI.
- Trash message can be sent to newer topology (must be the same topology of a newer version)
- Topology can be started even when disabled (works only from UI)
- StartingPoints of topology have new settings:
  - enable/disable
  - visible url for REST point
  - timer for Cron point


### Limiter
Complete overwrite of Limiter service for optimization.  


#### Removed services
- Repeater - merged into Limiter service

## 1.0.6

### Sdk
- Removed ILimitedApplication  
[Replaced by Application form](../documentation/limiter) within UI to set limits for nodes belonging to given application.


### Connectors
- Removed Universal Limiter nodes

### BC changes SDK/Connectors
- Updated Application forms

### Logstash
Replaced by Fluentd for optimization.   
Big drop in memory usage from 1 GB to roughly under 100 MB on a base image.

#### Removed services
- Notification sender (Replaced by Service topology)
- Status service (Replaced by Service topology)
