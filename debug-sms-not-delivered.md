[OPEN]

# Debug Session: sms-not-delivered

## Symptom
- SMS shows “sent successfully” in the app, but the phone never receives it.

## Expected
- When the app reports success, the SMS should arrive on the destination handset within normal carrier delay.

## Scope
- Feature: SMS sending (all providers)
- Affected roles/pages: SMS module / any SMS sender

## Hypotheses (falsifiable)
1. Provider API returns an “accepted/queued” response but delivery fails later (insufficient credit, DND, sender ID not approved, blocked route).
2. Phone number normalization is wrong (e.g., missing country code, wrong prefix, leading zeros), so provider accepts but routes to invalid MSISDN.
3. App is hitting a sandbox/test endpoint or wrong provider credentials (messages not actually sent to live network).
4. Provider request fails silently (timeout/SSL/cURL), but UI treats it as success due to response parsing bug.
5. Messages are delivered, but from unexpected sender/channel (e.g., routed as WhatsApp/flash) or phone blocks unknown sender.

## Evidence To Collect
- Provider name selected, endpoint URL, HTTP status, provider response body, message_id/reference, normalized MSISDN.
- Delivery status query (if supported) using message_id/reference.

## Instrumentation Plan
- Add runtime logs around the SMS send call and response parsing.
- Capture normalized phone + provider payload (redact secrets) + provider response.

## Notes
- Do not change business logic until evidence confirms root cause.

