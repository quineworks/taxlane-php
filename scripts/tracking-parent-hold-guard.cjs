'use strict';

// platform#3253: a self-healing backstop for the `tracking-parent-hold`
// bypass pattern that has now recurred three times (#1121, tax-lane#820/
// #838, tax-lane#2547) — an agent closes a held tracking parent directly
// (not via `tracking-parent-auto-closer.yml`), sometimes stripping the hold
// label in the same action, even immediately after the same agent run
// flagged an unmet DoD gap. `manager.md`/`reviewer.md` already carry a
// prompt guardrail against this (#1804) but prose doesn't reliably survive
// a long/multi-step agent run — this needs to not depend on the dispatched
// agent remembering/following it.
//
// Unlike `tracking-parent-auto-closer.cjs`, this guard never needs the
// Sub-issues API: `tracking-parent-hold` is, by convention, applied
// exclusively to tracking parents as the "more work remains" escape hatch
// (see `tracking-parent-auto-closer.cjs`'s own `decideClose()`), so the
// closed issue's own label history is sufficient to tell a bypass from a
// legitimate close — no need to independently re-derive "is this a
// tracking parent".
//
// decideBypass({ events, closedAt, closedByLogin, staleWindowMs }) ->
//   { isBypass, reason }
//
// events: the closed issue's own Issue Events
//   (`GET /repos/{owner}/{repo}/issues/{number}/events`), each with at
//   least `event` ("labeled"/"unlabeled"/...), `label.name`, `actor.login`,
//   `created_at`. Only `tracking-parent-hold` labeled/unlabeled events at
//   or before `closedAt` matter here.
// closedAt: ISO timestamp of the close (the webhook's
//   `issue.closed_at`, not derived from the events list, since the events
//   API can lag or omit the terminal `closed` event depending on delivery
//   timing).
// closedByLogin: the actor who performed the close (the webhook's
//   `sender.login`). `tracking-parent-auto-closer.yml` only ever acts via
//   `github.token`, so its closes always show as `github-actions[bot]` —
//   any other actor closing an issue that carried the hold label is, by
//   definition, not that workflow.
// staleWindowMs: how recently an `unlabeled(tracking-parent-hold)` event
//   must precede the close to still count as "stripped at close time"
//   rather than an earlier, separate, deliberate unhold. Defaults to 10
//   minutes — generous for a single agent-run close sequence (the
//   tax-lane#2547 incident's gap was ~1s) while not flagging an unhold
//   left to sit for a while before an unrelated later close.
function decideBypass({ events, closedAt, closedByLogin, staleWindowMs = 10 * 60 * 1000 }) {
  if (closedByLogin === 'github-actions[bot]') {
    return {
      isBypass: false,
      reason: 'Closed by github-actions[bot] — presumed tracking-parent-auto-closer.yml\'s own legitimate close, not a bypass.',
    };
  }

  const closedAtMs = new Date(closedAt).getTime();
  const holdEvents = (Array.isArray(events) ? events : [])
    .filter((e) => (e.event === 'labeled' || e.event === 'unlabeled') &&
      e.label && e.label.name === 'tracking-parent-hold')
    .filter((e) => new Date(e.created_at).getTime() <= closedAtMs)
    .sort((a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime());

  if (holdEvents.length === 0) {
    return {
      isBypass: false,
      reason: 'tracking-parent-hold was never applied to this issue before it closed — nothing to guard.',
    };
  }

  const last = holdEvents[holdEvents.length - 1];
  const lastActor = last.actor && last.actor.login;

  if (last.event === 'labeled') {
    return {
      isBypass: true,
      reason: `tracking-parent-hold was present (last labeled by ${lastActor} at ${last.created_at}) ` +
        `when ${closedByLogin} closed this issue directly — bypassing tracking-parent-auto-closer.yml.`,
    };
  }

  const gapMs = closedAtMs - new Date(last.created_at).getTime();
  if (gapMs <= staleWindowMs) {
    return {
      isBypass: true,
      reason: `tracking-parent-hold was stripped by ${lastActor} only ${Math.round(gapMs / 1000)}s before ` +
        `${closedByLogin} closed this issue directly — treating a same-session label-then-close as a ` +
        'bypass of tracking-parent-auto-closer.yml.',
    };
  }

  return {
    isBypass: false,
    reason: `tracking-parent-hold was removed ${Math.round(gapMs / 1000)}s before close, outside the ` +
      `${Math.round(staleWindowMs / 1000)}s stale window — looks like a deliberate, separate unhold rather ` +
      'than a same-session bypass.',
  };
}

module.exports = { decideBypass };

if (require.main === module) {
  // Wired up by tracking-parent-hold-guard.yml: fetch this issue's own
  // `GET .../events`, call decideBypass() with the webhook's closed_at/
  // sender.login, and on a bypass reopen + re-add the hold + comment.
  console.log('tracking-parent-hold-guard: no live entrypoint here — see tracking-parent-hold-guard.yml.');
}
