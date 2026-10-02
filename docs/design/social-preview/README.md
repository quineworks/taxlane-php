# Social preview card candidates

Three 1280×640px candidates for this repo's GitHub social preview image
(Settings → General → Social preview) — see the paired issue for the gap
this addresses. Open each `.html` file directly or view the matching PNG
under `screenshots/`.

GitHub has no API to set a repo's social preview image — it's a one-time
manual upload under repo Settings, not something a PR can land. These are
candidates for product to pick from; applying the pick is a manual
follow-up step once a candidate is chosen (see the paired issue).

- **A — brand lockup**: dark card, mark + wordmark + tagline, a ghosted
  code snippet for texture. Brand-forward, no code shown clearly.
- **B — code window**: light card, mark + title up top, a faux editor
  window below with the SDK's real hero snippet and its real output —
  echoes the "prove it works in 3 seconds" pattern already picked for the
  README header (`#5`, Candidate C).
- **C — stat row**: dark card, mark + tagline + a bold stat row
  (calculators / dependencies / API keys / license). Poster-style, leans
  on numbers over a code sample.

Brand colors used (ink `#12131A`/`#F2F2F2`, `--tax-accent`
`#0B8457`/`#34D399`) and the three-bar mark geometry (`x=6/13.5/21`,
heights `9/14/20`, third bar tinted) match the values recorded in
`taxlane-php#4`/`#5` — not re-verified against `quineworks/platform`'s
`brand/BRAND.md` directly this run, since that repo is out of this run's
scope; flagged in the paired issue as worth a byte-for-byte spot-check
before a candidate is actually applied.
