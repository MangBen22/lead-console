# Phase 121: Social Platform Catalog

## Summary
Replaced free-form social connector metadata with a platform catalog so connectors can be normalized against known auth modes, capability sets, and platform labels.

## Delivered
- Added API action:
  - `social.platforms.list`
- Added social platform catalog helpers for:
  - provider profiles
  - default connector types
  - auth mode validation
  - capability normalization
- `social.connectors.list` now returns decorated connector records with:
  - `account_label`
  - `capabilities_enabled`
  - `capabilities_supported`
  - `capability_gaps`
  - `profile`
- `social.connectors.save` now supports:
  - `account_label`
  - `expires_at`
  - normalized capabilities/auth mode defaults
- Added dashboard controls:
  - `Refresh Social Platforms`
  - `socialPlatforms`
- Updated API phase marker:
  - `2.07-social-platform-catalog`

## Ops Notes
- Use the platform catalog response as the source of truth for connector capability gating in later Social phases.
