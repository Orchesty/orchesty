# Pipedrive Integration

Orchesty integration for [Pipedrive](https://www.pipedrive.com/) — a sales pipeline and CRM platform.

This integration reuses the official `@orchesty/connector-pipedrive` npm package for the application class and the generic Add Lead / Add Note connectors. We add four sales-form-specific connectors that share a common `ISalesFormContext` payload, so Person → Lead → Note can run sequentially in a single topology and pass IDs forward.

## Components

| Component | Type | Node name | Source | Description |
|---|---|---|---|---|
| **PipedriveApplication** | Application | `pipedrive` | npm package | API token + subdomain authentication; appends `?api_token=...` to every request |
| **PipedriveAddSalesOrganizationConnector** | Connector | `pipedrive-add-sales-organization` | local | `POST /organizations` with `{ name: ctx.company }`, writes `orgId` back to context |
| **PipedriveAddSalesPersonConnector** | Connector | `pipedrive-add-sales-person` | local | `POST /persons` with name, email, phone, job title, `org_id`; writes `personId` back to context |
| **PipedriveAddSalesLeadConnector** | Connector | `pipedrive-add-sales-lead` | derived from npm `PipedriveAddLeadConnector` | `POST /leads` with title (`First Last (Company)`), `person_id`, `organization_id`, `origin_id`; writes `leadId` and a deep-link `leadUrl` (`https://{subdomain}.pipedrive.com/leads/inbox/{leadId}`) back to context |
| **PipedriveAddSalesNoteConnector** | Connector | `pipedrive-add-sales-note` | derived from npm `PipedriveAddNoteConnector` | `POST /notes` with the entire sales form payload formatted as HTML, linked to the lead via `lead_id`; writes `noteId` back to context |

## Application Settings

### Authorization — API token + subdomain

1. Log in to your Pipedrive account.
2. Open **Settings** → **Personal preferences** → **API**.
3. Copy the **personal API token**.
4. The **subdomain** is the prefix of your Pipedrive URL: e.g. `acme` from `https://acme.pipedrive.com`.

The application builds every request URL as `https://{subdomain}.pipedrive.com/api/v1{path}` and adds `?api_token={token}`.

No worker-specific application settings beyond authorization are needed — all sales-form recipient/template configuration lives on the Ecomail application.

## Sales Form Connectors

All four sales connectors operate on the shared `ISalesFormContext` (`src/Sales/types.ts`) and follow the same data-flow contract:

1. Read the context from `dto.getJsonData()`.
2. Validate the fields actually needed by this step via `checkParams()`.
3. Reshape into the API-expected payload and call the underlying API (directly via `app.getRequestDto()` for Org/Person, or via `super.processAction()` for Lead/Note).
4. Merge the new ID back into the context with `setNewJsonData({ ...ctx, <newId> })` so downstream nodes still see the original form payload plus everything created so far.

### Sequential context

```
Start
  → AddOrganization  ⇒ ctx.orgId
  → AddPerson         ⇒ ctx.personId   (uses ctx.orgId)
  → AddLead           ⇒ ctx.leadId, ctx.leadUrl  (uses ctx.personId, ctx.orgId; reads `subdomain` from app settings)
  → AddNote           ⇒ ctx.noteId     (uses ctx.leadId)
```

### Note content

`PipedriveAddSalesNoteConnector` builds an HTML note containing the full form payload (form metadata, contact details, message, technical meta) so the sales rep sees everything at a glance in the Pipedrive lead detail. The HTML is escaped to prevent injection from user-supplied fields.

## Topologies

| File | Trigger | Description |
|---|---|---|
| `src/topologies/sales-form.tplg.json` | Start | Sales form → Organization → Person → Lead → Note → Ecomail business notification → Ecomail customer confirmation |

## API Reference

- Base URL (per install): `https://{subdomain}.pipedrive.com/api/v1`
- Auth: `?api_token={token}` query parameter
- Docs: [https://developers.pipedrive.com/docs/api/v1](https://developers.pipedrive.com/docs/api/v1)
- Organizations: [https://developers.pipedrive.com/docs/api/v1/Organizations](https://developers.pipedrive.com/docs/api/v1/Organizations)
- Persons: [https://developers.pipedrive.com/docs/api/v1/Persons](https://developers.pipedrive.com/docs/api/v1/Persons)
- Leads: [https://developers.pipedrive.com/docs/api/v1/Leads](https://developers.pipedrive.com/docs/api/v1/Leads)
- Notes: [https://developers.pipedrive.com/docs/api/v1/Notes](https://developers.pipedrive.com/docs/api/v1/Notes)

## Notes on the npm package

The official `@orchesty/connector-pipedrive` package (v2.0.0) declares a peer dependency on `@orchesty/nodejs-sdk: ^5.0.8`. This worker pins `@orchesty/nodejs-sdk` to a local checkout (`file:../orchesty-nodejs-sdk/dist`) which is currently 5.0.5. To make all packages share the same SDK instance (so `instanceof AConnector` works and the container accepts derived nodes), `package.json` contains an `overrides` block forcing the local SDK everywhere:

```json
"overrides": {
  "@orchesty/nodejs-sdk": "file:../orchesty-nodejs-sdk/dist"
}
```
