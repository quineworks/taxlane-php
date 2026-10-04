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
`#0B8457`/`#34D399`) and the three-bar "Bracket Bars" mark geometry
(`rect x=6/13.5/21`, `width=5`, `rx=1.5`, heights `9/14/20`, bottom-aligned
in a `32×32` viewBox, third bar tinted) now match `quineworks/platform`'s
`brand/BRAND.md` spec byte-for-byte, spot-checked directly against it and
against the live geometry in that repo's `site/public/index.html` and
`quineworks/tax-lane`'s `web/src/App.tsx` header. A prior pass had flagged
this as unverified; a design-loop pass (`#11`) found the mark here had
actually drifted (`rx=1.2`, `28×28` viewBox, asymmetric margins) and
corrected all three candidates + their screenshots to the canonical
values.
