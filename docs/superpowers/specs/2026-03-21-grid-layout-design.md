# Grafana-Style Grid Layout for Service Cards

**Date:** 2026-03-21
**Status:** Approved

## Summary

Replace the current CSS Grid layout with fixed column-span snapping (1-4 steps) inside CategoryGroups with `vue-grid-layout`, enabling free drag, resize, and positioning of Service Cards within categories. Categories themselves remain as structural containers.

## Requirements

1. **Free resize** — Cards are resizable in both width and height, not just width
2. **Free placement** — Cards can be placed anywhere in the grid, including with gaps between them
3. **Configurable grid granularity** — Users choose column count (6, 12, 24) per category
4. **Auto-compression toggle** — Per-category option: cards auto-compact (Grafana-style) or stay in place with gaps
5. **Edit mode** — Global toggle (lock/unlock icon in toolbar) to enable/disable drag and resize; prevents accidental changes
6. **Automatic migration** — Existing `_colSpan` data is converted to `_layout` on first load; no DB migration needed
7. **Category min-height** — Categories auto-grow based on content, with optional configurable minimum height
8. **Cross-category move** — "Move to category" action in ServiceEditor replaces the current SortableJS cross-category drag

## Technology

- **Library:** `vue-grid-layout` (Vue 2 compatible, inspired by react-grid-layout)
- **Compatibility note:** `vue-grid-layout` v2.x targets Vue 2. If compatibility issues arise with Vue 2.7's Composition API shims, pin to a known-good version. Track Vue 3 migration separately.
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

### Per-Category: `_gridSettings` (stored in category `config` JSON column)

The existing `config` JSON column on categories (currently used only for `resources` type) stores the grid settings:

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

- `colCount` — Number of grid columns (6, 12, or 24)
- `rowHeight` — Pixel height of one grid row
- `autoCompress` — Whether cards auto-compact vertically
- `minHeight` — Minimum category height in grid rows (0 = auto only, useful as placeholder for empty categories when `autoCompress` is off)

The existing `columns` DB field on categories is migrated: its value (1-6) is mapped to the nearest `colCount` option (6→6, 1-2→6, 3-4→12, 5-6→24) during the client-side migration. After migration, `columns` is ignored.

## Migration Strategy

Migration happens client-side in the Pinia store when services are loaded:

1. **Category migration:** If a category has `columns` set but no `_gridSettings`, create `_gridSettings` with `colCount` derived from `columns` (1-2→6, 3-4→12, 5-6→24).

2. **Service migration:** If a service has `_colSpan` but no `_layout`, convert:
   - Determine effective old column count from the category's original `columns` value (default: 4 if unset)
   - `w = _colSpan * (colCount / effectiveOldColumns)` — scale proportionally
   - `h = 2` — default height
   - `x, y` — assigned using the packing algorithm below
3. Remove `_colSpan` from config, persist `_layout` via API

**Packing algorithm for sequential placement:**
```
currentX = 0, currentY = 0, rowMaxH = 0
for each service (in current sort order):
  if currentX + w > colCount:
    currentX = 0
    currentY += rowMaxH
    rowMaxH = 0
  assign x = currentX, y = currentY
  currentX += w
  rowMaxH = max(rowMaxH, h)
```

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
- Persist on `@moved` and `@resized` events (fired on drop, not during drag) with a 300ms debounce to batch rapid changes
- SortableJS for card drag within category is removed (vue-grid-layout replaces it)
- Re-call `vue-grid-layout` compact after category expand (collapse toggle) to handle hidden-container measurement issues

### ServiceCard.vue

- Remove resize logic entirely: `startResize`, `onMouseMove`, `Math.round` snapping
- Remove resize handle (`.service-card__resize-handle`) — vue-grid-layout provides its own
- Remove `effectiveColSpan` computed property
- Remove `cardGridStyle` method
- Card fills its `<grid-item>` container via `height: 100%`
- Widget content scrolls within fixed card height when needed (overflow-y: auto)

### DashboardView.vue

- The existing edit mode toggle (Pencil/"Edit"/"Done" button) is extended: in edit mode, vue-grid-layout drag/resize is enabled. The lock/unlock concept replaces the pencil icon but serves the same role — it is NOT a separate toggle.
- `editMode` boolean state, passed as prop to all CategoryGroups
- SortableJS for category row reordering remains unchanged

### CategoryEditor.vue

- New fields: column count (6/12/24), row height, auto-compression toggle, min-height
- Replaces the existing simple `columns` dropdown

### ServiceEditor.vue

- Remove `_colSpan` input field (resize is now visual in the grid)
- `_itemsPerRow` for widget inner content layout remains
- Add "Move to category" dropdown for cross-category card moves
- Widget type change watcher must preserve `_layout` alongside `_itemsPerRow`

## Styling & UX

### Edit Mode

- Toggle button: lock icon (view mode) / unlock icon (edit mode) — replaces existing pencil icon
- Edit mode: dashed border around cards, cursor changes to `grab`
- Optional grid overlay as visual guide (semi-transparent lines)

### vue-grid-layout Theming

- Drag placeholder: semi-transparent block in accent color
- Resize handles: subtle, only visible in edit mode
- CSS transitions from vue-grid-layout adapted to LinkBoard theming

### Responsive

- vue-grid-layout `responsive` mode with breakpoints:
  - `lg` (≥1200px): full `colCount` (e.g. 12)
  - `md` (≥996px): `colCount * 0.67` (e.g. 8)
  - `sm` (≥768px): `colCount * 0.5` (e.g. 6)
  - `xs` (<768px): 1 column, edit mode disabled
- Existing `min-width: 300px` logic for categories preserved

### Preserved Styles

- `card_style` (default/compact/minimal) unchanged
- `card_background` (glass/solid/flat/transparent) unchanged
- Widget content layout (`_itemsPerRow`, WidgetContainer/WidgetBlock) unchanged

## Data Flow

```
User drags/resizes card (edit mode)
  → vue-grid-layout emits @moved / @resized on drop
  → CategoryGroup handler (300ms debounce) maps positions back to services
  → Each service's widgetConfig._layout updated in store
  → Store batch-persists via API → DB (widget_config JSON)
```

## Out of Scope

- Cross-category card dragging via drag-and-drop (replaced by "Move to category" in ServiceEditor)
- Backend/PHP changes (all layout logic is frontend-only)
- Changes to widget data fetching or widget rendering
- Changes to category row ordering (stays SortableJS)
- Undo/reset layout (can be added later as enhancement)
