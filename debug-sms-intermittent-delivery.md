[OPEN]

# Debug Session: sms-intermittent-delivery

## Symptom
- App reports “SMS sent successfully”, but delivery is inconsistent (sometimes arrives, sometimes not).

## Expected
- If the provider accepts the message, delivery should be consistent; if delivery fails, the app should show the real provider error/status.

## Hypotheses (falsifiable)
1. Provider returns “success/queued” but later drops delivery (DND, sender ID not approved, route blocked, low/expired credit).
2. Some recipients are being normalized incorrectly (invalid MSISDN) causing silent non-delivery.
3. Provider/API key mismatch (wrong key, wrong environment/route) makes provider accept but not deliver.
4. Intermittent network/SSL failures cause partial/duplicate attempts and the UI misinterprets response as success.
5. Sender ID is being rejected/filtered by carriers depending on content/time, causing inconsistent delivery.

## Evidence Needed
- Provider response body (code/status/message), HTTP code, sender id, normalized MSISDN, and (if provided) provider reference/message_id.
- Whether failures correlate with specific numbers or categories (tithe/welfare vs bulk).

## Current Instrumentation
- SmsService logs debug events (provider, normalized numbers sample, MNotify response snippets) and can be viewed via:
  - /debug/sms-logs?last=200
  - /debug/sms-logs/clear

