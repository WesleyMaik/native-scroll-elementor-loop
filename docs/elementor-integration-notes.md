# Elementor Integration Notes

## Scope and verdict

This document records the source-code inspection performed against the locally installed WordPress, Elementor, and PRO Elements copies. No third-party files were changed. The resulting hook-based carousel architecture was implemented in this plugin after these findings were approved.

The hook-based extension architecture is viable in the installed versions. The original `loop-grid` widget can remain responsible for its query, skins, Loop Item rendering, pagination, filters, editor preview, and existing assets. The extension can add controls through widget-specific control hooks, add state to the widget wrapper before render, filter only the rendered Loop Grid content to add navigation/progress markup, and attach an additional Elementor frontend handler for every supported Loop Grid skin.

## Detected versions

| Component | Detected version | Source |
| --- | --- | --- |
| WordPress | `7.0.2` (`pt_BR`) | `wp-includes/version.php` |
| Elementor | `4.0.7` | `wp-content/plugins/elementor/elementor.php` |
| PRO Elements | `4.0.4.2` | `wp-content/plugins/pro-elements/pro-elements.php` |
| Elementor Pro compatibility constant exposed by PRO Elements | `ELEMENTOR_PRO_VERSION = 4.0.4.2` | `wp-content/plugins/pro-elements/pro-elements.php` |

The installed premium implementation is PRO Elements, not the official `elementor-pro` plugin directory. It exposes the Elementor Pro namespaces and constants used by the Loop Builder code. Its plugin header says it was tested up to Elementor `4.0.4.2-ga`, while Elementor `4.0.7` is installed. The inspected integration points are present, but this patch-level mismatch must be covered by runtime QA.

## Loop Grid identity and inheritance

- Class: `ElementorPro\Modules\LoopBuilder\Widgets\Loop_Grid`.
- File: `wp-content/plugins/pro-elements/modules/loop-builder/widgets/loop-grid.php`.
- Widget name: `loop-grid`.
- Widget title: `Loop Grid`.
- Inheritance: `Loop_Grid` -> `ElementorPro\Modules\LoopBuilder\Widgets\Base` -> `ElementorPro\Modules\Posts\Widgets\Posts`.
- Registered Loop Grid skins: `post`, `product`, `post_taxonomy`, and `product_taxonomy`.
- Style dependency: `widget-loop-grid`.
- Script dependency inherited from `Posts_Base`: `imagesloaded`.
- The PRO frontend registers three handlers for each Loop Grid skin: Loop, Load More, and AJAX Pagination.

The Loop Grid reports `has_widget_inner_wrapper(): true`, so its rendered content is inside `.elementor-widget-container`.

## Control injection hooks

Elementor `4.0.7` still emits the widget-specific section hooks from `Controls_Stack`:

```text
elementor/element/{stack_name}/{section_id}/before_section_start
elementor/element/{stack_name}/{section_id}/after_section_start
elementor/element/{stack_name}/{section_id}/before_section_end
elementor/element/{stack_name}/{section_id}/after_section_end
```

For this widget, `{stack_name}` is `loop-grid`. Confirmed section IDs include:

- `section_layout`
- `section_query`
- `section_pagination`
- `section_additional_options`
- `section_design_layout`
- `section_nothing_found_message_design`

Recommended injection point:

```text
elementor/element/loop-grid/section_additional_options/after_section_end
```

At that point the extension can call `start_controls_section()` to add the independent `Native Scroll Carousel` content section. A defensive fallback can use `elementor/element/loop-grid/section_layout/after_section_end` if the additional-options section disappears in a future version.

The installed responsive controls are safe to reuse at render time:

- `columns` is responsive, frontend available, and writes `--grid-columns` on the widget wrapper.
- `column_gap` is responsive and writes `--grid-column-gap` on the widget wrapper.

The extension should therefore reuse those CSS custom properties instead of duplicating desktop/tablet column and gap controls. A dedicated responsive mobile card-width control is still justified when a partial next card is desired, because the original `columns` control only represents whole column counts.

## Render hooks and markup injection

Elementor `4.0.7` exposes these relevant render hooks:

- `elementor/frontend/before_render`
- `elementor/frontend/widget/before_render`
- `elementor/widget/before_render_content`
- `elementor/widget/render_content`
- `elementor/frontend/widget/after_render`
- `elementor/frontend/after_render`

Recommended responsibilities:

1. Use `elementor/frontend/widget/before_render` and return immediately unless `$widget->get_name() === 'loop-grid'` and the feature is enabled. Add only scoped wrapper classes/data attributes and enqueue registered assets there.
2. Use `elementor/widget/render_content` and return the original string unchanged unless the same checks pass. Add navigation/progress markup around the existing content string without parsing, replacing, or duplicating `.elementor-loop-container` or Loop Items.
3. Do not use `elementor/widget/before_render_content` for the controls UI. It fires before the inner `.elementor-widget-container` is printed, so emitted markup would sit outside that inner wrapper.
4. Do not rely on the generic after-render hook to place controls inside the widget; it fires after the outer widget wrapper closes.

`elementor/widget/render_content` is the least invasive installed hook for adding UI inside `.elementor-widget-container`: Elementor captures the skin output, applies this filter, and then prints the filtered content inside its own inner wrapper.

## Actual DOM selectors

The source produces the following stable structure for a populated Loop Grid:

```html
<div
  class="elementor-element elementor-element-{widget-id} elementor-widget elementor-widget-loop-grid ..."
  data-id="{widget-id}"
  data-element_type="widget"
  data-widget_type="loop-grid.{skin}"
>
  <div class="elementor-widget-container">
    <div class="elementor-loop-container elementor-grid" role="list">
      <div
        class="elementor elementor-{template-id} e-loop-item e-loop-item-{post-id} ..."
        data-elementor-type="loop-item"
        data-elementor-id="{template-id}"
      >
        <!-- Original Loop Item document -->
      </div>
    </div>
    <!-- Pagination, Load More anchor/button/message, or nothing-found markup -->
  </div>
</div>
```

Confirmed selectors and attributes:

- Widget wrapper: `.elementor-widget-loop-grid` or `[data-widget_type^="loop-grid."]`.
- Widget inner wrapper: `.elementor-widget-container`.
- Scroll target: `.elementor-loop-container`.
- Grid state class: `.elementor-grid`.
- Direct item selector: `.elementor-loop-container > .e-loop-item`.
- Loop Item document selector used by the existing Loop handler: `.elementor-loop-container .elementor`.
- Per-item class: `.e-loop-item-{post-id}`.
- Pagination: `.elementor-pagination` and `a.page-numbers`.
- Load More anchor: `.e-load-more-anchor[data-page][data-max-page][data-next-page]`.
- Load More button: `.e-loop__load-more .elementor-button`.
- Load More spinner: `.e-load-more-spinner`.
- Nothing-found wrapper: `.e-loop-nothing-found-message`.

Pagination and Load More are siblings after `.elementor-loop-container`, not children of it. Carousel CSS must target the loop container only and must not make the entire `.elementor-widget-container` horizontally scrollable.

## Frontend lifecycle hook

The actual ready hook is skin-specific. Elementor reads `data-widget_type` and emits:

```text
frontend/element_ready/loop-grid.post
frontend/element_ready/loop-grid.product
frontend/element_ready/loop-grid.post_taxonomy
frontend/element_ready/loop-grid.product_taxonomy
```

`frontend/element_ready/loop-grid.default` is not the correct primary hook for the installed Loop Grid because a registered skin supplies `_skin` and PRO Elements only attaches its own Loop Grid handlers for the four skin names above.

Recommended registration is performed after `elementor/frontend/init` with `elementorFrontend.elementsHandler.attachHandler('loop-grid', NativeScrollLoopHandler, skin)` once for each confirmed skin. This matches the installed PRO handler architecture and uses `elementorModules.frontend.handlers.Base`.

Elementor's handler manager destroys the previous instance with the same constructor ID before creating a new one when the wrapper has an editor `data-model-cid`. On the public frontend there is no model CID, so rerunning a ready trigger on the same wrapper does not automatically destroy an earlier third-party handler. The extension must maintain an instance registry keyed by the wrapper element (for example, a `WeakMap`) and explicitly destroy/replace its own prior instance before initialization. This prevents leaks after Taxonomy Filter and AJAX pagination refreshes.

## Editor rerender behavior

Elementor's editor view defers a call to `elementorFrontend.elementsHandler.runReadyTrigger(self.el)` after rendering an element. The ready trigger emits the global, element-type, and skin-specific widget hooks in sequence.

For editor widget rerenders:

- the same handler constructor ID is tracked against the wrapper's model CID;
- the existing handler receives `onDestroy()` before the replacement handler is created;
- the Loop handler additionally emits `editor/widgets/loop-grid/on-init` during editor initialization;
- Loop Item edit handles may be prepended inside the first matching Loop Item document;
- an empty template renders `.e-loop-empty-view__wrapper` instead of a loop container.

The extension should rely on the standard skin-specific ready hook for initialization, implement complete `onDestroy()` cleanup, and fail without side effects when `.elementor-loop-container` is absent. The PRO-specific `editor/widgets/loop-grid/on-init` action can be observed only if a future editor-only adjustment is required; it is not needed for the primary lifecycle.

For live preview, behavior and styles are enqueued ahead of widget AJAX rerenders through `elementor/preview/enqueue_scripts` and `elementor/preview/enqueue_styles`. Controls that change markup, serialized behavior, icons, or layout use `render_type: template`, causing Elementor to rebuild the widget and rerun the skin-specific ready lifecycle. Style controls continue to use Elementor selectors for immediate CSS updates. Layout selectors deliberately outrank Elementor's generated per-document grid selectors, while scoped button selectors isolate navigation from global kit and reset button rules.

`attachHandler()` only registers callbacks for future ready triggers; it does not initialize widgets whose ready trigger already ran. Because preview script timing can be later than the initial editor widget trigger, the frontend module also scans existing enabled Loop Grid wrappers once after handler registration and directly initializes only its own handler. Future editor rerenders continue through the official ready hook and the `WeakMap` teardown path.

The editor's client-side element view rebuilds the outer widget wrapper and does not preserve custom wrapper classes or `data-*` attributes added by PHP `before_render`. The render filter therefore emits a hidden, editor-safe configuration marker inside `.elementor-widget-container`, which is preserved. The handler reads this marker as a fallback and reconstructs its namespaced wrapper classes before initializing the scroller.

## Taxonomy Filter integration

The Taxonomy Filter widget uses:

- widget name `taxonomy-filter`;
- filter buttons `.e-filter-item` inside `.e-filter`;
- setting `selected_element` to target a Loop Grid widget ID;
- REST endpoint `elementor-pro/v1/refresh-loop`.

On a filter change, PRO Elements:

1. fetches refreshed Loop Grid markup;
2. replaces the target widget's entire `.elementor-widget-container`;
3. initializes handlers inside new `.e-loop-item` documents;
4. calls `elementorFrontend.elementsHandler.runReadyTrigger()` on the existing Loop Grid outer wrapper.

There is no dedicated public `after-filter` event in the inspected handler. The official ready trigger is therefore the primary integration point. Because the outer wrapper survives while its inner container is replaced, the extension's frontend registry must destroy the previous instance and bind to the new container. A scoped observer is only a fallback for unexpected markup changes, not the primary Taxonomy Filter mechanism.

The filter also removes `e-load-more-pagination-end` from the Loop Grid wrapper when a new filter is selected and resets the target widget's pagination query parameter.

## Load More and AJAX pagination integration

### Load More

The installed `LoopLoadMore` handler uses:

- container `.elementor-loop-container`;
- direct response items `.e-loop-item`;
- button `.e-loop__load-more .elementor-button`;
- anchor `.e-load-more-anchor`.

It appends new items directly to the existing loop container, initializes descendant element handlers, and triggers this jQuery window event:

```text
elementor-pro/loop-builder/after-insert-posts
```

The event does not include a widget ID. The extension should listen once per active handler, refresh only its own scoped container, and remove the listener during destroy. A direct-child `MutationObserver` on that same container is a defensive fallback and also covers append behavior if the event changes.

### AJAX pagination

For numbered/previous-next pagination with `pagination_load_type = ajax`, the installed handler:

1. fetches the target page;
2. replaces `.elementor-widget-container` under the existing widget wrapper;
3. calls `elementorFrontend.elementsHandler.runReadyTrigger()` on the widget wrapper;
4. initializes handlers in the returned `.e-loop-item` documents.

The same instance-replacement strategy used for Taxonomy Filter applies. The new carousel instance should start at the beginning after container replacement. Page-reload pagination needs no special frontend integration.

## Compatibility risks

1. **Version skew:** PRO Elements `4.0.4.2` declares testing through Elementor `4.0.4.2-ga`, but Elementor `4.0.7` is installed. The inspected APIs agree, yet editor and frontend runtime QA remains mandatory.
2. **Skin suffixes:** binding only `loop-grid.default` would miss all currently registered Loop Grid skins.
3. **Public-frontend handler replacement:** Elementor automatically destroys prior instances in the editor, but not necessarily when ready is rerun on an unchanged public wrapper. A plugin-owned registry and idempotent teardown are required.
4. **Masonry:** the existing Loop handler can run masonry when `masonry` is enabled. Masonry and a single-row horizontal auto-flow grid are conflicting layout models. Carousel mode should fail safe or show an editor warning when masonry is enabled rather than silently overriding it.
5. **Alternate templates and column span:** alternate Loop Items can span multiple grid columns. Horizontal item sizing must either support the span deliberately or document/guard the incompatible combination.
6. **Taxonomy Filter replacement:** the inner widget container is replaced wholesale, so cached DOM references become invalid.
7. **Load More event scope:** `elementor-pro/loop-builder/after-insert-posts` is global and carries no originating widget ID. Every reaction must remain scoped and cheap.
8. **AJAX pagination:** it replaces navigation/progress markup added through `elementor/widget/render_content`; ready-trigger reinitialization must recreate state without duplicate listeners.
9. **RTL scrolling:** browser `scrollLeft` semantics differ in RTL. Progress, limits, keyboard direction, and autoplay need normalized logical scroll calculations.
10. **Empty and no-overflow states:** editor empty views and no-results responses have no loop container. Initialization must be a no-op and controls must remain hidden.
11. **Internal selector stability:** `.elementor-loop-container` and `.e-loop-item` are confirmed in this installation but originate in PRO implementation details. Selector checks must fail safely and compatibility notes must be updated on upgrades.
12. **Style timing:** enqueueing a stylesheet only during widget rendering can occur after the page head has printed. The implementation therefore enqueues its small stylesheet through Elementor's frontend style lifecycle and keeps the behavior script conditional on an enabled Loop Grid.

## Recommended final architecture

1. **Bootstrap and compatibility layer**
   - Wait for Elementor and PRO Elements to load.
   - Validate `ELEMENTOR_VERSION`, `ELEMENTOR_PRO_VERSION`, and availability of the `loop-grid` widget.
   - Register assets on Elementor's frontend asset-registration hooks.
   - Fail without fatal errors and show an administrator notice only for actionable incompatibility.

2. **Controls service**
   - Inject the `Native Scroll Carousel` section through the confirmed widget-specific control hook.
   - Keep enablement off by default.
   - Reuse `--grid-columns` and `--grid-column-gap` from the original responsive controls.
   - Add only the dedicated responsive sizing controls that the existing widget cannot express, notably partial mobile card width.

3. **Render integration service**
   - On `elementor/frontend/widget/before_render`, add namespaced wrapper classes and a compact JSON configuration only when enabled.
   - On `elementor/widget/render_content`, prepend/append native button and progress markup while leaving the original Loop Grid content byte-for-byte intact in the middle.
   - Never parse or recreate Loop Item markup and never modify Elementor/PRO files.

4. **Frontend handler**
   - Extend `elementorModules.frontend.handlers.Base`.
   - Register for all four installed skin hooks through `attachHandler`.
   - Use a plugin-owned `WeakMap` to guarantee one live instance per widget wrapper on both editor and public frontend.
   - Scope all selectors to the current wrapper and use `.elementor-loop-container > .e-loop-item`.
   - Implement complete teardown for DOM listeners, jQuery window events, observers, timers, media-query listeners, and animation frames.

5. **Dynamic update adapter**
   - Treat the skin-specific ready trigger as authoritative for Taxonomy Filter and AJAX pagination replacement.
   - Treat `elementor-pro/loop-builder/after-insert-posts` as the primary Load More append signal.
   - Use `ResizeObserver` for geometry changes and a direct-child `MutationObserver` only as a scoped fallback.
   - Refresh items, limits, progress, disabled states, and autoplay after every update.

6. **Compatibility policy**
   - Guard masonry and unsupported alternate-template span combinations.
   - Normalize RTL logical scroll positions.
   - Preserve regular pagination/Load More markup outside the scroller.
   - Leave a disabled or unsupported Loop Grid completely unchanged.

## Source files inspected

- `wp-includes/version.php`
- `wp-content/plugins/elementor/elementor.php`
- `wp-content/plugins/elementor/includes/base/controls-stack.php`
- `wp-content/plugins/elementor/includes/base/element-base.php`
- `wp-content/plugins/elementor/includes/base/widget-base.php`
- `wp-content/plugins/elementor/assets/js/frontend.js`
- `wp-content/plugins/elementor/assets/js/editor.js`
- `wp-content/plugins/pro-elements/pro-elements.php`
- `wp-content/plugins/pro-elements/plugin.php`
- `wp-content/plugins/pro-elements/modules/loop-builder/module.php`
- `wp-content/plugins/pro-elements/modules/loop-builder/widgets/base.php`
- `wp-content/plugins/pro-elements/modules/loop-builder/widgets/loop-grid.php`
- `wp-content/plugins/pro-elements/modules/loop-builder/skins/skin-loop-base.php`
- `wp-content/plugins/pro-elements/modules/loop-builder/documents/loop.php`
- `wp-content/plugins/pro-elements/modules/loop-filter/module.php`
- `wp-content/plugins/pro-elements/modules/loop-filter/widgets/taxonomy-filter.php`
- `wp-content/plugins/pro-elements/modules/loop-filter/traits/taxonomy-filter-trait.php`
- `wp-content/plugins/pro-elements/assets/js/elements-handlers.js`
- `wp-content/plugins/pro-elements/assets/js/loop.8f668e18a5d491cc01b7.bundle.js`
- `wp-content/plugins/pro-elements/assets/js/load-more.862f17c31e360ff1934e.bundle.js`
- `wp-content/plugins/pro-elements/assets/js/ajax-pagination.dfa3a82618d618a6a6bf.bundle.js`
- `wp-content/plugins/pro-elements/assets/js/taxonomy-filter.77f346809c2657dd250a.bundle.js`
- `wp-content/plugins/pro-elements/assets/js/loop-filter-editor.e5be4d8fdcb9e22b57f7.bundle.js`
- `wp-content/plugins/pro-elements/assets/css/widget-loop-grid.min.css`
