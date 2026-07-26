# Native Scroll Loop — Implementation Plan

## 1. Objective

Build a production-ready WordPress plugin that adds an optional native horizontal carousel mode to the existing Elementor Pro Loop Grid widget.

The plugin must extend the original widget through Elementor hooks whenever technically viable.

It must not create a copied Loop Grid widget, fork Elementor Pro code, or modify third-party files.

When the feature is disabled, the Loop Grid must behave exactly as it currently does.

## 2. Core Experience

The final experience should follow these design and interaction principles:

- Native browser horizontal scrolling
- CSS Scroll Snap instead of a JavaScript slider engine
- Touch dragging on mobile
- Arrow controls on desktop
- Progress indicator below or beside the controls
- Optional autoplay
- No third-party carousel dependency
- Minimal, professional visual language
- Progressive enhancement
- Full compatibility with existing Loop Item templates

## 3. Primary Architecture

### 3.1 Preferred approach

Extend the existing Elementor Pro Loop Grid by hooks.

The plugin should:

1. Detect the original Loop Grid widget during Elementor registration.
2. Inject a new control section into that widget.
3. Add settings and CSS classes only when carousel mode is enabled.
4. Register a frontend handler for the original Loop Grid widget.
5. Convert the existing `.elementor-loop-container` into a native horizontal scroller.
6. Add or inject navigation and progress UI without replacing Loop Item rendering.
7. Reinitialize after editor rerenders and dynamic content updates.

### 3.2 Fallback approach

Only when the installed versions provide no safe hook-based path:

- document the missing hook or lifecycle capability;
- use the least invasive fallback;
- avoid inheriting internal Elementor Pro classes unless absolutely necessary;
- fail safely rather than breaking the original widget.

## 4. Required Environment Inspection

Before implementation, inspect the real installed code.

Record:

- WordPress version
- Elementor version
- Elementor Pro version
- Loop Grid widget class
- Loop Grid widget name
- Widget control hooks
- Element render hooks
- Frontend ready hook
- Editor lifecycle behavior
- Loop container selector
- Loop Item selector
- Taxonomy Filter behavior
- Load More behavior
- AJAX pagination behavior
- Script and style dependency behavior

Write findings to:

```text
docs/elementor-integration-notes.md
```

Do not implement against guessed selectors or namespaces.

## 5. Proposed Plugin Structure

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

## 6. Elementor Controls

Create a new content section:

```text
Native Scroll Carousel
```

The feature must be disabled by default.

### 6.1 General controls

- Enable native horizontal carousel
- Enable CSS Scroll Snap
- Snap strictness:
  - mandatory
  - proximity
- Enable `scroll-snap-stop: always`
- Scroll behavior:
  - smooth
  - instant
- Arrow advance:
  - one item
  - one visible group

### 6.2 Navigation controls

- Show arrows
- Previous icon
- Next icon
- Arrow placement:
  - top right
  - bottom right
  - split sides
- Hide arrows on mobile
- Disable unavailable arrows

### 6.3 Progress controls

- Show progress
- Progress mode:
  - growing bar
  - scrollbar thumb
- Progress placement:
  - below carousel
  - beside navigation

### 6.4 Autoplay controls

- Enable autoplay
- Interval in milliseconds
- Pause on hover
- Pause on focus
- Pause on pointer or touch interaction
- Pause when the document is hidden
- Resume delay after interaction
- End behavior:
  - return to beginning
  - stop at end

Do not implement an infinite clone-based loop.

### 6.5 Responsive layout controls

First investigate whether the existing Loop Grid responsive columns and gap values can be reused safely.

If their control IDs or internal data shape are unstable, add dedicated responsive controls:

- visible items on desktop
- visible items on tablet
- mobile item width percentage
- carousel gap

Recommended defaults:

```text
Desktop: 3 items
Tablet: 2 items
Mobile: 78% item width
Gap: 20px desktop/tablet, 12px mobile
```

## 7. Style Controls

Add Elementor style controls for:

- arrow icon color
- arrow background
- arrow border color
- arrow hover icon color
- arrow hover background
- arrow hover border color
- arrow button size
- arrow icon size
- arrow border width
- arrow border radius
- navigation spacing
- progress color
- progress track color
- progress height
- progress spacing
- thumb minimum width when scrollbar-thumb mode is used

Avoid hardcoded storefront colors in the final plugin.

## 8. Markup Strategy

The original Loop Grid and Loop Item markup must remain intact.

The plugin may add:

- wrapper classes
- data attributes
- navigation buttons
- progress markup

Prefer render hooks around the original widget over duplicating render logic.

Example conceptual structure:

```html
<div
  class="elementor-widget-loop-grid native-scroll-loop"
  data-native-scroll-loop
  data-native-scroll-settings="..."
>
  <div class="native-loop-navigation">
    <button type="button" data-native-loop-previous></button>
    <button type="button" data-native-loop-next></button>
  </div>

  <div class="elementor-loop-container native-loop-scroller">
    <!-- Original Elementor Loop Items -->
  </div>

  <div class="native-loop-footer">
    <div
      class="native-loop-progress"
      role="progressbar"
      aria-valuemin="0"
      aria-valuemax="100"
      aria-valuenow="0"
    >
      <span class="native-loop-progress__bar"></span>
    </div>
  </div>
</div>
```

Do not assume this exact wrapper hierarchy until installed hooks are inspected.

## 9. CSS Baseline

The following CSS is the implementation baseline derived from the standalone demo.

It must be adapted to Elementor's real markup and converted to configurable CSS custom properties where appropriate.

```css
.native-scroll-loop {
  --native-loop-gap: var(--grid-column-gap, 20px);
  --native-loop-scroll-behavior: smooth;
  --native-loop-columns: 3;
  --native-loop-columns-tablet: 2;
  --native-loop-mobile-width: 78%;
  --native-loop-arrow-size: 48px;
  --native-loop-progress-height: 2px;

  position: relative;
}

.native-loop-navigation {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-bottom: 24px;
}

.native-loop-arrow {
  display: inline-grid;
  width: var(--native-loop-arrow-size);
  height: var(--native-loop-arrow-size);
  padding: 0;
  place-items: center;

  border: 1px solid currentColor;
  border-radius: 999px;
  background: transparent;
  color: inherit;

  cursor: pointer;

  transition:
    opacity 200ms ease,
    background-color 200ms ease,
    border-color 200ms ease,
    color 200ms ease,
    transform 200ms ease;
}

.native-loop-arrow:hover:not(:disabled) {
  transform: translateY(-1px);
}

.native-loop-arrow:active:not(:disabled) {
  transform: translateY(0);
}

.native-loop-arrow:disabled {
  cursor: not-allowed;
  opacity: 0.3;
}

.native-loop-arrow svg {
  width: 1em;
  height: 1em;
}

.native-scroll-loop
  .native-loop-scroller.elementor-loop-container {
  display: grid;
  grid-auto-flow: column;

  grid-auto-columns:
    calc(
      (
        100% -
        (
          var(--native-loop-gap) *
          (
            var(--native-loop-columns) - 1
          )
        )
      ) /
      var(--native-loop-columns)
    );

  grid-template-columns: none;
  gap: var(--native-loop-gap);

  overflow-x: auto;
  overflow-y: visible;

  overscroll-behavior-inline: contain;
  scroll-behavior: var(--native-loop-scroll-behavior);
  scroll-padding-inline: 1px;

  scrollbar-width: none;
  -webkit-overflow-scrolling: touch;
}

.native-scroll-loop
  .native-loop-scroller::-webkit-scrollbar {
  display: none;
}

.native-scroll-loop--snap-mandatory
  .native-loop-scroller {
  scroll-snap-type: x mandatory;
}

.native-scroll-loop--snap-proximity
  .native-loop-scroller {
  scroll-snap-type: x proximity;
}

.native-scroll-loop
  .native-loop-scroller
  > .e-loop-item {
  min-width: 0;
  scroll-snap-align: start;
}

.native-scroll-loop--snap-stop
  .native-loop-scroller
  > .e-loop-item {
  scroll-snap-stop: always;
}

.native-loop-footer {
  display: flex;
  align-items: center;
  margin-top: 24px;
}

.native-loop-progress {
  position: relative;
  width: 100%;
  height: var(--native-loop-progress-height);
  overflow: hidden;
  background: currentColor;
  opacity: 0.2;
}

.native-loop-progress__bar {
  display: block;
  width: 100%;
  height: 100%;
  background: currentColor;
  opacity: 1;

  transform: scaleX(0);
  transform-origin: left center;

  will-change: transform;
}

.native-loop-progress--thumb {
  overflow: visible;
}

.native-loop-progress--thumb
  .native-loop-progress__bar {
  position: absolute;
  top: 0;
  left: 0;
  width: var(--native-loop-thumb-width, 40px);
  transform: translateX(
    var(--native-loop-thumb-offset, 0px)
  );
  transform-origin: center;
}

@media (max-width: 1024px) {
  .native-scroll-loop
    .native-loop-scroller.elementor-loop-container {
    grid-auto-columns:
      calc(
        (
          100% -
          (
            var(--native-loop-gap) *
            (
              var(--native-loop-columns-tablet) - 1
            )
          )
        ) /
        var(--native-loop-columns-tablet)
      );
  }
}

@media (max-width: 767px) {
  .native-scroll-loop {
    --native-loop-gap: 12px;
  }

  .native-scroll-loop
    .native-loop-scroller.elementor-loop-container {
    grid-auto-columns:
      var(--native-loop-mobile-width);
  }

  .native-scroll-loop--hide-arrows-mobile
    .native-loop-navigation {
    display: none;
  }
}

@media (prefers-reduced-motion: reduce) {
  .native-scroll-loop
    .native-loop-scroller {
    scroll-behavior: auto;
  }

  .native-loop-arrow,
  .native-loop-progress__bar {
    transition-duration: 0.01ms;
  }
}
```

### 9.1 CSS implementation notes

The implementation agent must verify:

- the actual Loop Item selector;
- whether Elementor applies `display: grid` inline;
- whether `grid-template-columns` requires stronger specificity;
- how Elementor exposes column gap values;
- whether editor preview adds wrapper elements;
- whether the native scrollbar needs restoration inside the editor for debugging;
- whether split-side arrows require absolute positioning and safe overflow handling.

## 10. JavaScript Baseline

The following JavaScript is the behavioral baseline derived from the standalone demo.

It must be adapted to:

- the installed Elementor handler API;
- the original Loop Grid widget name;
- the actual DOM;
- multiple widget instances;
- editor rerendering;
- Taxonomy Filter updates;
- Load More;
- dynamic content replacement;
- the final serialized control settings.

```js
(() => {
  "use strict";

  class NativeScrollLoopHandler
    extends elementorModules.frontend.handlers.Base {
    getDefaultSettings() {
      return {
        selectors: {
          root: "[data-native-scroll-loop]",
          loopContainer: ".elementor-loop-container",
          item: ".e-loop-item",
          previousButton: "[data-native-loop-previous]",
          nextButton: "[data-native-loop-next]",
          progress: "[data-native-loop-progress]",
          progressBar:
            "[data-native-loop-progress-bar]",
          navigation: ".native-loop-navigation"
        }
      };
    }

    getDefaultElements() {
      const selectors =
        this.getSettings("selectors");

      return {
        $root: this.$element.find(
          selectors.root
        ),
        $loopContainer: this.$element.find(
          selectors.loopContainer
        ),
        $items: this.$element.find(
          selectors.item
        ),
        $previousButton: this.$element.find(
          selectors.previousButton
        ),
        $nextButton: this.$element.find(
          selectors.nextButton
        ),
        $progress: this.$element.find(
          selectors.progress
        ),
        $progressBar: this.$element.find(
          selectors.progressBar
        ),
        $navigation: this.$element.find(
          selectors.navigation
        )
      };
    }

    onInit() {
      super.onInit();

      this.container =
        this.elements.$loopContainer.get(0);

      this.root =
        this.elements.$root.get(0);

      if (!this.container || !this.root) {
        return;
      }

      this.config = this.readConfig();

      if (!this.config.enabled) {
        return;
      }

      this.autoplayTimer = null;
      this.interactionTimer = null;
      this.scrollFrame = null;
      this.isInteracting = false;

      this.reducedMotion = window.matchMedia(
        "(prefers-reduced-motion: reduce)"
      );

      this.handleScroll =
        this.handleScroll.bind(this);

      this.handleKeydown =
        this.handleKeydown.bind(this);

      this.handlePrevious =
        this.handlePrevious.bind(this);

      this.handleNext =
        this.handleNext.bind(this);

      this.beginInteraction =
        this.beginInteraction.bind(this);

      this.endInteraction =
        this.endInteraction.bind(this);

      this.handleVisibilityChange =
        this.handleVisibilityChange.bind(this);

      this.handleResize =
        this.handleResize.bind(this);

      this.handleReducedMotionChange =
        this.handleReducedMotionChange.bind(this);

      this.prepareMarkup();
      this.bindEvents();
      this.observeChanges();
      this.updateInterface();
      this.startAutoplay();
    }

    readConfig() {
      const rawSettings =
        this.root.dataset.nativeScrollSettings ||
        "{}";

      try {
        return JSON.parse(rawSettings);
      } catch {
        return {};
      }
    }

    prepareMarkup() {
      this.root.classList.add(
        "native-scroll-loop--initialized"
      );

      this.container.classList.add(
        "native-loop-scroller"
      );

      this.container.setAttribute(
        "tabindex",
        "0"
      );

      this.container.setAttribute(
        "aria-label",
        this.config.ariaLabel ||
          "Vitrine de itens"
      );

      this.root.classList.toggle(
        "native-scroll-loop--snap-mandatory",
        this.config.snapStrictness ===
          "mandatory"
      );

      this.root.classList.toggle(
        "native-scroll-loop--snap-proximity",
        this.config.snapStrictness ===
          "proximity"
      );

      this.root.classList.toggle(
        "native-scroll-loop--snap-stop",
        Boolean(this.config.snapStop)
      );

      this.root.classList.toggle(
        "native-scroll-loop--hide-arrows-mobile",
        Boolean(
          this.config.hideArrowsMobile
        )
      );

      this.root.style.setProperty(
        "--native-loop-scroll-behavior",
        this.config.behavior === "instant"
          ? "auto"
          : "smooth"
      );
    }

    bindEvents() {
      this.container.addEventListener(
        "scroll",
        this.handleScroll,
        { passive: true }
      );

      this.container.addEventListener(
        "keydown",
        this.handleKeydown
      );

      this.elements.$previousButton
        .get(0)
        ?.addEventListener(
          "click",
          this.handlePrevious
        );

      this.elements.$nextButton
        .get(0)
        ?.addEventListener(
          "click",
          this.handleNext
        );

      if (this.config.pauseOnHover) {
        this.root.addEventListener(
          "mouseenter",
          this.beginInteraction
        );

        this.root.addEventListener(
          "mouseleave",
          this.endInteraction
        );
      }

      if (
        this.config.pauseOnInteraction
      ) {
        this.container.addEventListener(
          "pointerdown",
          this.beginInteraction
        );

        this.container.addEventListener(
          "pointerup",
          this.endInteraction
        );

        this.container.addEventListener(
          "pointercancel",
          this.endInteraction
        );
      }

      if (this.config.pauseOnFocus) {
        this.container.addEventListener(
          "focusin",
          this.beginInteraction
        );

        this.container.addEventListener(
          "focusout",
          this.endInteraction
        );
      }

      document.addEventListener(
        "visibilitychange",
        this.handleVisibilityChange
      );

      this.reducedMotion.addEventListener(
        "change",
        this.handleReducedMotionChange
      );
    }

    observeChanges() {
      this.resizeObserver =
        new ResizeObserver(
          this.handleResize
        );

      this.resizeObserver.observe(
        this.container
      );

      this.mutationObserver =
        new MutationObserver(() => {
          this.refreshItems();
          this.updateInterface();
          this.restartAutoplay();
        });

      this.mutationObserver.observe(
        this.container,
        {
          childList: true,
          subtree: false
        }
      );
    }

    refreshItems() {
      this.items = this.getItems();
    }

    getItems() {
      return Array.from(
        this.container.querySelectorAll(
          this.getSettings(
            "selectors"
          ).item
        )
      );
    }

    getGap() {
      const styles =
        getComputedStyle(this.container);

      return (
        Number.parseFloat(
          styles.columnGap ||
            styles.gap
        ) || 0
      );
    }

    getItemStep() {
      const firstItem =
        this.getItems()[0];

      if (!firstItem) {
        return this.container.clientWidth;
      }

      return (
        firstItem
          .getBoundingClientRect()
          .width +
        this.getGap()
      );
    }

    getVisibleItemsCount() {
      const itemStep =
        this.getItemStep();

      return Math.max(
        1,
        Math.floor(
          (
            this.container.clientWidth +
            this.getGap()
          ) /
            itemStep
        )
      );
    }

    getScrollDistance() {
      const itemStep =
        this.getItemStep();

      if (
        this.config.scrollAmount ===
        "item"
      ) {
        return itemStep;
      }

      return (
        itemStep *
        this.getVisibleItemsCount()
      );
    }

    getMaximumScroll() {
      return Math.max(
        0,
        this.container.scrollWidth -
          this.container.clientWidth
      );
    }

    hasOverflow() {
      return this.getMaximumScroll() > 2;
    }

    getBehavior() {
      if (this.reducedMotion.matches) {
        return "auto";
      }

      return this.config.behavior ===
        "instant"
        ? "auto"
        : "smooth";
    }

    scrollByDirection(direction) {
      this.container.scrollBy({
        left:
          this.getScrollDistance() *
          direction,
        behavior: this.getBehavior()
      });
    }

    scrollToStart() {
      this.container.scrollTo({
        left: 0,
        behavior: this.getBehavior()
      });
    }

    scrollAutomatically() {
      if (!this.hasOverflow()) {
        this.stopAutoplay();
        return;
      }

      const maximumScroll =
        this.getMaximumScroll();

      const reachedEnd =
        this.container.scrollLeft >=
        maximumScroll - 2;

      if (!reachedEnd) {
        this.scrollByDirection(1);
        return;
      }

      if (
        this.config.endBehavior ===
        "stop"
      ) {
        this.stopAutoplay();
        return;
      }

      this.scrollToStart();
    }

    handlePrevious() {
      this.scrollByDirection(-1);
      this.restartAutoplay();
    }

    handleNext() {
      this.scrollByDirection(1);
      this.restartAutoplay();
    }

    handleKeydown(event) {
      if (event.key === "ArrowLeft") {
        event.preventDefault();
        this.handlePrevious();
      }

      if (event.key === "ArrowRight") {
        event.preventDefault();
        this.handleNext();
      }

      if (event.key === "Home") {
        event.preventDefault();
        this.scrollToStart();
      }

      if (event.key === "End") {
        event.preventDefault();

        this.container.scrollTo({
          left: this.getMaximumScroll(),
          behavior: this.getBehavior()
        });
      }
    }

    handleScroll() {
      if (this.scrollFrame) {
        return;
      }

      this.scrollFrame =
        window.requestAnimationFrame(
          () => {
            this.scrollFrame = null;
            this.updateInterface();
          }
        );
    }

    handleResize() {
      this.updateInterface();
    }

    handleVisibilityChange() {
      if (!document.hidden) {
        this.restartAutoplay();
      }
    }

    handleReducedMotionChange() {
      this.restartAutoplay();
    }

    updateInterface() {
      const maximumScroll =
        this.getMaximumScroll();

      const progress =
        maximumScroll > 0
          ? this.container.scrollLeft /
            maximumScroll
          : 1;

      const normalizedProgress =
        Math.min(
          1,
          Math.max(0, progress)
        );

      const previousButton =
        this.elements.$previousButton.get(0);

      const nextButton =
        this.elements.$nextButton.get(0);

      const progressElement =
        this.elements.$progress.get(0);

      const progressBar =
        this.elements.$progressBar.get(0);

      if (previousButton) {
        previousButton.disabled =
          this.container.scrollLeft <= 2;
      }

      if (nextButton) {
        nextButton.disabled =
          this.container.scrollLeft >=
          maximumScroll - 2;
      }

      if (
        this.config.progressType ===
        "thumb"
      ) {
        this.updateThumbProgress(
          progressElement,
          progressBar,
          normalizedProgress
        );
      } else if (progressBar) {
        progressBar.style.transform =
          `scaleX(${normalizedProgress})`;
      }

      if (progressElement) {
        progressElement.setAttribute(
          "aria-valuenow",
          String(
            Math.round(
              normalizedProgress * 100
            )
          )
        );
      }

      this.root.classList.toggle(
        "native-scroll-loop--no-overflow",
        maximumScroll <= 2
      );
    }

    updateThumbProgress(
      progressElement,
      progressBar,
      progress
    ) {
      if (
        !progressElement ||
        !progressBar
      ) {
        return;
      }

      const trackWidth =
        progressElement.clientWidth;

      const visibleRatio =
        this.container.scrollWidth > 0
          ? this.container.clientWidth /
            this.container.scrollWidth
          : 1;

      const minimumThumbWidth =
        Math.max(
          24,
          Number(
            this.config.minimumThumbWidth ||
              40
          )
        );

      const thumbWidth =
        Math.max(
          minimumThumbWidth,
          trackWidth * visibleRatio
        );

      const maximumThumbOffset =
        Math.max(
          0,
          trackWidth - thumbWidth
        );

      const thumbOffset =
        maximumThumbOffset * progress;

      progressElement.style.setProperty(
        "--native-loop-thumb-width",
        `${thumbWidth}px`
      );

      progressElement.style.setProperty(
        "--native-loop-thumb-offset",
        `${thumbOffset}px`
      );
    }

    beginInteraction() {
      this.isInteracting = true;

      window.clearTimeout(
        this.interactionTimer
      );
    }

    endInteraction() {
      window.clearTimeout(
        this.interactionTimer
      );

      const delay = Math.max(
        0,
        Number(
          this.config.resumeDelay ||
            1200
        )
      );

      this.interactionTimer =
        window.setTimeout(() => {
          this.isInteracting = false;
        }, delay);
    }

    startAutoplay() {
      this.stopAutoplay();

      if (
        !this.config.autoplay ||
        this.reducedMotion.matches ||
        !this.hasOverflow()
      ) {
        return;
      }

      const delay = Math.max(
        1500,
        Number(
          this.config.autoplayDelay ||
            5000
        )
      );

      this.autoplayTimer =
        window.setInterval(() => {
          if (
            this.isInteracting ||
            document.hidden
          ) {
            return;
          }

          this.scrollAutomatically();
        }, delay);
    }

    stopAutoplay() {
      if (!this.autoplayTimer) {
        return;
      }

      window.clearInterval(
        this.autoplayTimer
      );

      this.autoplayTimer = null;
    }

    restartAutoplay() {
      if (!this.config.autoplay) {
        return;
      }

      this.startAutoplay();
    }

    onDestroy() {
      this.stopAutoplay();

      if (this.scrollFrame) {
        window.cancelAnimationFrame(
          this.scrollFrame
        );
      }

      window.clearTimeout(
        this.interactionTimer
      );

      this.resizeObserver?.disconnect();
      this.mutationObserver?.disconnect();

      this.container?.removeEventListener(
        "scroll",
        this.handleScroll
      );

      this.container?.removeEventListener(
        "keydown",
        this.handleKeydown
      );

      this.elements.$previousButton
        .get(0)
        ?.removeEventListener(
          "click",
          this.handlePrevious
        );

      this.elements.$nextButton
        .get(0)
        ?.removeEventListener(
          "click",
          this.handleNext
        );

      this.root?.removeEventListener(
        "mouseenter",
        this.beginInteraction
      );

      this.root?.removeEventListener(
        "mouseleave",
        this.endInteraction
      );

      this.container?.removeEventListener(
        "pointerdown",
        this.beginInteraction
      );

      this.container?.removeEventListener(
        "pointerup",
        this.endInteraction
      );

      this.container?.removeEventListener(
        "pointercancel",
        this.endInteraction
      );

      this.container?.removeEventListener(
        "focusin",
        this.beginInteraction
      );

      this.container?.removeEventListener(
        "focusout",
        this.endInteraction
      );

      document.removeEventListener(
        "visibilitychange",
        this.handleVisibilityChange
      );

      this.reducedMotion.removeEventListener(
        "change",
        this.handleReducedMotionChange
      );

      super.onDestroy();
    }
  }

  const registerHandler =
    ($element) => {
      elementorFrontend
        .elementsHandler
        .addHandler(
          NativeScrollLoopHandler,
          {
            $element
          }
        );
    };

  window.addEventListener(
    "elementor/frontend/init",
    () => {
      elementorFrontend.hooks.addAction(
        "frontend/element_ready/loop-grid.default",
        registerHandler
      );
    }
  );
})();
```

### 10.1 JavaScript implementation notes

The implementation agent must not blindly copy the baseline.

It must verify:

- the actual frontend ready hook;
- whether the original Loop Grid uses another skin suffix;
- whether Elementor already registers its own handler;
- whether the handler should compose with existing handlers;
- whether the editor rerender destroys previous handlers correctly;
- official events for Taxonomy Filter and Load More;
- whether dynamic replacement occurs inside or above `.elementor-loop-container`;
- whether a MutationObserver must observe a wrapper instead;
- whether Elementor jQuery collections are available in the installed build;
- whether script loading can be conditional on widget settings.

## 11. Progress Behavior

### 11.1 Growing bar

Calculation:

```text
maximumScroll = scrollWidth - clientWidth
progress = scrollLeft / maximumScroll
```

Rendering:

```css
transform: scaleX(progress);
```

Do not continuously update `width`.

### 11.2 Scrollbar thumb

Calculate:

```text
visibleRatio = clientWidth / scrollWidth
thumbWidth = max(minimumThumbWidth, trackWidth × visibleRatio)
thumbOffset = progress × (trackWidth - thumbWidth)
```

The thumb communicates:

- how much content is currently visible;
- how far the user has scrolled;
- how much content remains.

## 12. Arrow Behavior

Calculate movement from actual rendered dimensions.

### One-item mode

```text
distance = item width + computed gap
```

### Visible-group mode

```text
distance = visible item count × (item width + computed gap)
```

Never hardcode movement in pixels.

Disable:

- previous button at the beginning;
- next button at the end.

Use a tolerance of approximately 2 pixels to avoid subpixel issues.

## 13. Autoplay Behavior

Autoplay must:

- be disabled by default;
- use the configured interval;
- not run when content does not overflow;
- respect reduced motion;
- pause during configured interactions;
- pause while the document is hidden;
- avoid fighting manual scroll;
- safely restart after dynamic updates;
- clean up timers on destroy;
- return to beginning or stop at end;
- never clone Loop Items.

Recommended minimum interval:

```text
1500 ms
```

Recommended default:

```text
5000 ms
```

## 14. Dynamic Elementor Updates

Support:

- Elementor editor rerender
- responsive control changes
- Taxonomy Filter
- Load More
- AJAX replacement when compatible
- multiple Loop Grid instances

Preferred order:

1. official Elementor or Loop Grid events;
2. existing handler lifecycle;
3. scoped `MutationObserver` fallback.

After updates:

- refresh item references;
- update scroll limits;
- update progress;
- update arrow disabled states;
- restart autoplay safely;
- avoid duplicated listeners;
- clamp invalid scroll positions.

## 15. Accessibility

Required:

- semantic native buttons;
- descriptive labels;
- keyboard controls;
- progressbar role and values;
- visible focus;
- disabled controls;
- reduced-motion support;
- no focus stealing;
- no cloned content;
- no forced scroll on page load.

Keyboard behavior:

```text
ArrowLeft: previous item/group
ArrowRight: next item/group
Home: beginning
End: final scroll position
```

## 16. Performance

Use:

- passive scroll listeners;
- `requestAnimationFrame` for frequent progress updates;
- `ResizeObserver`;
- scoped `MutationObserver`;
- clean event teardown;
- clean timer teardown;
- computed dimensions only when needed.

Avoid:

- global mutation observers;
- querying the entire document on every scroll;
- repeated synchronous read/write cycles;
- autoplay when there is no overflow;
- third-party dependencies.

## 17. Compatibility Behavior

When Elementor Pro is unavailable or unsupported:

- do not fatal;
- leave Elementor operational;
- show an admin notice;
- do not register unsupported hooks;
- document the detected limitation.

When pagination cannot safely coexist with horizontal scrolling:

- preserve original behavior when possible;
- otherwise show an editor notice;
- recommend Load More, no pagination, or a “View all” link;
- never silently break pagination.

## 18. Implementation Phases

### Phase 1 — Inspection

- inspect installed versions;
- inspect Loop Grid source;
- identify hooks and selectors;
- create `elementor-integration-notes.md`;
- do not edit plugin behavior yet.

### Phase 2 — Plugin scaffold

- bootstrap plugin;
- dependency checks;
- namespaces;
- asset registration;
- admin notices;
- README skeleton;
- uninstall file.

### Phase 3 — Control injection

- inject content controls;
- inject style controls;
- serialize frontend settings;
- leave original Loop Grid unchanged when disabled.

### Phase 4 — Markup integration

- add scoped wrapper state;
- add navigation;
- add progress;
- preserve original rendering;
- verify multiple instances.

### Phase 5 — Frontend handler

- initialize carousel;
- arrows;
- keyboard;
- progress;
- autoplay;
- cleanup;
- reduced motion;
- resize updates.

### Phase 6 — Dynamic updates

- editor rerender;
- Taxonomy Filter;
- Load More;
- AJAX updates;
- MutationObserver fallback.

### Phase 7 — QA and documentation

- syntax checks;
- lint;
- browser testing;
- compatibility notes;
- manual QA checklist;
- semantic commits.

## 19. Acceptance Criteria

The implementation is complete only when:

- the original Loop Grid works unchanged when disabled;
- no duplicate widget is registered;
- no Elementor file is modified;
- desktop arrows work;
- mobile touch dragging works;
- scroll snap works;
- arrow movement is dimension-based;
- arrow disabled states work;
- growing progress works;
- thumb progress works;
- autoplay works;
- autoplay pauses correctly;
- reduced motion is respected;
- multiple instances work;
- editor preview works;
- dynamic updates do not duplicate handlers;
- all observers and timers are cleaned up;
- no third-party carousel library is loaded;
- no browser console errors remain;
- documentation is complete.

## 20. Validation Commands

Use the repository's existing tooling when available.

At minimum:

```bash
find . -name "*.php" -print0 | xargs -0 -n1 php -l
```

When WordPress Coding Standards is configured:

```bash
vendor/bin/phpcs
```

When JavaScript linting is configured:

```bash
npm run lint
```

Also report:

- tested browser widths;
- editor tests;
- dynamic update tests;
- unresolved manual tests.

## 21. Recommended Semantic Commits

```text
feat: scaffold native scroll loop extension
feat: add loop grid carousel controls
feat: add loop grid render integration
feat: implement native carousel frontend behavior
style: add responsive carousel styling
docs: document installation and compatibility
test: add validation and manual qa checklist
```

## 22. Initial Codex Prompt

After adding this plan and `AGENTS.md`, use a prompt similar to:

```text
Read AGENTS.md and docs/implementation-plan.md in full.

Inspect the installed WordPress, Elementor, and Elementor Pro code before editing anything.

Validate whether the hook-based extension architecture is viable in the installed versions.

Do not implement the carousel yet.

Create docs/elementor-integration-notes.md containing:
- detected versions;
- Loop Grid widget class and widget name;
- control injection hooks;
- render hooks;
- frontend lifecycle hook;
- actual DOM selectors;
- editor rerender behavior;
- Taxonomy Filter and Load More integration points;
- compatibility risks;
- recommended final architecture.

Then present a concise implementation plan based on the installed source code.
```
