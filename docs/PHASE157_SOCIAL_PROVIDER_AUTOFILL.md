# Phase 157: Social Provider Autofill

## Summary
- added provider-aware autofill in the Social connector form
- surfaced the matched platform profile inline in the dashboard
- synced the form defaults from the Social platform catalog

## Autofill
- fills connector type from the provider default
- fills auth mode from the provider-supported auth list
- fills capabilities from the provider-supported capability list
- fills account label only when the current field is blank

## Version
- bumped plugin version to `2.0.0.177`
