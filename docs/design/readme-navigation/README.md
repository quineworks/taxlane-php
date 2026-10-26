# README navigation candidates

**Resolved — Candidate B shipped.** `tax-lane#3041` landed Candidate B
(the endpoints-at-a-glance index table) in `sdk-php/README.md`, synced here
in `025622d` ("sdk-php: add endpoints-at-a-glance index table to README
(#3041)"). This repo's live `README.md` `## Endpoints` section is
byte-for-byte that candidate. The three candidates below are kept as the
historical record of what was considered, not an open choice — don't
re-propose a second navigation scheme on top of the one already shipped.

Three candidates for navigating this repo's `README.md` — the package's
landing page on both GitHub and Packagist (Packagist mirrors a package's
README verbatim) — see the paired issue for the gap this addresses. Open
each `.html` file directly or view the matching PNG under `screenshots/`.

## Gap

`README.md` is 298 lines with 13 near-identical `### calculate*` endpoint
sections back to back (lines 73-231) and no way to jump to one — a reader
who wants `calculateCgt` has to scroll past PAYE, VAT, Presumptive, Direct
Assessment and CIT first, every time.

Pulled and checked nine comparable PHP SDK/API-client READMEs before
drafting these (`stripe/stripe-php`, `twilio/twilio-php`,
`PaystackHQ/paystack-php`, `mollie/mollie-api-php`,
`algolia/algoliasearch-client-php`, `spatie/laravel-permission`,
`laravel/scout`, `thephpleague/csv`, `firebase/php-jwt`) — **none use an
in-README table of contents**, so this isn't "everyone else has a ToC and
we don't." What they do instead is keep the README short (19-324 lines,
median well under 298) by linking out to a hosted docs site for the full
per-endpoint reference rather than inlining every example — e.g.
`respect/validation`'s README is 19 lines and points straight to its docs
site; Stripe and Twilio document only a handful of illustrative calls, not
every API resource.

`taxlane`'s README is long *because* its whole pitch is different from
that pattern on purpose — "zero Composer dependencies... no API key, no
account," every example runnable by copy-paste with no docs site required
(`README.md` lines 18-26) — and that's a real, already-shipped
differentiator worth keeping, not a mistake to fix by trimming examples
down to Stripe/Twilio's size. Given the README stays long by design, the
actual gap is narrower than "match a convention nobody else uses": once a
reference doc is ~300 lines with 13 repeated sections, GitHub's own
markdown renders `<details>` collapse and anchor links natively regardless
of whether other packages use them, and this repo's own prior design pass
(`#5`/PR#5) already set precedent for a navigable README landing page.
These three candidates are a read on "what helps a reader find one
endpoint in this specific long document," not a claim that peer SDKs are
doing this already.

Checked before opening this: zero open issues/PRs on this repo going in
besides `#10` (CEO-only manual social-preview upload, unrelated), no
`docs/product/` directory (this repo has no product specs of its own — see
`README.md`'s own "Distribution" section: it's a generated Packagist mirror
of `quineworks/tax-lane`'s `sdk-php/`), and no prior README-navigation
design pass in memory for this repo. `DesignSync` isn't reachable from this
non-interactive dispatch (`needs design-system authorization, /design-login
requires an interactive terminal` — a known, already-tracked gap per
`memory/reference/design-asset-tooling.md`, not re-escalated here).

**Important constraint, confirmed from `README.md`'s own "Distribution"
section**: *"This README's source of truth is `quineworks/tax-lane`'s
`sdk-php/` directory ... Don't edit this copy directly — the next sync will
overwrite it."* This run was scoped to `quineworks/taxlane-php` only, so
these were candidates only at the time — landing one for real needed a
follow-up engineering ticket against `quineworks/tax-lane`'s
`sdk-php/README.md` (same two-step pattern PR#5 used: `tax-lane#1982`
landed Candidate C from this repo's prior README-header pass). That
follow-up has since happened: `tax-lane#3041` landed Candidate B and it's
now live (see the note at the top of this file).

Previews approximate GitHub's rendered-markdown styling by hand (no network
access in this run to fetch GitHub's actual stylesheet or render through
GitHub itself) — close enough to judge layout and scanability, not a
pixel-perfect GitHub render. Each preview shows the real intro/Install/
Quickstart content unchanged, the real Endpoints heading, 2-3 real endpoint
bodies in full, and an explicit "N more … continue exactly as today" note
standing in for the other 10 (unchanged) — a full real-content diff is a
plain-text README edit, not a rendering concern, and is shown in the paired
PR description.

- **A — linked table of contents**: a `## Contents` block right after the
  intro, linking every top-level section plus all 13 endpoint methods as a
  nested list. Smallest change, most familiar GitHub pattern, zero new
  content to maintain beyond the links themselves.
- **B — endpoints-at-a-glance index table** (shipped): a two-column table
  (`Method` / `Calculates`) right at the top of `## Endpoints`, each method
  name linking down to its full section; no separate top-of-file ToC for
  the other 6 non-endpoint sections (they're short enough to reach by
  scrolling). Mirrors the "index table before full reference" pattern
  common in API-client READMEs. Needs a one-line description written per
  method (shown in the preview) and kept in sync if an endpoint is ever
  added — a small, bounded maintenance cost.
- **C — collapsible per-endpoint sections**: each of the 13 endpoint
  bodies wrapped in a native GitHub `<details><summary>` block, collapsed
  by default, with the one-line description in the summary row itself.
  Shortens the page dramatically at a glance (the reference collapses to
  13 one-line rows instead of ~160 lines of code blocks) at the cost of an
  extra click to see any one endpoint's body, and depends on GitHub's
  (confirmed, already used elsewhere at Quine Works) `<details>` markdown
  support rather than plain links.
