import Image from '/src/components/ThemedImg';

# Advanced methods

## Repeated calls on communication error
In case the communication with the integrated service is not succesfull, Orchesty allows to set repeated calling.
This repeating is provided by the service called **Repeater**. It is possible to set the Repeater individually
for every connector. Its setting should be based on return codes of the specific service. We can set the return values
for which the Repeater should be used, which frequency try to call continuously and how many times to repeat the calling. 

:::note More about
- [Results evaluation](../documentation/results-evaluation)
- [How to use Repeater](../tutorials/basic-connector)
:::

## Messages order & parallel processing
Orchesty is able to process messages in parallel. It doesn't guarantee the right order of the messages.
Parallel processing depends on **prefetch** setting of every process action. This value shows the amount of messages
from the queue which are fetched by **Bridge** at the moment.

Bridge is a controlling service.
It knows the process topology and provides calling of every action and the trafic process logic.
Bridge will not process messages in parallel if the prefetch value is 1.
Therefore, messages will be in the right order. 

:::caution
Prefetch value **1** lowers the speed of queue processing. We need to decide what is more important - order of messages or the performance. 
:::

We can set prefetch in [process editor](../admin/process-editor) in the settings of every action.

<Image path="/img/orchestration/prefetch.png" lightOnly />

## Distant service's communication limits setting

Services like SaaS limit permitted quantity of requests for its API. These limits have rules.
Some services restrict access due to the user account. Various user accounts have different limits.
Some restrictions are based upon IP adress and some of them are even combined.

Orchesty provides service called **Limiter** to deal with those limits. It restricts process actions to prevent
exceeding of allowed limits. To work efficiently, Limiter monitors limits of all connectors and topologies.

<Image path="/img/architecture/limiter.png"/>

Limiter has a broad variety of settings. Messages contained in the common limit counting depends on the definition
of the key which we transmit to Limiter. It can be the identificator of the integrated service which restricts
calling of this specific service. We must add the user ID into the key in case of mutual integration of two SaaS services.
Limiter then regulates the communication of every user of the service separately even though
they are parts of the same integration processes.

Limiter also deals with situations when user incorrectly sets his limit and the integrated service rejects
the request with information about overruned limit. Limiter then sets the counter to the reached limit
and restrict further communication according to the set rules. In certain cases, the service returns also
details about allowed limits. Programmer himself decides whether he sets the rules automatically by received informations. 

:::note More about
- [Limiter service documentation](../documentation/limiter)
- [How to set communication limits](../documentation/limiter)
:::
