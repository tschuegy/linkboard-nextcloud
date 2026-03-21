# Grafana-Style Grid Layout for Service Cards

**Date:** 2026-03-21
**Status:** Approved

## Summary

Replace the current CSS Grid layout with fixed column-span snapping (1-4 steps) inside CategoryGroups with `vue-grid-layout`, enabling free drag, resize, and positioning of Service Cards within categories. Categories themselves remain as structural containers.

## Requirements

1. **Free resize** — Cards are resizable in both width and height, not just width
2. **Free placement** — Cards can be placed anywhere in the grid, including with gaps between them
3. **Configurable grid granularity** — Users choose column count (6, 12, 24) per category; 0 = pixel-level (no snapping)
4. **Auto-compression toggle** — Per-category option: cards auto-compact (Grafana-style) or stay in place with gaps
5. **Edit mode** — Global toggle (lock/unlock icon in toolbar) to enable/disable drag and resize; prevents accidental changes
6. **Automatic migration** — Existing `_colSpan` data is converted to `_layout` on first load; no DB migration needed
7. **Category min-height** — Categories auto-grow based on content, with optional configurable minimum height

## Technology

- **Library:** `vue-grid-layout` (Vue 2 compatible, inspired by react-grid-layout)
- **No new backend changes** — Layout data stored in existing `widget_config` JSON field

## Data Model

### Per-Service: `widgetConfig._layout`

```json
{
  "_layout": { "x": 0, "y": 0, "w": 4, "h": 2 }
}
```

- `x, y` — Position in grid units (column, row)
- `w` — Width in grid units
- `h` — Height in grid units

### Per-Category: `_gridSettings` (stored in category config)

```json
{
  "_gridSettings": {
    "colCount": 12,
    "rowHeight": 80,
    "autoCompress": true,
    "minHeight": 0
  }
}
```

- `colCount` — Number of grid columns (6, 12, 24, or 0 for pixel-level)
- `rowHeight` — Pixel height of one grid row
- `autoCompress` — Whether cards auto-compact vertically
- `minHeight` — Minimum category height in grid rows (0 = auto only)

## Migration Strategy

Migration happens client-side in the Pinia store when services are loaded:

1. If a service has `_colSpan` but no `_layout`, convert:
   - `w = _colSpan * (colCount / 4)` — scale to new grid
   - `h = 2` — default height
   - `x, y` — assigned sequentially (left to right, top to bottom)
2. Remove `_colSpan` from config
3. Persist converted layout via API

**No PHP database migration required.** The existing `widget_config` JSON column stores the new `_layout` object alongside other config.

## Defaults

| Scenario | w | h |
|---|---|---|
| New card without widget (12-col grid) | 3 | 2 |
| New card with widget (12-col grid) | 6 | 3 |
| Category colCount | 12 | — |
| Category rowHeight | — | 80px |
| autoCompress | true | — |
| minHeight | 0 | — |

## Component Changes

### CategoryGroup.vue

- Replace `.category-group__grid` CSS Grid with `<grid-layout>` component
- Props from `_gridSettings`: `:col-num`, `:row-height`, `:is-draggable`, `:is-resizable`
- `verticalCompact` prop driven by `autoCompress` setting
- Each card wrapped in `<grid-item :x :y :w :h>`
- Handle `@layout-updated` event to batch-persist all card positions in the category
- SortableJS for card drag within category is removed (vue-grid-layout replaces it)

### ServiceCard.vue

- Remove resize logic entirely: `startResize`, `onMouseMove`, `Math.round` snapping
- Remove resize handle (`.service-card__resize-handle`) — vue-grid-layout provides its own
- Remove `effectiveColSpan` computed property
- Remove `cardGridStyle` method
- Card fills its `<grid-item>` container via `height: 100%`
- Widget content scrolls within fixed card height when needed

### DashboardView.vue

- Add edit mode toggle button (lock/unlock icon) in toolbar
- `editMode` boolean state, passed as prop to all CategoryGroups
- SortableJS for category row reordering remains unchanged

### CategoryEditor.vue

- New fields: column count (6/12/24), row height, auto-compression toggle, min-height
- Replaces the existing simple `columns` dropdown

### ServiceEditor.vue

- Remove `_colSpan` input field (resize is now visual in the grid)
- `_itemsPerRow` for widget inner content layout remains

## Styling & UX

### Edit Mode

- Toggle button: lock icon (view mode) / unlock icon (edit mode)
- Edit mode: dashed border around cards, cursor changes to `grab`
- Optional grid overlay as visual guide (semi-transparent lines)

### vue-grid-layout Theming

- Drag placeholder: semi-transparent block in accent color
- Resize handles: subtle, only visible in edit mode
- CSS transitions from vue-grid-layout adapted to LinkBoard theming

### Responsive

- vue-grid-layout `responsive` mode with breakpoints
- Screens < 768px: cards forced to full width, edit mode disabled
- Existing `min-width: 300px` logic for categories preserved

### Preserved Styles

- `card_style` (default/compact/minimal) unchanged
- `card_background` (glass/solid/flat/transparent) unchanged
- Widget content layout (`_itemsPerRow`, WidgetContainer/WidgetBlock) unchanged

## Data Flow

```
User drags/resizes card (edit mode)
  → vue-grid-layout emits @layout-updated with all positions
  → CategoryGroup handler maps positions back to services
  → Each service's widgetConfig._layout updated in store
  → Store batch-persists via API → DB (widget_config JSON)
```

## Out of Scope

- Cross-category card dragging (cards stay within their category)
- Backend/PHP changes (all layout logic is frontend-only)
- Changes to widget data fetching or widget rendering
- Changes to category row ordering (stays SortableJS)
