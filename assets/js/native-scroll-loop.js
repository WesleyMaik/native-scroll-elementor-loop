(function (globalObject, factory) {
  'use strict';

  const api = factory();

  if (typeof module === 'object' && module.exports) {
    module.exports = api;
  }

  if (globalObject) {
    globalObject.NativeScrollLoop = api;
    api.register(globalObject);
  }
})(typeof window === 'undefined' ? null : window, function () {
  'use strict';

  const instances = new WeakMap();
  const supportedSkins = ['post', 'product', 'post_taxonomy', 'product_taxonomy'];
  let rtlScrollType = null;
  let handlersAttached = false;

  function clamp(value, minimum, maximum) {
    return Math.min(maximum, Math.max(minimum, value));
  }

  function calculateProgress(position, maximum) {
    return maximum > 0 ? clamp(position / maximum, 0, 1) : 1;
  }

  function rawToLogical(rawPosition, maximum, isRtl, scrollType) {
    if (!isRtl) {
      return clamp(rawPosition, 0, maximum);
    }

    if (scrollType === 'negative') {
      return clamp(-rawPosition, 0, maximum);
    }

    if (scrollType === 'default') {
      return clamp(maximum - rawPosition, 0, maximum);
    }

    return clamp(rawPosition, 0, maximum);
  }

  function logicalToRaw(logicalPosition, maximum, isRtl, scrollType) {
    const position = clamp(logicalPosition, 0, maximum);

    if (!isRtl) {
      return position;
    }

    if (scrollType === 'negative') {
      return -position;
    }

    if (scrollType === 'default') {
      return maximum - position;
    }

    return position;
  }

  function getVisibleItemsCount(containerWidth, itemWidth, gap) {
    const step = itemWidth + gap;

    if (step <= 0) {
      return 1;
    }

    return Math.max(1, Math.floor((containerWidth + gap) / step));
  }

  function getAdvanceDistance(mode, containerWidth, itemWidth, gap) {
    const step = itemWidth + gap;

    if (mode !== 'group') {
      return step;
    }

    return step * getVisibleItemsCount(containerWidth, itemWidth, gap);
  }

  function calculateItemWidth(containerWidth, columns, gap) {
    const safeColumns = Math.max(1, Math.floor(Number(columns) || 1));
    const availableWidth = containerWidth - Math.max(0, gap) * (safeColumns - 1);

    return Math.max(0, availableWidth / safeColumns);
  }

  function getNavigationTarget(direction, current, maximum, distance, wrapAtEdges) {
    const safeMaximum = Math.max(0, maximum);
    const normalizedCurrent = clamp(current, 0, safeMaximum);

    if (wrapAtEdges && 0 < direction && normalizedCurrent >= safeMaximum - 2) {
      return 0;
    }

    if (wrapAtEdges && 0 > direction && normalizedCurrent <= 2) {
      return safeMaximum;
    }

    return clamp(normalizedCurrent + distance * direction, 0, safeMaximum);
  }

  function parseConfig(rawConfig) {
    if (typeof rawConfig !== 'string' || rawConfig === '') {
      return {};
    }

    try {
      const parsed = JSON.parse(rawConfig);
      return parsed && typeof parsed === 'object' && !Array.isArray(parsed) ? parsed : {};
    } catch (error) {
      return {};
    }
  }

  function readConfigFromRoot(root) {
    const wrapperConfig = root.getAttribute('data-native-scroll-settings');
    const configMarker = root.querySelector('[data-native-scroll-loop-config]');
    const markerConfig = configMarker?.getAttribute('data-native-scroll-settings') || '';

    return parseConfig(wrapperConfig || markerConfig);
  }

  function detectRtlScrollType(documentObject) {
    if (rtlScrollType) {
      return rtlScrollType;
    }

    const container = documentObject.createElement('div');
    const child = documentObject.createElement('div');

    container.dir = 'rtl';
    container.style.cssText = 'position:absolute;left:-10000px;width:4px;height:1px;overflow:scroll;visibility:hidden;';
    child.style.width = '8px';
    child.style.height = '1px';
    container.appendChild(child);
    documentObject.body.appendChild(container);

    if (container.scrollLeft > 0) {
      rtlScrollType = 'default';
    } else {
      container.scrollLeft = 1;
      rtlScrollType = container.scrollLeft === 0 ? 'negative' : 'reverse';
    }

    container.remove();
    return rtlScrollType;
  }

  function createHandlerClass(windowObject) {
    return class NativeScrollLoopHandler extends windowObject.elementorModules.frontend.handlers.Base {
      onInit(...args) {
        super.onInit(...args);

        this.root = this.$element && this.$element[0];

        if (!this.root) {
          return;
        }

        const previousInstance = instances.get(this.root);

        if (previousInstance && previousInstance !== this) {
          previousInstance.onDestroy();
        }

        this.config = readConfigFromRoot(this.root);
        this.container = this.root.querySelector('.elementor-loop-container');

        if (!this.config.enabled || !this.container) {
          return;
        }

        instances.set(this.root, this);
        this.windowObject = windowObject;
        this.documentObject = windowObject.document;
        this.previousButton = this.root.querySelector('[data-native-scroll-loop-previous]');
        this.nextButton = this.root.querySelector('[data-native-scroll-loop-next]');
        this.progressElement = this.root.querySelector('[data-native-scroll-loop-progress]');
        this.progressBar = this.root.querySelector('[data-native-scroll-loop-progress-bar]');
        this.pauseReasons = new Set();
        this.resumeTimers = new Map();
        this.items = [];
        this.scrollFrame = 0;
        this.autoplayTimer = 0;
        this.autoplayStoppedAtEnd = false;
        this.reducedMotion = windowObject.matchMedia('(prefers-reduced-motion: reduce)');
        this.isRtl = windowObject.getComputedStyle(this.container).direction === 'rtl';
        this.rtlType = this.isRtl ? detectRtlScrollType(this.documentObject) : 'default';
        this.originalTabindex = this.container.getAttribute('tabindex');
        this.originalAriaLabel = this.container.getAttribute('aria-label');
        this.eventNamespace = `.nativeScrollLoop-${this.getID()}`;
        this.bound = {
          scroll: this.handleScroll.bind(this),
          keydown: this.handleKeydown.bind(this),
          previous: this.handlePrevious.bind(this),
          next: this.handleNext.bind(this),
          pointerDown: () => this.pause('pointer'),
          pointerUp: () => this.resume('pointer', true),
          wheel: () => {
            this.pause('wheel');
            this.resume('wheel', true);
          },
          mouseEnter: () => this.pause('hover'),
          mouseLeave: () => this.resume('hover', true),
          focusIn: () => this.pause('focus'),
          focusOut: () => this.resume('focus', true),
          visibility: this.handleVisibilityChange.bind(this),
          reducedMotion: this.handleReducedMotionChange.bind(this),
          dynamicAppend: this.refresh.bind(this),
          resize: this.refresh.bind(this),
          mutation: this.refresh.bind(this),
        };

        this.prepareMarkup();
        this.bindNativeEvents();
        this.observeChanges();

        if (this.config.pauseWhenHidden && this.documentObject.hidden) {
          this.pauseReasons.add('visibility');
        }

        this.refresh();
      }

      prepareMarkup() {
        this.runtimeRootClasses = [
          'native-scroll-loop',
          `native-scroll-loop--${this.config.arrowPosition}`,
          `native-scroll-loop--progress-${this.config.progressMode}`,
        ];
        this.root.classList.add(...this.runtimeRootClasses);
        this.root.classList.add('native-scroll-loop--initialized');
        this.root.classList.toggle('native-scroll-loop--snap-enabled', Boolean(this.config.snapEnabled));
        this.root.classList.toggle('native-scroll-loop--snap-mandatory', this.config.snapStrictness === 'mandatory');
        this.root.classList.toggle('native-scroll-loop--snap-proximity', this.config.snapStrictness === 'proximity');
        this.root.classList.toggle('native-scroll-loop--snap-stop', Boolean(this.config.snapStop));
        this.root.classList.toggle('native-scroll-loop--hide-arrows-mobile', Boolean(this.config.hideArrowsMobile));
        this.container.classList.add('native-scroll-loop__scroller');
        this.container.setAttribute('tabindex', '0');
        this.container.setAttribute('aria-label', this.config.ariaLabel || 'Scrollable items');
      }

      bindNativeEvents() {
        this.container.addEventListener('scroll', this.bound.scroll, { passive: true });
        this.container.addEventListener('keydown', this.bound.keydown);
        this.previousButton?.addEventListener('click', this.bound.previous);
        this.nextButton?.addEventListener('click', this.bound.next);

        if (this.config.pauseOnInteraction) {
          this.container.addEventListener('pointerdown', this.bound.pointerDown, { passive: true });
          this.container.addEventListener('pointerup', this.bound.pointerUp, { passive: true });
          this.container.addEventListener('pointercancel', this.bound.pointerUp, { passive: true });
          this.container.addEventListener('wheel', this.bound.wheel, { passive: true });
        }

        if (this.config.pauseOnHover) {
          this.root.addEventListener('mouseenter', this.bound.mouseEnter);
          this.root.addEventListener('mouseleave', this.bound.mouseLeave);
        }

        if (this.config.pauseOnFocus) {
          this.root.addEventListener('focusin', this.bound.focusIn);
          this.root.addEventListener('focusout', this.bound.focusOut);
        }

        if (this.config.pauseWhenHidden) {
          this.documentObject.addEventListener('visibilitychange', this.bound.visibility);
        }

        if (typeof this.reducedMotion.addEventListener === 'function') {
          this.reducedMotion.addEventListener('change', this.bound.reducedMotion);
        }

        if (windowObject.jQuery) {
          windowObject.jQuery(windowObject).on(
            `elementor-pro/loop-builder/after-insert-posts${this.eventNamespace}`,
            this.bound.dynamicAppend
          );
        }
      }

      observeChanges() {
        if (typeof this.windowObject.ResizeObserver === 'function') {
          this.resizeObserver = new this.windowObject.ResizeObserver(this.bound.resize);
          this.resizeObserver.observe(this.container);
        }

        if (typeof this.windowObject.MutationObserver === 'function') {
          this.mutationObserver = new this.windowObject.MutationObserver(this.bound.mutation);
          this.mutationObserver.observe(this.container, { childList: true, subtree: false });
        }
      }

      refresh() {
        if (!this.container) {
          return;
        }

        this.updateLayout();
        this.items = Array.from(this.container.querySelectorAll(':scope > .e-loop-item'));
        this.autoplayStoppedAtEnd = false;
        const maximum = this.getMaximumScroll();
        const current = this.getLogicalScroll();

        if (current > maximum) {
          this.scrollToLogical(maximum, 'auto');
        }

        this.updateInterface();
        this.scheduleAutoplay();
      }

      updateLayout() {
        const rootStyles = this.windowObject.getComputedStyle(this.root);
        const configuredWidth = rootStyles.getPropertyValue('--native-scroll-loop-item-width').trim();

        if (configuredWidth) {
          this.root.style.removeProperty('--native-scroll-loop-computed-item-width');
          return;
        }

        const containerStyles = this.windowObject.getComputedStyle(this.container);
        const columns = Number.parseFloat(
          rootStyles.getPropertyValue('--grid-columns')
          || containerStyles.getPropertyValue('--grid-columns')
        ) || 1;
        const itemWidth = calculateItemWidth(this.container.clientWidth, columns, this.getGap());
        this.root.style.setProperty('--native-scroll-loop-computed-item-width', `${itemWidth}px`);
      }

      getMaximumScroll() {
        return Math.max(0, this.container.scrollWidth - this.container.clientWidth);
      }

      getLogicalScroll() {
        return rawToLogical(
          this.container.scrollLeft,
          this.getMaximumScroll(),
          this.isRtl,
          this.rtlType
        );
      }

      getGap() {
        const styles = this.windowObject.getComputedStyle(this.container);
        return Number.parseFloat(styles.columnGap || styles.gap) || 0;
      }

      getFirstItemWidth() {
        const firstItem = this.items[0];
        return firstItem ? firstItem.getBoundingClientRect().width : this.container.clientWidth;
      }

      getScrollDistance() {
        return getAdvanceDistance(
          this.config.arrowAdvance,
          this.container.clientWidth,
          this.getFirstItemWidth(),
          this.getGap()
        );
      }

      getScrollBehavior() {
        return this.reducedMotion.matches || this.config.scrollBehavior === 'instant' ? 'auto' : 'smooth';
      }

      scrollToLogical(position, behavior = this.getScrollBehavior()) {
        const maximum = this.getMaximumScroll();
        const rawPosition = logicalToRaw(position, maximum, this.isRtl, this.rtlType);
        this.container.scrollTo({ left: rawPosition, behavior });
      }

      scrollByDirection(direction) {
        const target = this.getLogicalScroll() + this.getScrollDistance() * direction;
        this.scrollToLogical(target);
      }

      navigateManually(direction) {
        const target = getNavigationTarget(
          direction,
          this.getLogicalScroll(),
          this.getMaximumScroll(),
          this.getScrollDistance(),
          !this.config.disableUnavailableArrows
        );

        this.scrollToLogical(target);
      }

      handlePrevious() {
        this.autoplayStoppedAtEnd = false;
        this.navigateManually(-1);
        this.scheduleAutoplay();
      }

      handleNext() {
        this.autoplayStoppedAtEnd = false;
        this.navigateManually(1);
        this.scheduleAutoplay();
      }

      handleKeydown(event) {
        const actions = {
          ArrowLeft: () => this.handlePrevious(),
          ArrowRight: () => this.handleNext(),
          Home: () => this.scrollToLogical(0),
          End: () => this.scrollToLogical(this.getMaximumScroll()),
        };

        if (!actions[event.key]) {
          return;
        }

        event.preventDefault();
        actions[event.key]();
      }

      handleScroll() {
        if (this.scrollFrame) {
          return;
        }

        this.scrollFrame = this.windowObject.requestAnimationFrame(() => {
          this.scrollFrame = 0;
          this.updateInterface();
        });
      }

      updateInterface() {
        const maximum = this.getMaximumScroll();
        const position = this.getLogicalScroll();
        const progress = calculateProgress(position, maximum);
        const hasOverflow = maximum > 2;
        const atStart = position <= 2;
        const atEnd = position >= maximum - 2;

        this.root.classList.toggle('native-scroll-loop--no-overflow', !hasOverflow);
        this.updateButton(this.previousButton, !hasOverflow || atStart);
        this.updateButton(this.nextButton, !hasOverflow || atEnd);
        this.updateProgress(progress);

        if (!hasOverflow) {
          this.stopAutoplay();
        }
      }

      updateButton(button, unavailable) {
        if (!button) {
          return;
        }

        if (this.config.disableUnavailableArrows) {
          button.setAttribute('aria-disabled', String(unavailable));
          button.disabled = unavailable;
        } else {
          button.removeAttribute('aria-disabled');
          button.disabled = false;
        }
      }

      updateProgress(progress) {
        if (!this.progressElement || !this.progressBar) {
          return;
        }

        this.progressElement.setAttribute('aria-valuenow', String(Math.round(progress * 100)));

        if (this.config.progressMode !== 'thumb') {
          this.progressBar.style.transform = `scaleX(${progress})`;
          return;
        }

        const trackWidth = this.progressElement.clientWidth;
        const minimumWidth = Number.parseFloat(
          this.windowObject.getComputedStyle(this.root).getPropertyValue('--native-scroll-loop-thumb-min-width')
        ) || 40;
        const visibleRatio = this.container.scrollWidth > 0
          ? this.container.clientWidth / this.container.scrollWidth
          : 1;
        const thumbWidth = Math.min(trackWidth, Math.max(minimumWidth, trackWidth * visibleRatio));
        const offset = Math.max(0, trackWidth - thumbWidth) * progress;

        this.progressElement.style.setProperty('--native-scroll-loop-thumb-width', `${thumbWidth}px`);
        this.progressElement.style.setProperty('--native-scroll-loop-thumb-offset', `${offset}px`);
      }

      pause(reason) {
        this.pauseReasons.add(reason);
        this.clearResumeTimer(reason);
        this.stopAutoplay();
      }

      resume(reason, delayed = false) {
        this.clearResumeTimer(reason);

        if (!delayed) {
          this.pauseReasons.delete(reason);
          this.scheduleAutoplay();
          return;
        }

        const timer = this.windowObject.setTimeout(() => {
          this.resumeTimers.delete(reason);
          this.pauseReasons.delete(reason);
          this.scheduleAutoplay();
        }, Math.max(0, Number(this.config.resumeDelay) || 0));

        this.resumeTimers.set(reason, timer);
      }

      clearResumeTimer(reason) {
        const timer = this.resumeTimers.get(reason);

        if (timer) {
          this.windowObject.clearTimeout(timer);
          this.resumeTimers.delete(reason);
        }
      }

      handleVisibilityChange() {
        if (this.documentObject.hidden) {
          this.pause('visibility');
        } else {
          this.resume('visibility');
        }
      }

      handleReducedMotionChange() {
        if (this.reducedMotion.matches) {
          this.pause('reduced-motion');
        } else {
          this.resume('reduced-motion');
        }
      }

      scheduleAutoplay() {
        this.stopAutoplay();

        if (
          !this.config.autoplay
          || this.reducedMotion.matches
          || this.pauseReasons.size > 0
          || this.autoplayStoppedAtEnd
          || this.getMaximumScroll() <= 2
        ) {
          return;
        }

        this.autoplayTimer = this.windowObject.setTimeout(() => {
          this.autoplayTimer = 0;
          this.runAutoplayStep();
          this.scheduleAutoplay();
        }, Math.max(1500, Number(this.config.autoplayInterval) || 5000));
      }

      runAutoplayStep() {
        const maximum = this.getMaximumScroll();

        if (this.getLogicalScroll() < maximum - 2) {
          this.scrollByDirection(1);
          return;
        }

        if (this.config.autoplayEndBehavior === 'stop') {
          this.autoplayStoppedAtEnd = true;
          return;
        }

        this.scrollToLogical(0);
      }

      stopAutoplay() {
        if (!this.autoplayTimer) {
          return;
        }

        this.windowObject.clearTimeout(this.autoplayTimer);
        this.autoplayTimer = 0;
      }

      onDestroy() {
        this.stopAutoplay();

        if (this.scrollFrame && this.windowObject) {
          this.windowObject.cancelAnimationFrame(this.scrollFrame);
          this.scrollFrame = 0;
        }

        if (this.resumeTimers && this.windowObject) {
          this.resumeTimers.forEach((timer) => this.windowObject.clearTimeout(timer));
          this.resumeTimers.clear();
        }

        this.resizeObserver?.disconnect();
        this.mutationObserver?.disconnect();

        if (this.container && this.bound) {
          this.container.removeEventListener('scroll', this.bound.scroll);
          this.container.removeEventListener('keydown', this.bound.keydown);
          this.container.removeEventListener('pointerdown', this.bound.pointerDown);
          this.container.removeEventListener('pointerup', this.bound.pointerUp);
          this.container.removeEventListener('pointercancel', this.bound.pointerUp);
          this.container.removeEventListener('wheel', this.bound.wheel);
        }

        if (this.bound) {
          this.previousButton?.removeEventListener('click', this.bound.previous);
          this.nextButton?.removeEventListener('click', this.bound.next);
        }

        if (this.root && this.bound) {
          this.root.removeEventListener('mouseenter', this.bound.mouseEnter);
          this.root.removeEventListener('mouseleave', this.bound.mouseLeave);
          this.root.removeEventListener('focusin', this.bound.focusIn);
          this.root.removeEventListener('focusout', this.bound.focusOut);
        }

        if (this.documentObject && this.bound) {
          this.documentObject.removeEventListener('visibilitychange', this.bound.visibility);
        }

        if (this.reducedMotion && this.bound && typeof this.reducedMotion.removeEventListener === 'function') {
          this.reducedMotion.removeEventListener('change', this.bound.reducedMotion);
        }

        if (this.windowObject?.jQuery && this.eventNamespace) {
          this.windowObject.jQuery(this.windowObject).off(this.eventNamespace);
        }

        if (this.root && instances.get(this.root) === this) {
          instances.delete(this.root);
        }

        if (this.root) {
          this.root.style.removeProperty('--native-scroll-loop-computed-item-width');
          this.root.classList.remove(...(this.runtimeRootClasses || []));
          this.root.classList.remove(
            'native-scroll-loop--initialized',
            'native-scroll-loop--snap-enabled',
            'native-scroll-loop--snap-mandatory',
            'native-scroll-loop--snap-proximity',
            'native-scroll-loop--snap-stop',
            'native-scroll-loop--hide-arrows-mobile',
            'native-scroll-loop--no-overflow'
          );
        }

        if (this.container) {
          this.container.classList.remove('native-scroll-loop__scroller');

          if (this.originalTabindex === null) {
            this.container.removeAttribute('tabindex');
          } else {
            this.container.setAttribute('tabindex', this.originalTabindex);
          }

          if (this.originalAriaLabel === null) {
            this.container.removeAttribute('aria-label');
          } else {
            this.container.setAttribute('aria-label', this.originalAriaLabel);
          }
        }

        super.onDestroy();
      }
    };
  }

  function initializeExistingWidgets(windowObject, Handler) {
    if (!windowObject.document || typeof windowObject.jQuery !== 'function') {
      return 0;
    }

    const wrappers = windowObject.document.querySelectorAll('[data-widget_type^="loop-grid."]');
    let initializedCount = 0;

    wrappers.forEach((wrapper) => {
      if (instances.has(wrapper)) {
        return;
      }

      const elementName = wrapper.getAttribute('data-widget_type') || '';
      const skin = elementName.split('.')[1] || '';

      if (!supportedSkins.includes(skin)) {
        return;
      }

      new Handler({
        $element: windowObject.jQuery(wrapper),
        elementName,
      });
      initializedCount += 1;
    });

    return initializedCount;
  }

  function attachHandlers(windowObject) {
    if (
      handlersAttached
      || !windowObject.elementorFrontend?.elementsHandler
      || !windowObject.elementorModules?.frontend?.handlers?.Base
    ) {
      return;
    }

    handlersAttached = true;
    const Handler = createHandlerClass(windowObject);

    supportedSkins.forEach((skin) => {
      windowObject.elementorFrontend.elementsHandler.attachHandler('loop-grid', Handler, skin);
    });

    initializeExistingWidgets(windowObject, Handler);
  }

  function register(windowObject) {
    if (!windowObject || windowObject.__nativeScrollLoopRegistered) {
      return;
    }

    windowObject.__nativeScrollLoopRegistered = true;
    windowObject.addEventListener('elementor/frontend/init', () => attachHandlers(windowObject));
    attachHandlers(windowObject);
  }

  return {
    calculateItemWidth,
    calculateProgress,
    clamp,
    getAdvanceDistance,
    getNavigationTarget,
    getVisibleItemsCount,
    initializeExistingWidgets,
    logicalToRaw,
    parseConfig,
    rawToLogical,
    readConfigFromRoot,
    register,
  };
});
