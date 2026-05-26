[OPEN] Debug Session: member-import-timeout

## Symptom
- Importing members triggers: "Fatal error: Maximum execution time of 120 seconds exceeded" in app/Helpers/Database.php line 149.

## Expected
- Import completes successfully without timing out.

## Hypotheses
- H1: Too many DB queries per row (group/department lookups, duplicate checks) cause total runtime > 120s.
- H2: A specific DB query is slow/hanging (locks, network, missing index), so a single execute() consumes most time.
- H3: Schema checks (information_schema queries) are repeatedly executed and slow.
- H4: DB connection is slow/unresponsive during import (timeouts/retries).
- H5: File parsing is fine, but processing loop is large and needs batching/transaction changes.

## Evidence Plan
- Instrument Database::query to log slow queries (elapsed ms, route, driver).
- Instrument MemberController::importExcel to log start/end, row count, progress every N rows.

## Runs
- pre: pending logs
- post: pending logs

