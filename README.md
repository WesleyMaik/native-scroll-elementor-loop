# Native Scroll Loop

Native Scroll Loop adds an optional native horizontal carousel mode to the existing Elementor Pro Loop Grid widget. It preserves Elementor's query, skins, Loop Item rendering, filters, pagination, Load More, responsive controls, and editor lifecycle.

The carousel uses native browser scrolling and CSS Scroll Snap. It does not copy the Loop Grid widget, modify Elementor files, clone slides, or load a third-party slider library.

## Requirements

- WordPress 6.6 or newer
- PHP 7.4 or newer
- Elementor with the Loop Grid frontend APIs used by Elementor 4.0.7
- A compatible Elementor Pro implementation exposing `ELEMENTOR_PRO_VERSION` and Loop Grid;

The plugin intentionally lists only Elementor in `Requires Plugins` because compatible premium implementations can use different plugin directory names. Premium Loop Grid availability is validated at runtime.

## Installation

1. Place this directory in `wp-content/plugins/native-scroll-loop`.
2. Activate Elementor and the compatible premium implementation.
3. Activate Native Scroll Loop.
4. Edit an existing Loop Grid and open **Native Scroll Carousel**.
5. Enable **Native horizontal carousel** and configure the controls.

The Loop Grid remains unchanged while the feature is disabled. Carousel mode also stays inactive when Masonry is enabled or an alternate template uses a column span greater than one.

## Features

- Native horizontal scrolling and touch dragging
- Mandatory, proximity, or disabled CSS Scroll Snap
- Item or visible-group arrow advance
- Optional bidirectional edge wrapping when unavailable arrows remain enabled
- Custom Elementor icons and style controls
- Growing-bar and scrollbar-thumb progress modes
- Optional autoplay with hover, focus, interaction, visibility, and reduced-motion safeguards
- ArrowLeft, ArrowRight, Home, and End keyboard controls
- Logical RTL scroll normalization
- Multiple independent Loop Grid instances
- Elementor editor rerender cleanup
- Live editor preview for behavioral, layout, icon, and style controls
- Taxonomy Filter, AJAX pagination, and Load More refresh integration

## Architecture

- `includes/class-loop-grid-controls.php` injects controls into the original `loop-grid` widget.
- `includes/class-loop-grid-render.php` adds scoped state and accessible controls around the original rendered content.
- `includes/class-assets.php` registers and conditionally enqueues the frontend assets.
- `assets/js/native-scroll-loop.js` attaches an Elementor frontend handler for every installed Loop Grid skin.
- `assets/css/native-scroll-loop.css` changes only enabled Loop Grid containers into horizontal tracks.

The handler owns one instance per widget wrapper through a `WeakMap`. It completely removes listeners, observers, timers, animation frames, classes, and temporary attributes during teardown.

## Dynamic content

Taxonomy Filter and AJAX pagination replace `.elementor-widget-container` and rerun Elementor's widget-ready lifecycle. Load More appends direct `.e-loop-item` children and emits `elementor-pro/loop-builder/after-insert-posts`. The plugin handles both paths and uses a direct-child `MutationObserver` only as a scoped fallback.

## Validation

Run the local automated checks from this directory:

```bash
php tests/php/run.php
node tests/js/native-scroll-loop.test.js
```

Browser and Elementor editor acceptance checks are intentionally manual. See `docs/manual-qa-checklist.md`.

## Compatibility notes

The integration was developed from installed WordPress 7.0.2, Elementor 4.0.7, and Elementor PRO 4.0.4.2 source. Elementor PRO reports testing only through Elementor 4.0.4.2-ga, so the installed version skew requires manual runtime review. Confirmed hooks, markup, lifecycle behavior, and upgrade risks are documented in `docs/elementor-integration-notes.md`.
