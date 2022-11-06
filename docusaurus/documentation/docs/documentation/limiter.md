import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# Limiter

The Limiter controls the frequency of requests to a specific API so that its allowed limits are not exceeded. For proper behavior, the limiter operates over all topologies and nodes simultaneously. Thus, it calculates the limit for a given application from the calls of all running processes.

## Settings

Setting up the Limiter is very simple. We can do it in the details of each application. 

![Limiter settings](/img/tutorial/limiter-form.svg "Limiter settings")


