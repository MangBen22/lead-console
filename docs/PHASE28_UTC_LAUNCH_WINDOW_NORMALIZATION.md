# Phase 28 UTC Launch Window Normalization

## Delivered
- Added UTC datetime helpers in API:
  - `app_parse_utc_datetime`
  - `app_format_utc_datetime`
- Updated deployment guard launch-window logic to use normalized UTC values.
- Updated guard save action to normalize window start/end to `YYYY-MM-DDTHH:MM` UTC.
- Updated UI launch-window fields to explicit UTC text format.

## Why
- `datetime-local` uses browser-local time, which can conflict with server timezone and UTC labels.
- Normalizing in API ensures guard enforcement is consistent regardless of user/server timezone.

## Input Format
- `YYYY-MM-DDTHH:MM` (UTC), example:
  - `2026-03-02T18:00`

## Purpose
Prevents timezone drift and inconsistent launch-window enforcement during production cutover.
