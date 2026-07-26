# Manual QA Checklist

These runtime checks were intentionally left unexecuted for the site owner. Complete them on a staging copy before production deployment.

## Baseline

- [ ] Confirm a disabled Loop Grid is visually and behaviorally unchanged.
- [ ] Confirm enabling the carousel does not change Loop Item content or dynamic fields.
- [ ] Confirm the browser console has no errors on frontend and editor preview.
- [ ] Confirm two or more enabled Loop Grids operate independently on one page.
- [ ] Confirm Masonry and incompatible alternate-template spans remain regular Loop Grids.

## Responsive and input

- [ ] Review desktop, tablet, and mobile breakpoints.
- [ ] Confirm existing responsive columns and gaps are respected.
- [ ] Confirm the configured mobile card width shows the intended partial next item.
- [ ] Test mouse wheel, trackpad, pointer drag, and touch drag.
- [ ] Test left-to-right and right-to-left pages.

## Navigation and progress

- [ ] Test item and visible-group arrow advance modes.
- [ ] Test top-right, bottom-right, and split-side arrow placements.
- [ ] Confirm unavailable arrows disable only when configured.
- [ ] Test growing-bar and scrollbar-thumb progress modes.
- [ ] Confirm navigation and progress hide when content does not overflow.
- [ ] Test ArrowLeft, ArrowRight, Home, and End from the focused scroller.

## Autoplay and accessibility

- [ ] Test autoplay interval, rewind, and stop-at-end behavior.
- [ ] Test pause and resume on hover, focus, pointer/touch interaction, and wheel input.
- [ ] Test pause while the browser tab is hidden.
- [ ] Test the configured interaction resume delay.
- [ ] Confirm autoplay is disabled under `prefers-reduced-motion: reduce`.
- [ ] Confirm buttons, focus-visible treatment, labels, disabled state, and progressbar values with a screen reader.
- [ ] Confirm autoplay never moves keyboard focus.

## Elementor and dynamic content

- [ ] Enable, disable, and change controls in the Elementor editor and confirm clean rerenders.
- [ ] Switch Loop Grid skins and confirm handler initialization.
- [ ] Test the Taxonomy Filter with results, empty results, and repeated filter changes.
- [ ] Test Load More through multiple append operations.
- [ ] Test AJAX numbered, previous, and next pagination.
- [ ] Confirm progress, arrow limits, and autoplay refresh after every dynamic update.
- [ ] Confirm no duplicate click, scroll, or autoplay behavior appears after repeated rerenders.

