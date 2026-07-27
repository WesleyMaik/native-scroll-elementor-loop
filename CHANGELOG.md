# Changelog

All notable changes to Native Scroll Loop are documented in this file.

## [1.2.0] - 2026-07-26

### Added

- Non-interactive Dots progress mode.
- Dot targets based on reachable item or visible-group scroll positions.
- Active dot synchronization during native scrolling, arrows, autoplay, resize, and dynamic Loop Grid updates.
- Elementor style controls for dot size and spacing.
- Accessible progress value text without focusable or clickable dot controls.

### Changed

- Progress mode documentation and manual QA coverage now include informational dots.
- Frontend asset version updated to `1.2.0`.

## [1.1.3] - 2026-07-26

### Fixed

- Preserved carousel configuration inside Elementor editor rerenders.
- Restored wrapper state and frontend handler initialization inside the editor preview iframe.
- Isolated carousel navigation styles from global Elementor button rules.
