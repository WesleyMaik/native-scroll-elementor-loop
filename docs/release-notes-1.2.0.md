# Native Scroll Loop 1.2.0

Native Scroll Loop 1.2.0 adds a visual, non-interactive Dots progress mode while preserving the existing Growing Bar and Scrollbar Thumb modes.

## Added

- Informational dots generated from reachable carousel positions.
- Item-based dots when arrows advance one item.
- Page-like dots when arrows advance one visible group.
- Automatic active-dot synchronization during scrolling, arrow navigation, and autoplay.
- Dynamic regeneration after resize, Elementor editor rerenders, Load More, Taxonomy Filter, and AJAX pagination.
- Dot Size and Dot Spacing Elementor style controls.
- Accessible `aria-valuetext` progress information without interactive dot controls.

## Compatibility

Validated against the installed environment:

- WordPress 7.0.2
- Elementor 4.0.7
- PRO Elements 4.0.4.2
- PHP 7.4 or newer

## Validation

- PHP integration tests passing.
- 12 JavaScript tests passing.
- PHP and JavaScript syntax checks passing.

After updating, fully reload open Elementor editor tabs to load the versioned `1.2.0` assets.
