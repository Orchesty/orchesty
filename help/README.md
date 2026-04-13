# Help System

In-app contextual help for the Orchesty enterprise instance. Help content is authored as Markdown files, built into JSON, and served by the PHP backend to the frontend's Help drawer.

## Structure

```
help/
├── scripts/
│   └── build-help.mjs          # Build script (Markdown → JSON)
├── .dist/                       # Pre-built output (committed)
│   ├── manifest.json            # Page list with metadata
│   ├── search-index.json        # MiniSearch full-text index
│   └── pages/**/*.json          # Individual page content
├── control-center/              # Section: Control Center
│   ├── overview.md
│   ├── applications.md
│   ├── connectors.md
│   ├── topologies.md
│   ├── processes.md
│   └── limiter.md
├── topologies/                  # Section: Topologies
│   ├── overview.md
│   └── designer.md
├── applications/overview.md
├── failed-messages/overview.md
├── logs/overview.md
├── scheduled-tasks/overview.md
├── settings/overview.md
└── users/overview.md
```

## Writing Help Pages

Each `.md` file requires YAML frontmatter:

```yaml
---
title: Page Title          # Displayed in navigation and breadcrumbs
helpId: section/page-name  # Unique ID, matches the file path (without .md)
order: 1                   # Sort order within the section
---
```

The body is standard Markdown. It gets rendered in the frontend via `marked`.

Directory structure defines sections — files in the same folder are grouped together in the navigation tree.

## Building

After editing Markdown files, rebuild the `.dist/` output:

```bash
cd help
npm install --no-save gray-matter minisearch
node scripts/build-help.mjs . .dist
```

This generates:
- `manifest.json` — page list with slug, title, helpId, order, parent
- `search-index.json` — pre-computed MiniSearch index (fuzzy + prefix search)
- `pages/**/*.json` — individual pages with title, helpId, and raw Markdown content

The `.dist/` folder is committed to the repo so the build step is only needed when content changes.

## How It Works

### Backend (PHP)

`HelpController` serves the pre-built JSON files via three endpoints:

| Endpoint | Returns |
|---|---|
| `GET /api/help/manifest` | Page list with metadata |
| `GET /api/help/search-index` | MiniSearch serialized index |
| `GET /api/help/page/{slug}` | Single page content |

The controller reads from a configurable directory (`$helpDir`). Default paths:

- **Development:** `%kernel.project_dir%/../Help/.dist` (relative to `pf-bundles/`)
- **Production Docker:** `/srv/app/help`

### Frontend (Vue)

- `HelpDrawer.vue` — side panel with navigation tree, search, and Markdown rendering
- `useHelp.ts` — composable providing `open(helpId?)`, `close()`, `toggle()` via Vue injection
- `helpService.ts` — API calls to fetch manifest, pages, and search index
- Routes define `meta.helpId` to auto-open the relevant page:

```typescript
{ path: 'topologies', meta: { helpId: 'topologies/overview' } }
```

### Route → Help Mapping

| Route | helpId |
|---|---|
| `/dashboard` | `control-center/overview` |
| `/topologies` | `topologies/overview` |
| `/applications` | `applications/overview` |
| `/logs` | `logs/overview` |
| `/trash` | `failed-messages/overview` |
| `/scheduled-tasks` | `scheduled-tasks/overview` |
| `/settings` | `settings/overview` |
| `/users` | `users/overview` |

## Deployment

### Development (docker-compose)

The `help/.dist/` directory is available via volume mount of the entire `pipes/` repository. No extra configuration needed.

### Production (Docker image)

Copy the pre-built files into the image:

```dockerfile
COPY help/.dist /srv/app/help
```

The `HelpController` constructor defaults to `/srv/app/help`, so no additional DI configuration is required.

### Verification

```bash
curl https://<instance-url>/api/help/manifest
```

Should return a JSON array with all help pages. A 404 means the `.dist/` files are not on the expected path.
