# Extensions

The concept of Orchesty is to make everything straight. It is quite an open tool and most
importantly a tool for developers. It allows building custom extensions of more levels.

- Build new connectors for services in **Appstore**.
  Custom connectors can be useful if we need to call endpoint which is not included natively.
- Extend **Appstore** with new applications.
- Build new elements to integrate into our processes.

The Orchesty basis allows building custom tools.
Extensions are reusable and can be built in every programming language for which is prepared
[SDK](../sdk/keep). The principle is very simple.
You need to build a service with custom extensions with [SDK](../sdk/keep) and register it into [Admin](../admin/admin.md) as [Service for direct integration](integration).

:::info More about
- [Custom SDK service for direct integration](../tutorials/SDK-settings)
- [Application with basic authentication](../tutorials/basic-application)
- [Application with 0Auth 1 authorization](../tutorials/oauth1-application)
- [Application with 0Auth 2 authorization](../tutorials/oauth2-application)
- [Integration of service with webhooks](../tutorials/webhooks)
:::
