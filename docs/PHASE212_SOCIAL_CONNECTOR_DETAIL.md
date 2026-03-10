# Phase 212 - Social Connector Detail

- added `social.connectors.detail`
- returns one connector together with its recent:
  - sync log
  - retry queue
  - schedule queue
  - inbox threads
- added connector metadata including:
  - retry count
  - schedule count
  - open inbox count
  - days until expiry
  - last sync snapshot
- added connector detail UI and form prefill for connector editing and delivery drill-down
