# Orchesty Community Edition

> The source-available core of the [Orchesty Platform](https://orchesty.io/platform) — a self-hostable integration engine for building connectors, mappers, and event-driven topologies on your own infrastructure.

[![License: Elastic License 2.0](https://img.shields.io/badge/License-Elastic%20License%202.0-005571.svg)](https://www.elastic.co/licensing/elastic-license)
[![Docs](https://img.shields.io/badge/Docs-orchesty.io%2Fdocs-1f6feb.svg)](https://orchesty.io/docs/3.0/getting-started/full-stack-setup/overview)
[![Components](https://img.shields.io/badge/Components-Catalogue-2ea44f.svg)](https://orchesty.io/components)

---

## What is Orchesty?

Orchesty is an integration platform for building, running, and observing **topologies** — directed graphs of small workers (connectors, batches, mappers, filters) that pass JSON between services. You write the worker logic in TypeScript or PHP using the SDK; the engine takes care of queueing, retries, scheduling, scaling, persistence, and observability.

Typical use cases:

- Syncing data between SaaS APIs (CRM ↔ ERP, billing ↔ accounting, support ↔ ops)
- ETL and batch ingestion pipelines
- Event-driven workflows triggered by webhooks or cron
- Embedded integration marketplace for your SaaS product

Community Edition is the **same engine** that powers the managed [Orchesty Cloud](https://orchesty.io/platform) and the [Enterprise Edition](https://orchesty.io/enterprise-edition) — free to self-host, free for production use, with the only caveat being the Elastic License terms (see [License](#license)).

## Why Orchesty

- **Code-first, not low-code** — workers are real TypeScript / PHP, version-controlled in your repo. No drag-and-drop UI you can't diff.
- **No vendor lock-in** — runs on any Docker host or Kubernetes cluster. Your data stays in your DB, your secrets stay in your vault.
- **Production-ready out of the box** — built-in retries with backoff, dead-letter handling, per-node concurrency limits, scheduled cron triggers, webhook subscriptions, distributed tracing.
- **Visual topology editor** — design topologies in the bundled admin UI, run them headless.
- **Same core, three editions** — start free on Community Edition; upgrade to Enterprise Edition (SLA + support) or Orchesty Cloud (managed) when you need it, without rewriting your workers.

## Editions at a glance

|                                  | Community Edition       | [Enterprise Edition](https://orchesty.io/enterprise-edition) | [Orchesty Cloud](https://orchesty.io/platform) |
| -------------------------------- | ----------------------- | ------------------------------------------------------------ | ---------------------------------------------- |
| Deployment                       | Self-host (Docker / k8s) | Self-host (Docker / k8s)                                    | Fully managed                                  |
| License                          | Elastic License 2.0     | Elastic License 2.0 + commercial agreement                   | Hosted service                                 |
| Price                            | Free                    | Paid                                                         | Free trial + subscription                      |
| SLA & support                    | Community only          | Yes — priority support, on-call                              | Yes — included with plan                       |
| Updates                          | DIY                     | Managed releases                                             | Continuous                                     |
| Best for                         | Prototypes, OSS, small teams | Regulated industries, data sovereignty                  | Teams who don't want to operate infra          |

## Quick start

The fastest way is the **[Orchesty Skeleton](https://github.com/Orchesty/orchesty-skeleton)** — a turnkey Docker Compose setup that bootstraps the core services plus a Node.js worker. Requires Docker and `make`; runs on macOS, Linux, or Windows (WSL2).

```bash
git clone https://github.com/Orchesty/orchesty-skeleton.git my-orchesty
cd my-orchesty
make init-dev
```

Then open [http://127.0.0.1](http://127.0.0.1) for the admin UI.

For a complete walkthrough (including the AI-assisted bootstrap that wires it into Cursor / Claude Code / Copilot for you), see the [full installation guide](https://orchesty.io/docs/3.0/getting-started/full-stack-setup/install).

### Build your first connector

Once the platform is running, the next step is writing a worker. The [Node.js SDK](https://github.com/Orchesty/orchesty-nodejs-sdk) provides typed primitives for **Applications**, **Connectors**, **Batches**, and **CustomNodes**:

```bash
# Inside the skeleton, there's a sample worker under ./node-sdk/
# Or scaffold a fresh worker:
git clone https://github.com/Orchesty/orchesty-skeleton.git my-worker
cd my-worker/node-sdk
pnpm install
pnpm dev
```

See [Building a connector](https://orchesty.io/docs/3.0/development/project-setup/skeleton-and-structure) for the full guide.

## Architecture

Orchesty splits responsibilities across small, independently-scalable services. The Community Edition ships everything you need for a complete platform; you don't pick and choose.

```
┌─────────────────────────────────────────────────────────────┐
│  Admin UI (Vue)         Topology editor, runs, metrics      │
└────────────────────────┬────────────────────────────────────┘
                         │
┌────────────────────────┴────────────────────────────────────┐
│  pf-bundles (PHP)       Control plane: API, auth, configs   │
└────────────────────────┬────────────────────────────────────┘
                         │
        ┌────────────────┼────────────────┬─────────────┐
        ▼                ▼                ▼             ▼
┌─────────────┐  ┌────────────────┐  ┌─────────┐  ┌───────────┐
│  bridge     │  │ starting-point │  │  cron   │  │  worker-  │
│  (Go)       │  │  (Go)          │  │  (Go)   │  │  api      │
│             │  │ Webhook entry  │  │ Sched.  │  │  (Node)   │
└──────┬──────┘  └────────────────┘  └─────────┘  └─────┬─────┘
       │                                                 │
       ▼                                                 ▼
┌──────────────────────────────────────────────────────────────┐
│  Your worker(s) — Node.js or PHP SDK, your business logic   │
└──────────────────────────────────────────────────────────────┘

Plus: limiter (rate limits) · counter (concurrency) · detector (state) · topology-generator (compile topologies to runnable graphs)
```

| Service              | Stack    | Responsibility                                            |
| -------------------- | -------- | --------------------------------------------------------- |
| `bridge`             | Go       | Routes messages between workers, handles retries          |
| `starting-point`     | Go       | HTTP/webhook entry point that injects messages into topologies |
| `cron`               | Go       | Scheduler for cron-triggered topologies                   |
| `topology-generator` | Go       | Compiles topology JSON into runnable definitions          |
| `limiter`            | Go       | Per-topology / per-node rate limiting                     |
| `counter`            | Go       | Tracks in-flight work for concurrency caps                |
| `detector`           | Go       | Persists topology state and process metadata              |
| `worker-api`         | Node     | Generic worker bridge between Node SDK and the bus        |
| `pf-bundles`         | PHP      | Control plane — REST API, auth, config storage            |
| `app-ui`             | Vue      | Admin UI for topologies, runs, metrics, configuration     |

The full architecture, including data flow and persistence layer (MongoDB + RabbitMQ), is documented at [orchesty.io/docs](https://orchesty.io/docs/3.0/getting-started/full-stack-setup/overview).

## Documentation

| Topic                                       | Link |
| ------------------------------------------- | ---- |
| Getting started (install & first topology)  | [docs/3.0/getting-started](https://orchesty.io/docs/3.0/getting-started/full-stack-setup/overview) |
| Concepts (topologies, nodes, runs)          | [learn/basics](https://orchesty.io/learn/basics) |
| Building a worker (Node.js SDK)             | [docs/3.0/development](https://orchesty.io/docs/3.0/development/project-setup/skeleton-and-structure) |
| Connector catalogue                         | [orchesty.io/components](https://orchesty.io/components) |
| Tutorials & guides                          | [orchesty.io/learn](https://orchesty.io/learn) |
| API reference                               | [docs/3.0/api](https://orchesty.io/docs) |

## Connector catalogue

Need to talk to HubSpot, Pipedrive, Shopify, Stripe, or 50+ other services? The [Orchesty Components Catalogue](https://orchesty.io/components) is a free, public registry of **open-source connectors** maintained by the Orchesty team and the community. Each connector is a tiny npm / Composer package you can install into your worker and register with one line.

The connectors themselves are MIT/Apache-licensed — open source, separate from the Elastic-licensed core. Contributions welcome; see each connector's repo under [github.com/Orchesty](https://github.com/Orchesty).

## Companion repositories

| Repo                                                                            | Purpose                                                       |
| ------------------------------------------------------------------------------- | ------------------------------------------------------------- |
| [`orchesty-skeleton`](https://github.com/Orchesty/orchesty-skeleton)            | Docker Compose bootstrap for local dev / self-hosted install  |
| [`orchesty-nodejs-sdk`](https://github.com/Orchesty/orchesty-nodejs-sdk)        | TypeScript SDK for building workers                           |
| [`orchesty-nodejs-connectors`](https://github.com/Orchesty/orchesty-nodejs-connectors) | Reference / community connectors for the Node SDK      |
| [`orchesty-php-sdk`](https://github.com/Orchesty/orchesty-php-sdk)              | PHP SDK for building workers                                  |
| [`orchesty-skeleton-php`](https://github.com/Orchesty/orchesty-skeleton-php)    | Skeleton using the PHP SDK instead of Node                    |

Full list at [github.com/Orchesty](https://github.com/Orchesty).

## Community & support

- **Documentation & tutorials** — [orchesty.io/learn](https://orchesty.io/learn)
- **Issues & feature requests** — open an issue on the relevant component's GitHub repo
- **Community channel** — [orchesty.io/community](https://orchesty.io/community)
- **Commercial support / SLA** — available with [Enterprise Edition](https://orchesty.io/enterprise-edition) or [Orchesty Cloud](https://orchesty.io/platform)

> Community Edition is **community-supported only**. There is no guaranteed response time. For production deployments that need a contractual SLA, on-call support, or assistance with deployment, look at Enterprise Edition or Cloud.

## Contributing

Contributions are welcome — bug reports, fixes, docs improvements, and new connectors.

1. Pick a component repo (this monorepo, the SDK, or a connector).
2. Open an issue first for non-trivial changes, so we can align on direction.
3. Fork, branch, commit (conventional commits preferred), and open a PR.
4. By contributing you agree your contributions are licensed under the same terms as the repository.

For setting up a local dev environment for the engine itself (Go services + PHP bundles + Vue UI), see [`docs/3.0/development`](https://orchesty.io/docs/3.0/development/project-setup/skeleton-and-structure).

## Security

If you discover a security vulnerability, **please do not open a public issue**. Email security disclosures to the address listed at [orchesty.io/security](https://orchesty.io/security) (or, if missing, to `security@orchesty.io`). We aim to acknowledge reports within 2 business days.

## License

Orchesty Community Edition is released under the [**Elastic License 2.0 (ELv2)**](https://www.elastic.co/licensing/elastic-license).

In plain English, ELv2 lets you:

- **Use** the software for any purpose, including commercial, in your own product, on your own infrastructure.
- **Modify** the source, fork it, distribute your modifications.
- **Integrate** it into your own commercial offering.

The two main restrictions are:

- You may **not** offer the software (or a derivative) **as a hosted or managed service** to third parties (the "no SaaS-of-Orchesty" clause).
- You may **not** circumvent the license-key or feature-gating mechanisms.

Full license text in [`LICENSE`](LICENSE) and in each component's own `LICENSE` file. If your use case might conflict with the SaaS clause, talk to us about a [commercial license](https://orchesty.io/enterprise-edition).

> **Note on terminology.** Orchesty's engine is **source-available**, not "open source" in the OSI sense, because of the ELv2 SaaS clause. The connectors in the [components catalogue](https://orchesty.io/components) are separately licensed under permissive OSS licenses (MIT / Apache 2.0) and are true open source.

---

Made with care by Orchesty Solutions and the Orchesty community.
