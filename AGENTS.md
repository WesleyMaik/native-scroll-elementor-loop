# AGENTS.md

## Project Overview

This repository contains a WordPress plugin that extends the existing Elementor Pro Loop Grid widget with an optional native horizontal carousel mode.

The plugin must enhance the original Loop Grid through Elementor hooks whenever technically viable.

It must not copy, replace, fork, or directly modify Elementor or Elementor Pro source files.

The original Loop Grid remains responsible for:

- Loop Item template rendering
- Dynamic content
- Query configuration
- Taxonomy filters
- Pagination and Load More
- Elementor editor integration
- Elementor responsive settings
- Existing Loop Grid styles and skins

The plugin adds only optional carousel behavior:

- Native horizontal scrolling
- CSS Scroll Snap
- Desktop navigation arrows
- Mobile touch dragging
- Scroll progress indicator
- Optional autoplay
- Accessibility and reduced-motion support
- Compatibility with dynamic Loop Grid updates

## Primary Architectural Decision

Prefer extending the original Elementor Pro Loop Grid through hooks.

Do not register a duplicate Loop Grid widget unless the installed Elementor and Elementor Pro versions make the hook-based approach technically impossible.

Do not inherit from Elementor Pro internal classes unless no safe hook-based architecture exists.

Before implementing any integration:

1. Inspect the installed Elementor and Elementor Pro versions.
2. Inspect the actual Loop Grid widget class and widget name.
3. Inspect current render hooks, control hooks, frontend hooks, and generated markup.
4. Confirm selectors and lifecycle behavior from the installed source code.
5. Document the findings in `docs/elementor-integration-notes.md`.

Do not assume that namespaces, hook names, control IDs, CSS classes, or DOM structure from previous Elementor versions are still correct.

## Repository Scope

The plugin should use a structure close to:

```text
native-scroll-loop/
├── native-scroll-loop.php
├── uninstall.php
├── AGENTS.md
├── README.md
├── includes/
│   ├── class-plugin.php
│   ├── class-assets.php
│   ├── class-loop-grid-controls.php
│   └── class-loop-grid-render.php
├── assets/
│   ├── css/
│   │   └── native-scroll-loop.css
│   └── js/
│       └── native-scroll-loop.js
├── docs/
│   ├── implementation-plan.md
│   └── elementor-integration-notes.md
└── tests/
```

The structure may be adjusted only when the installed environment materially benefits from another organization.

## Coding Standards

Use:

- PHP namespaces
- Strict direct-access guards
- WordPress escaping and sanitization
- WordPress translation functions
- Unique class names, namespaces, script handles, and style handles
- WordPress coding standards
- Defensive compatibility checks
- Small classes with clear responsibilities
- Clear inline comments only where the integration is non-obvious

Avoid:

- Global functions unless required by WordPress hooks
- Modifying Elementor or Elementor Pro files
- Third-party frontend carousel libraries
- Global DOM selectors that can leak between widget instances
- Hardcoded site-specific colors
- Hardcoded scroll distances
- Duplicated event listeners
- Unmanaged timers, observers, and animation frames
- Direct dependence on undocumented Elementor internals without a documented fallback

## Frontend Constraints

The carousel must use native browser scrolling.

Do not install or use:

- Swiper
- Slick
- Splide
- Flickity
- Embla
- Owl Carousel
- Any other slider or carousel dependency

The implementation must preserve natural touch dragging on mobile.

The core scrolling model should use:

```css
overflow-x: auto;
overscroll-behavior-inline: contain;
scroll-snap-type: x mandatory;
-webkit-overflow-scrolling: touch;
```

Each Loop Item should use:

```css
scroll-snap-align: start;
```

Use `Element.scrollBy()` or `Element.scrollTo()` for arrow navigation and autoplay.

## Elementor Integration Rules

The plugin must:

- Add controls to the original Loop Grid widget
- Leave the Loop Grid unchanged when the feature is disabled
- Scope all frontend behavior to the current widget wrapper
- Support multiple carousel-enabled Loop Grids on the same page
- Work in the public frontend
- Work in Elementor editor preview
- Reinitialize safely after widget rerenders
- Handle dynamic content replacement or appending
- Prefer official Elementor lifecycle events where available
- Use a scoped `MutationObserver` only as a fallback

When the original Loop Grid markup or selector assumptions change, fail safely without breaking Elementor.

If a required integration point is unavailable:

- Do not cause a fatal error
- Show a clear administrator notice when appropriate
- Document the compatibility limitation

## Controls Requirements

The new Elementor section should be named:

```text
Native Scroll Carousel
```

The feature must be disabled by default.

Controls should cover:

### General

- Enable native horizontal carousel
- Enable CSS Scroll Snap
- Snap strictness
- Scroll snap stop
- Scroll behavior
- Arrow advance mode

### Navigation

- Show arrows
- Previous icon
- Next icon
- Arrow position
- Hide arrows on mobile
- Disable unavailable arrows

### Progress

- Show progress
- Growing bar mode
- Scrollbar thumb mode
- Progress placement

### Autoplay

- Enable autoplay
- Interval
- Pause on hover
- Pause on focus
- Pause on pointer or touch interaction
- Pause while the browser tab is hidden
- Resume delay
- Stop at end or return to beginning

### Responsive Layout

Prefer the Loop Grid's existing responsive column and gap controls when they can be accessed reliably.

If using Elementor internal control IDs would be fragile, add dedicated responsive controls for:

- Desktop visible items
- Tablet visible items
- Mobile card width percentage
- Carousel gap

Document the decision.

## JavaScript Rules

Prefer the current Elementor frontend handler architecture found in the installed version.

Use `elementorModules.frontend.handlers.Base` when supported.

The handler must:

- Initialize only when the feature is enabled
- Scope selectors to the current widget
- Use passive scroll listeners
- Batch frequent progress updates with `requestAnimationFrame` when useful
- Use `ResizeObserver`
- Clean up all listeners, observers, timers, and animation frames
- Avoid autoplay when content does not overflow
- Respect `prefers-reduced-motion`
- Pause autoplay while the document is hidden
- Avoid fighting manual scrolling
- Handle keyboard controls
- Handle dynamic item replacement
- Avoid duplicate initialization

## CSS Rules

CSS must:

- Preserve Elementor Loop Item styling
- Avoid styling inside Loop Item templates unnecessarily
- Hide the native scrollbar without disabling scrolling
- Use CSS custom properties for responsive sizing
- Avoid hardcoded product-specific presentation styles
- Keep arrows and progress styling configurable through Elementor controls
- Preserve touch scrolling
- Respect reduced motion

## Accessibility Requirements

Implement:

- Native `<button>` controls
- Descriptive `aria-label` values
- Disabled states at carousel limits
- Keyboard support for:
  - ArrowLeft
  - ArrowRight
  - Home
  - End
- Progressbar ARIA values
- Focus-visible styles
- Reduced-motion behavior
- No cloned slides
- No automatic keyboard focus movement during autoplay

## Performance Requirements

The plugin must:

- Add no third-party frontend dependency
- Load assets only when needed when Elementor permits
- Avoid unnecessary layout reads and writes
- Avoid global mutation observers
- Use scoped observers
- Stop autoplay when no overflow exists
- Clean up every registered resource
- Avoid unnecessary DOM wrappers that interfere with Elementor

## Security Requirements

The plugin must:

- Escape rendered output
- Sanitize persisted values
- Validate numeric ranges
- Never store secrets
- Never execute arbitrary user-provided JavaScript
- Never modify third-party plugin files
- Fail safely when dependencies are missing

## Validation Requirements

Before declaring the task complete:

1. Run PHP syntax checks on all PHP files.
2. Run available WordPress coding-standard checks.
3. Run JavaScript linting when configured.
4. Check for browser console errors.
5. Test multiple widget instances.
6. Test desktop, tablet, and mobile widths.
7. Test touch and pointer dragging.
8. Test arrows and disabled states.
9. Test both progress modes.
10. Test autoplay and all pause conditions.
11. Test reduced motion.
12. Test Elementor editor rerendering.
13. Test Taxonomy Filter integration when available.
14. Test Load More or AJAX replacement when available.
15. List any manual tests that could not be executed.

## Documentation Requirements

Maintain:

- `README.md`
- `docs/implementation-plan.md`
- `docs/elementor-integration-notes.md`
- Manual QA checklist
- Compatibility notes
- Installation and usage instructions

## Git Workflow

Make focused semantic commits.

Recommended commit groups:

```text
feat: scaffold native scroll loop extension
feat: add loop grid carousel controls
feat: implement native carousel frontend behavior
style: add carousel and control styling
docs: document installation and compatibility
test: add validation and manual qa checklist
```

Do not commit:

- Temporary files
- Debug logs
- Generated archives unless explicitly requested
- Modified Elementor or Elementor Pro files
- Secrets or credentials

## Agent Workflow

Before editing:

1. Inspect the repository.
2. Inspect installed Elementor and Elementor Pro code.
3. Read this file.
4. Read `docs/implementation-plan.md`.
5. Present a concise implementation plan.
6. Identify compatibility risks.
7. Implement without waiting for confirmation unless an action is destructive or security-sensitive.

During implementation:

- Make focused changes
- Preserve existing behavior
- Avoid unrelated refactors
- Report confirmed issues early
- Re-run validation after meaningful changes

At completion:

1. Show the changed file tree.
2. Summarize the architecture.
3. Report commands and validation results.
4. List remaining manual tests.
5. Propose semantic commits.
