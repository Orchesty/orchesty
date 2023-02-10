import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# Applications and connectors

## Connector

A connector is a [worker](../documentation/workers) action that communicates with a remote service using the HTTP protocol. The connector also evaluates the response of the request and has various options for evaluating it, in addition to a successful call, such as [retrying a call when the service is unavailable](../documentation/results-evaluation) or [trashing a message](../documentation/trash) based on specific return codes.

## Application

In order to communicate, a connector must typically authorize its calls. Basic authorization can be included directly by the connector. Since we often call multiple endpoints of the same API, we must also use multiple connectors with the same authorization. Therefore, it is preferable to use an application that bundles everything we need to use across multiple connectors. This will greatly simplify the writing of the connectors themselves. Some applications also give us additional features, such as OAuth authorization or the ability to register webhooks.

### Basic application
Enables basic authentication. The application will provide a user-defined form to set the required settings via the user interface.

![Basic application form](/img/documentation/basic-application.svg "Basic application form")

:::note Useful links
- [Basic application tutorial](../tutorials/basic-application)
:::

### OAuth application
It allows authentication using OAuth 2. In addition to the form for entering credentials, the application will provide an **Authorize** button to allow user authorization. In this case, Orchesty will also take care of refreshing the authorization token according to its expiration setting.

![OAuth settings](/img/documentation/oauth-settings.svg "OAuth settings")

:::note Useful links
- [OAuth application tutorial](../tutorials/oauth2-application)
:::

### Application with webhooks
It can be a basic or OAuth application. To use webhooks, the application implements a webhook interface and adds a definition of the individual webhooks it should allow to register. We can then find the necessary settings in the application detail.

![Webhooks settings](/img/documentation/webhook-settings.svg "Webhook settings")

:::note Useful links
- [How to use webhooks in topology](../documentation/editor)
- [Webhooks tutorial](../tutorials/basic-application)
:::
