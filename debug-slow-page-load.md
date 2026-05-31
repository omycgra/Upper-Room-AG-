# Debug Session: slow-page-load

## Session Metadata
- **Session ID**: slow-page-load
- **Status**: [OPEN]
- **Start Time**: 2026-05-28
- **Description**: Pages are loading very slowly; trying to find bottlenecks

## Hypotheses
1. Database queries are slow (e.g., unindexed tables, N+1 queries)
2. BirthdayService or SchemaState is running expensive operations on every request
3. AppConfig is making multiple database calls instead of caching
4. View rendering is taking too long (loading large data into memory)

## Evidence Collection Log
[To be filled during debugging]
