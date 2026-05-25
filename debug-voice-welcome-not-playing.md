[OPEN]

# Debug Session: voice-welcome-not-playing

## Symptom
- After login as Pastor (and other roles), the voice welcome does not play.

## Expected
- On first landing page after login, the browser should speak a welcome message.
- For Pastor role: “Welcome Reverend {Name}.”

## Hypotheses (falsifiable)
1. The voice code is not executed because the trigger condition is false (e.g., route not `dashboard`, `just_logged_in` not set, or layout not included).
2. Browser autoplay/gesture policy blocks speech on page load (speech requires user interaction) and the browser silently cancels.
3. `speechSynthesis` is available but no voices are loaded yet, or utterance events show cancellation/error.
4. The script runs but errors before calling speech due to JS exception elsewhere on the page.
5. Session variable `just_logged_in` is being cleared too early or not set for Pastor login flow.

## Evidence Needed
- Whether the voice code block runs (client log)
- Values of: route, role, `just_logged_in`, `speechSynthesis` availability, voices count
- `SpeechSynthesisUtterance` events (`onstart`, `onend`, `onerror`)

