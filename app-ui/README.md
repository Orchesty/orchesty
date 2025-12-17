## Orchesty UI (Frontend)

The Orchesty UI allows the user to manage topologies, applications. Monitor individual processes and metrics of the entire Orchesty ecosystem.

#### HOW TO RUN

```bash
# Install dependencies
pnpm install
# Compiles and hot-reloads for development
pnpm run serve || pnpm start
# Compiles and minifies for production
pnpm run build
# Run tests
pnpm run test
# Lint
pnpm run lint
```

#### Directory structure (src)

- api
  - module (dirs by main route)
- assets
  - stylus (less, scss)
  - img
- components
  - commons (general component for app - form input, form input with validation, flash message, etc.)
  - layout
  - module (dirs by modules)
- config (config files for app - urls, icon, timeout)
- enums
- filters (format function for templates)
- localization
- router
  - routes
    - module (dirs by main routes)
- services (fetch, graphQL, stream, etc.)
- store
  - grid (universal store for grid)
  - modules
    - module (dirs by modules)
- views
  - module (dirs by modules)

#### Design and components

- https://vuetifyjs.com/en/

#### Static rendering

- https://www.npmjs.com/package/prerender-spa-plugin
- https://www.npmjs.com/package/vue-meta
