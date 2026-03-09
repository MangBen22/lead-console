# Phase 127: WebOps Incident Registry

## Summary
Added durable WebOps incident tracking so monitor failures and recoveries produce a stable operational state instead of only transient retry items.

## Delivered
- Added API actions:
  - `webops.incidents.list`
  - `webops.incidents.resolve`
- Added storage key:
  - `webops_incidents`
- Monitor test/run/retry flows now record incident state changes.
- WebOps summary now reports:
  - `active_incidents`
  - `retry_backlog`
- Added dashboard controls:
  - `Refresh WebOps Incidents`
  - `webopsIncidents`
  - `Resolve Incident`
- Incident open/resolve events push notifications for operator visibility.
- Updated API phase marker:
  - `2.13-webops-incident-registry`

## Ops Notes
- Use manual incident resolution only when the monitor state is understood; new failing runs will reopen the incident stream automatically.
