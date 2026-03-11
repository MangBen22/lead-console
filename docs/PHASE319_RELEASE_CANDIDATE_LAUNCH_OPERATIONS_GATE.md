# Phase 319: Release Candidate Launch Operations Gate

- Extended release candidate generation with launch operations context.
- Updated `deployment_release_candidate_snapshot()` to:
  - generate a launch operations snapshot
  - persist that snapshot to launch operations history with `release_candidate` source
  - include launch issue summary in the response
  - record launch fields on the candidate log entry
- Release candidate readiness now also requires launch operations state to be `ready`.
- Candidate records now include:
  - `launch_state`
  - `launch_issue_count`
  - `launch_blocked_modules`
  - `launch_review_modules`
  - `launch_snapshot_id`

This tightens release-candidate approval so it reflects the new launch-operations layer rather than older gate data alone.
