# README header candidates (issue #4)

Three distinct treatments for this repo's bare README header — no live
README.md changes here, this is the candidate record for product's review.

- `previews/candidate-a-badges-only-light.html` — badges only, no image asset.
- `previews/candidate-b-logo-lockup-{light,dark}.html` — TaxLane "Bracket
  Bars" mark + wordmark banner, uses `taxlane-lockup-on-{light,dark}.svg`.
- `previews/candidate-c-hero-snippet-light.html` — badges + an immediate
  runnable snippet + its output, no logo.

Rendered screenshots: `screenshots/`. See the PR description for the actual
markdown each candidate would drop into README.md and the trade-offs.

`taxlane-lockup-on-{light,dark}.svg` geometry/colors are copied from the
live "Bracket Bars" TaxLane product mark (`quineworks/platform`'s
`site/public/index.html` `.product-mark-tile` SVG + `brand/BRAND.md`'s
"Product accents" table: ink `#12131A`/`#F2F2F2`, `--tax-accent`
`#0B8457`/`#34D399`) — no new colors or geometry introduced.
