# Grafana-Style Grid Layout Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace CSS Grid fixed-snap resizing with vue-grid-layout for free drag/resize/placement of Service Cards within categories.

**Architecture:** Install `vue-grid-layout` as dependency. Add a migration utility that converts old `_colSpan` data to `_layout` objects. Replace the CSS Grid in CategoryGroup with `<grid-layout>` + `<grid-item>` wrappers. Remove resize logic from ServiceCard. Extend CategoryEditor with grid settings. Replace pencil edit-mode icon with lock/unlock.

**Tech Stack:** Vue 2.7, vue-grid-layout v2.x, Pinia, Nextcloud Vue components

**Spec:** `docs/superpowers/specs/2026-03-21-grid-layout-design.md`

---

## File Structure

| File | Action | Responsibility |
|------|--------|----------------|
| `src/utils/gridMigration.js` | Create | Migration logic: `_colSpan` → `_layout`, category `columns` → `_gridSettings` |
| `src/components/Dashboard/CategoryGroup.vue` | Modify | Replace CSS Grid with `<grid-layout>`, handle layout events |
| `src/components/Dashboard/ServiceCard.vue` | Modify | Remove resize logic and drag handle, fill grid-item container |
| `src/components/Dashboard/DashboardView.vue` | Modify | Replace pencil icon with lock/unlock toggle, remove `@service-moved` |
| `src/components/Editor/CategoryEditor.vue` | Modify | Add grid settings fields (colCount, rowHeight, autoCompress, minHeight) |
| `src/components/Editor/ServiceEditor.vue` | Modify | Remove `_colSpan`, add "Move to category", preserve `_layout` |
| `src/store/dashboard.js` | Modify | Call migration on load, add batch layout update action |

---

### Task 1: Install vue-grid-layout

**Files:**
- Modify: `package.json`

- [ ] **Step 1: Install the dependency**

```bash
cd /var/www/nextcloud/apps/linkboard && npm install vue-grid-layout@2.4.0 --save
```

- [ ] **Step 2: Verify it installed correctly**

```bash
cd /var/www/nextcloud/apps/linkboard && node -e "require('vue-grid-layout'); console.log('OK')"
```

Expected: `OK`

- [ ] **Step 3: Build to check for import issues**

```bash
cd /var/www/nextcloud/apps/linkboard && npm run build
```

Expected: Build succeeds without errors.

- [ ] **Step 4: Commit**

```bash
cd /var/www/nextcloud/apps/linkboard
git add package.json package-lock.json
git commit -m "feat: add vue-grid-layout dependency for grid layout system"
```

---

### Task 2: Create grid migration utility

**Files:**
- Create: `src/utils/gridMigration.js`

- [ ] **Step 1: Create the migration utility**

Create `src/utils/gridMigration.js`:

```javascript
/**
 * Grid layout migration utility.
 * Converts legacy _colSpan data to _layout objects for vue-grid-layout.
 */

var DEFAULT_COL_COUNT = 12
var DEFAULT_ROW_HEIGHT = 80
var DEFAULT_H = 2
var DEFAULT_W_NO_WIDGET = 3
var DEFAULT_W_WIDGET = 6
var DEFAULT_H_WIDGET = 3

/**
 * Map old category columns (1-6) to new colCount (6, 12, 24).
 */
function mapColumnsToColCount(columns) {
	if (!columns || columns <= 2) return 6
	if (columns <= 4) return 12
	return 24
}

/**
 * Get default grid settings for a category.
 */
function getDefaultGridSettings() {
	return {
		colCount: DEFAULT_COL_COUNT,
		rowHeight: DEFAULT_ROW_HEIGHT,
		autoCompress: true,
		minHeight: 0,
	}
}

/**
 * Migrate a category's columns field to _gridSettings.
 * Returns the new config object, or null if no migration needed.
 */
function migrateCategory(category) {
	var config = category.config || {}
	if (typeof config === 'string') {
		try { config = JSON.parse(config) } catch (e) { config = {} }
	}
	if (config._gridSettings) return null // already migrated

	var gridSettings = getDefaultGridSettings()
	if (category.columns) {
		gridSettings.colCount = mapColumnsToColCount(category.columns)
	}
	config._gridSettings = gridSettings
	return config
}

/**
 * Determine the effective width for a service being migrated.
 */
function getEffectiveWidth(service, colCount, effectiveOldColumns) {
	var cfg = service.widgetConfig || {}
	var colSpan = cfg._colSpan ? parseInt(cfg._colSpan) : null

	if (colSpan) {
		return Math.max(1, Math.min(colCount, Math.round(colSpan * (colCount / effectiveOldColumns))))
	}
	if (service.widgetType === 'resources' || cfg._wide) {
		return Math.round(2 * (colCount / effectiveOldColumns))
	}
	if (service.widgetType) {
		return DEFAULT_W_WIDGET
	}
	return DEFAULT_W_NO_WIDGET
}

/**
 * Migrate services within a category from _colSpan to _layout.
 * Uses a packing algorithm to assign x, y positions sequentially.
 * Returns array of { serviceId, widgetConfig } for services that need updating, or empty array.
 */
function migrateServices(services, colCount, effectiveOldColumns) {
	var updates = []
	var currentX = 0
	var currentY = 0
	var rowMaxH = 0

	for (var i = 0; i < services.length; i++) {
		var service = services[i]
		var cfg = service.widgetConfig || {}
		if (typeof cfg === 'string') {
			try { cfg = JSON.parse(cfg) } catch (e) { cfg = {} }
		}

		// Skip if already migrated
		if (cfg._layout) continue

		// Only migrate if there's something to migrate (_colSpan, _wide, or just assign defaults)
		var w = getEffectiveWidth(service, colCount, effectiveOldColumns)
		var h = service.widgetType ? DEFAULT_H_WIDGET : DEFAULT_H

		// Pack into grid
		if (currentX + w > colCount) {
			currentX = 0
			currentY += rowMaxH
			rowMaxH = 0
		}

		var newCfg = Object.assign({}, cfg)
		newCfg._layout = { x: currentX, y: currentY, w: w, h: h }
		delete newCfg._colSpan
		delete newCfg._wide

		updates.push({
			serviceId: service.id,
			widgetConfig: newCfg,
		})

		currentX += w
		rowMaxH = Math.max(rowMaxH, h)
	}

	return updates
}

/**
 * Assign a default layout position for a new service.
 * Finds the first available position after existing services.
 */
function assignDefaultLayout(existingServices, hasWidget, colCount) {
	colCount = colCount || DEFAULT_COL_COUNT
	var w = hasWidget ? DEFAULT_W_WIDGET : DEFAULT_W_NO_WIDGET
	var h = hasWidget ? DEFAULT_H_WIDGET : DEFAULT_H

	// Find max y + h from existing layouts
	var maxBottom = 0
	for (var i = 0; i < existingServices.length; i++) {
		var layout = (existingServices[i].widgetConfig || {})._layout
		if (layout) {
			var bottom = layout.y + layout.h
			if (bottom > maxBottom) maxBottom = bottom
		}
	}

	return { x: 0, y: maxBottom, w: w, h: h }
}

export {
	mapColumnsToColCount,
	getDefaultGridSettings,
	migrateCategory,
	migrateServices,
	assignDefaultLayout,
	DEFAULT_COL_COUNT,
	DEFAULT_ROW_HEIGHT,
}
```

- [ ] **Step 2: Build to verify no syntax errors**

```bash
cd /var/www/nextcloud/apps/linkboard && npm run build
```

Expected: Build succeeds.

- [ ] **Step 3: Commit**

```bash
cd /var/www/nextcloud/apps/linkboard
git add src/utils/gridMigration.js
git commit -m "feat: add grid migration utility for _colSpan to _layout conversion"
```

---

### Task 3: Integrate migration into Pinia store

**Files:**
- Modify: `src/store/dashboard.js` (import migration, call on load, add batch layout update action)

- [ ] **Step 1: Add import at top of store file**

At the top of `src/store/dashboard.js`, after existing imports, add:

```javascript
import { migrateCategory, migrateServices, assignDefaultLayout } from '../utils/gridMigration.js'
```

- [ ] **Step 2: Add migration call in the `fetchDashboard` action**

In the `fetchDashboard` action, after the line `this.categories = data.categories || []` (the assignment at line 134), add the migration call:

```javascript
				this.categories = data.categories || []

				// Migrate legacy grid data to vue-grid-layout format
				this.migrateGridLayouts()
```

- [ ] **Step 3: Add migrateGridLayouts and batchUpdateLayouts actions**

After the `toggleEditMode` action (the one at line 449 that sets `this.editMode`), add these two new actions:

```javascript

		/**
		 * Migrate legacy _colSpan / columns data to _layout / _gridSettings.
		 * Runs once on load, persists changes via API.
		 */
		async migrateGridLayouts() {
			var self = this
			var categoryUpdates = []
			var serviceUpdates = []

			forEachCategory(this.categories, function(cat) {
				// Migrate category grid settings
				var newConfig = migrateCategory(cat)
				if (newConfig) {
					categoryUpdates.push({ id: cat.id, config: newConfig })
					cat.config = newConfig
				}

				// Migrate service layouts
				var config = cat.config || {}
				if (typeof config === 'string') {
					try { config = JSON.parse(config) } catch (e) { config = {} }
				}
				var gridSettings = (config._gridSettings) || { colCount: 12 }
				var effectiveOldColumns = cat.columns || 4
				var updates = migrateServices(cat.services || [], gridSettings.colCount, effectiveOldColumns)

				for (var i = 0; i < updates.length; i++) {
					serviceUpdates.push(updates[i])
					// Update in-memory immediately
					var svc = (cat.services || []).find(function(s) { return s.id === updates[i].serviceId })
					if (svc) {
						svc.widgetConfig = updates[i].widgetConfig
					}
				}
			})

			// Persist category config changes
			for (var i = 0; i < categoryUpdates.length; i++) {
				try {
					await categoryApi.update(categoryUpdates[i].id, {
						config: JSON.stringify(categoryUpdates[i].config),
					})
				} catch (err) {
					console.error('LinkBoard: Failed to migrate category grid settings', categoryUpdates[i].id, err)
				}
			}

			// Persist service layout changes
			for (var j = 0; j < serviceUpdates.length; j++) {
				try {
					await serviceApi.update(serviceUpdates[j].serviceId, {
						widgetConfig: serviceUpdates[j].widgetConfig,
					})
				} catch (err) {
					console.error('LinkBoard: Failed to migrate service layout', serviceUpdates[j].serviceId, err)
				}
			}
		},

		/**
		 * Batch update layouts for all services in a category.
		 * Called by CategoryGroup on @layout-updated event.
		 */
		async batchUpdateLayouts(categoryId, layoutMap) {
			// layoutMap: { serviceId: { x, y, w, h }, ... }
			var self = this
			var cat = null
			forEachCategory(this.categories, function(c) {
				if (c.id === categoryId) cat = c
			})
			if (!cat) return

			var promises = []
			var serviceIds = Object.keys(layoutMap)
			for (var i = 0; i < serviceIds.length; i++) {
				var serviceId = parseInt(serviceIds[i])
				var newLayout = layoutMap[serviceId]
				var svc = (cat.services || []).find(function(s) { return s.id === serviceId })
				if (!svc) continue

				var cfg = Object.assign({}, svc.widgetConfig || {})
				cfg._layout = newLayout
				svc.widgetConfig = cfg

				promises.push(serviceApi.update(serviceId, {
					widgetType: svc.widgetType || '',
					widgetConfig: cfg,
				}))
			}

			try {
				await Promise.all(promises)
			} catch (err) {
				console.error('LinkBoard: Failed to batch update layouts', err)
				self.error = t('linkboard', 'Failed to update layout')
			}
		},
```

- [ ] **Step 4: Build and verify**

```bash
cd /var/www/nextcloud/apps/linkboard && npm run build
```

Expected: Build succeeds.

- [ ] **Step 5: Commit**

```bash
cd /var/www/nextcloud/apps/linkboard
git add src/store/dashboard.js
git commit -m "feat: integrate grid migration into store with batch layout update"
```

---

### Task 4: Replace CSS Grid with vue-grid-layout in CategoryGroup

**Files:**
- Modify: `src/components/Dashboard/CategoryGroup.vue`

This is the largest task. It replaces the grid template, removes SortableJS for cards, and wires up vue-grid-layout.

**vue-grid-layout API pattern (verified from docs):**
- `:layout.sync="layout"` on `<grid-layout>` — layout is a data array of `{i, x, y, w, h}` objects
- `<grid-item v-for="item in layout" :i="item.i" :x="item.x" :y="item.y" :w="item.w" :h="item.h">` — items iterate over the layout array
- `@layout-updated` fires **after** drag/resize completes (not continuously)
- `dragAllowFrom` prop on `<grid-item>` restricts drag to specific elements

- [ ] **Step 1: Add vue-grid-layout imports and remove SortableJS**

At the top of the `<script>` section in CategoryGroup.vue:

Remove: `import Sortable from 'sortablejs'`

Add:
```javascript
import { GridLayout, GridItem } from 'vue-grid-layout'
```

Update the `components` object — remove `Sortable` (if registered), add `GridLayout, GridItem`.

- [ ] **Step 2: Add computed properties for grid settings and responsive config**

Add these computed properties:

```javascript
gridSettings: function() {
    var config = this.category.config || {}
    if (typeof config === 'string') {
        try { config = JSON.parse(config) } catch (e) { config = {} }
    }
    var gs = config._gridSettings || {}
    return {
        colCount: gs.colCount || 12,
        rowHeight: gs.rowHeight || 80,
        autoCompress: gs.autoCompress !== undefined ? gs.autoCompress : true,
        minHeight: gs.minHeight || 0,
    }
},

gridMinHeight: function() {
    var mh = this.gridSettings.minHeight
    if (mh > 0) {
        return { minHeight: (mh * this.gridSettings.rowHeight) + 'px' }
    }
    return {}
},

responsiveCols: function() {
    var col = this.gridSettings.colCount
    return {
        lg: col,
        md: Math.max(1, Math.round(col * 0.67)),
        sm: Math.max(1, Math.round(col * 0.5)),
        xs: 1,
    }
},
```

- [ ] **Step 3: Add layout data property and watcher**

Add to `data()`:

```javascript
gridLayout: [],
layoutUpdateTimer: null,
```

Add a watcher that syncs the layout data from the service list (this keeps `gridLayout` in sync when services change, e.g. after migration or adding a new service):

```javascript
watch: {
    'category.services': {
        handler: function(services) {
            var layout = []
            var svcs = services || []
            for (var i = 0; i < svcs.length; i++) {
                var svc = svcs[i]
                var cfg = svc.widgetConfig || {}
                var l = cfg._layout || { x: 0, y: i, w: 3, h: 2 }
                layout.push({
                    i: String(svc.id),
                    x: l.x,
                    y: l.y,
                    w: l.w,
                    h: l.h,
                })
            }
            this.gridLayout = layout
        },
        immediate: true,
        deep: true,
    },
},
```

If there is already a `watch` object with an `editMode` watcher for SortableJS, replace that watcher with this one. If the `editMode` watcher only calls `initSortable`/`destroySortable`, remove it entirely.

- [ ] **Step 4: Replace the grid template**

Replace the existing grid `<div>` block (the `<div ref="serviceGrid" class="category-group__grid" ...>` and its ServiceCard v-for contents) with:

```vue
<grid-layout
    ref="serviceGrid"
    class="category-group__grid"
    :style="gridMinHeight"
    :layout.sync="gridLayout"
    :col-num="gridSettings.colCount"
    :row-height="gridSettings.rowHeight"
    :is-draggable="editMode"
    :is-resizable="editMode"
    :vertical-compact="gridSettings.autoCompress"
    :use-css-transforms="true"
    :responsive="true"
    :cols="responsiveCols"
    :margin="[12, 12]"
    @layout-updated="onLayoutUpdated">
    <grid-item
        v-for="item in gridLayout"
        :key="item.i"
        :i="item.i"
        :x="item.x"
        :y="item.y"
        :w="item.w"
        :h="item.h"
        class="category-group__grid-item"
        :class="{ 'category-group__grid-item--editing': editMode }">
        <ServiceCard
            :service="getServiceById(item.i)"
            :edit-mode="editMode"
            :card-style="cardStyle"
            :card-background="cardBackground"
            :status-style="statusStyle"
            :widget-data="getWidgetData(parseInt(item.i))"
            :show-status-bars="showStatusBars"
            :status-bars-opacity="statusBarsOpacity"
            :manual-colors="manualColors"
            @click="handleServiceClick(getServiceById(item.i))"
            @edit="$emit('edit-service', parseInt(item.i))"
            @status-click="$emit('status-click', $event)" />
    </grid-item>
</grid-layout>
```

Note: Items iterate over `gridLayout` (the data array), NOT over `category.services`. This is the standard vue-grid-layout pattern.

- [ ] **Step 5: Add helper methods**

Add these methods:

```javascript
getServiceById: function(itemI) {
    var id = parseInt(itemI)
    var services = this.category.services || []
    for (var i = 0; i < services.length; i++) {
        if (services[i].id === id) return services[i]
    }
    return null
},

onLayoutUpdated: function(newLayout) {
    if (!this.editMode) return
    var layoutMap = {}
    for (var i = 0; i < newLayout.length; i++) {
        var item = newLayout[i]
        layoutMap[parseInt(item.i)] = {
            x: item.x,
            y: item.y,
            w: item.w,
            h: item.h,
        }
    }
    var store = useDashboardStore()
    store.batchUpdateLayouts(this.category.id, layoutMap)
},
```

- [ ] **Step 6: Remove old grid code**

Remove the following from CategoryGroup.vue (identify by content, not line numbers, as earlier changes may shift lines):

1. **`gridStyle` computed property** — the one returning `gridTemplateColumns: 'repeat(...)'`
2. **`cardGridStyle` method** — the one returning `{ gridColumn: 'span ' + span }`
3. **`initSortable` method** — the one calling `Sortable.create(el, { group: 'services', ... })`
4. **`destroySortable` method** — the one calling `this.sortableInstance.destroy()`
5. **`sortableInstance` from data** — no longer needed
6. **`editMode` watcher** that calls `initSortable`/`destroySortable` — already replaced in Step 3
7. **`mounted` hook** call to `initSortable` — no longer needed
8. **`beforeDestroy` hook** call to `destroySortable` — no longer needed
9. **Old grid CSS** — the `&__grid` rule with `display: grid; grid-template-columns: repeat(auto-fill, ...); gap: 12px; min-height: 40px;`

- [ ] **Step 7: Handle collapse/expand re-initialization**

In the collapse toggle method (wherever `isCollapsed` is toggled), add after expanding:

```javascript
// After category expand, force vue-grid-layout to re-render
var self = this
this.$nextTick(function() {
    // Toggle a key to force re-mount of grid-layout
    self.gridLayoutKey = (self.gridLayoutKey || 0) + 1
})
```

Add `gridLayoutKey: 0` to `data()`, and bind `:key="gridLayoutKey"` on the `<grid-layout>` component. This forces a clean re-render after expanding from collapsed state.

- [ ] **Step 8: Remove `@reorder-services` emission**

Since vue-grid-layout manages positioning via x/y coordinates, the `reorder-services` event is no longer needed from this component. Remove any `$emit('reorder-services', ...)` calls. The parent DashboardView may still bind this event — that will be cleaned up in Task 6.

- [ ] **Step 9: Add new CSS for grid items**

Add to the `<style>` section:

```scss
.category-group {
    &__grid-item {
        &--editing {
            border: 1px dashed var(--color-border-dark);
            border-radius: var(--border-radius-large);
            cursor: grab;
        }
    }
}

// vue-grid-layout placeholder styling
.vue-grid-item.vue-grid-placeholder {
    background: var(--color-primary) !important;
    opacity: 0.2;
    border-radius: var(--border-radius-large);
}

// vue-grid-layout resize handle styling
.vue-grid-item > .vue-resizable-handle {
    opacity: 0;
    transition: opacity 0.15s;
}

.category-group__grid-item--editing > .vue-resizable-handle {
    opacity: 0.3;
    &:hover {
        opacity: 1;
    }
}
```

- [ ] **Step 10: Build and verify**

```bash
cd /var/www/nextcloud/apps/linkboard && npm run build
```

Expected: Build succeeds.

- [ ] **Step 11: Commit**

```bash
cd /var/www/nextcloud/apps/linkboard
git add src/components/Dashboard/CategoryGroup.vue
git commit -m "feat: replace CSS Grid with vue-grid-layout in CategoryGroup"
```

---

### Task 5: Remove resize logic and drag handle from ServiceCard

**Files:**
- Modify: `src/components/Dashboard/ServiceCard.vue`

- [ ] **Step 1: Remove resize handle from template**

Remove the resize handle element — the `<span>` with class `service-card__resize-handle` and the `@mousedown.stop.prevent="startResize"` binding.

- [ ] **Step 2: Remove drag handle from template**

Remove the drag handle element — the `<span>` with class `service-card__drag-handle`. vue-grid-layout handles drag via the grid-item wrapper, so the internal drag handle is no longer needed.

- [ ] **Step 3: Remove resize methods and computed**

Remove:
- The `startResize` method (the one with `onMouseMove`/`onMouseUp` listeners and `Math.round` snapping)
- The `effectiveColSpan` computed property

- [ ] **Step 4: Remove CSS for resize handle and drag handle**

Remove:
- The `&__resize-handle` CSS block
- The `&__drag-handle` CSS block

- [ ] **Step 5: Remove unused icon imports**

Remove imports for `ResizeBRIcon` and the drag handle icon (e.g. `DragIcon` or `DragHorizontalVariantIcon`) if they are only used for those handles. Check if they are used elsewhere in the component before removing.

- [ ] **Step 6: Make card fill its grid-item container**

Ensure the root `.service-card` element fills the grid-item height. Add or update in the CSS:

```scss
.service-card {
    height: 100%;
    overflow-y: auto;
}
```

- [ ] **Step 7: Build and verify**

```bash
cd /var/www/nextcloud/apps/linkboard && npm run build
```

Expected: Build succeeds.

- [ ] **Step 8: Commit**

```bash
cd /var/www/nextcloud/apps/linkboard
git add src/components/Dashboard/ServiceCard.vue
git commit -m "feat: remove resize/drag handles from ServiceCard, fill grid-item container"
```

---

### Task 6: Update DashboardView — lock/unlock icon, remove service-moved

**Files:**
- Modify: `src/components/Dashboard/DashboardView.vue`

- [ ] **Step 1: Replace pencil icon with lock/unlock**

Find the edit mode NcButton (the one with `PencilIcon` and `@click="toggleEditMode"`). Replace it with:

```vue
<NcButton
    :type="editMode ? 'primary' : 'tertiary'"
    :aria-label="editMode ? t('linkboard', 'Lock layout') : t('linkboard', 'Unlock layout')"
    @click="toggleEditMode">
    <template #icon>
        <LockOpenVariantIcon v-if="editMode" :size="20" />
        <LockIcon v-else :size="20" />
    </template>
    {{ editMode ? t('linkboard', 'Done') : t('linkboard', 'Edit') }}
</NcButton>
```

- [ ] **Step 2: Add icon imports**

Add:
```javascript
import LockIcon from 'vue-material-design-icons/Lock.vue'
import LockOpenVariantIcon from 'vue-material-design-icons/LockOpenVariant.vue'
```

Register them in `components`. Remove `PencilIcon` import if no longer used anywhere in DashboardView.

- [ ] **Step 3: Remove `@service-moved` bindings and handler**

Remove `@service-moved="handleServiceMoved"` from both CategoryGroup template bindings (there are two — one around line 112 and one around line 133).

Remove the `handleServiceMoved` method (around line 600).

- [ ] **Step 4: Remove `@reorder-services` bindings if no longer needed**

Since card ordering is now managed by vue-grid-layout x/y positions, check if `@reorder-services="handleReorderServices"` is still needed. If `handleReorderServices` only reordered cards within a category (which is now handled by layout positions), remove the binding and method.

- [ ] **Step 5: Add translations for new strings**

Add to all `l10n/*.json` files:
- `"Lock layout"` — DE: `"Layout sperren"`
- `"Unlock layout"` — DE: `"Layout entsperren"`

- [ ] **Step 6: Build and verify**

```bash
cd /var/www/nextcloud/apps/linkboard && npm run build
```

Expected: Build succeeds.

- [ ] **Step 7: Commit**

```bash
cd /var/www/nextcloud/apps/linkboard
git add src/components/Dashboard/DashboardView.vue l10n/
git commit -m "feat: replace pencil edit toggle with lock/unlock icons, remove service-moved"
```

---

### Task 7: Update CategoryEditor with grid settings

**Files:**
- Modify: `src/components/Editor/CategoryEditor.vue`

- [ ] **Step 1: Replace columns dropdown with grid settings fields**

Find the columns field (the `<div>` with label "Columns" and `NcSelect v-model="form.columns"`). Replace it with:

```vue
<div class="category-editor__field">
    <label>{{ t('linkboard', 'Grid columns') }}</label>
    <NcSelect v-model="gridConfig.colCount" :options="colCountOptions" :clearable="false" />
</div>
<div class="category-editor__field">
    <label>{{ t('linkboard', 'Row height (px)') }}</label>
    <NcTextField :value="String(gridConfig.rowHeight)" type="number" @update:value="gridConfig.rowHeight = parseInt($event) || 80" />
</div>
<div class="category-editor__field">
    <NcCheckboxRadioSwitch :checked.sync="gridConfig.autoCompress">
        {{ t('linkboard', 'Auto-arrange cards') }}
    </NcCheckboxRadioSwitch>
</div>
<div class="category-editor__field">
    <label>{{ t('linkboard', 'Minimum height (rows)') }}</label>
    <NcTextField :value="String(gridConfig.minHeight)" type="number" @update:value="gridConfig.minHeight = parseInt($event) || 0" />
</div>
```

- [ ] **Step 2: Update data() to include gridConfig**

In `data()`, the variable `cfg` is already defined as `this.category.config || {}`. Add JSON parsing since `config` may be a string:

```javascript
// At the start of data(), after var cfg = this.category.config || {}
if (typeof cfg === 'string') {
    try { cfg = JSON.parse(cfg) } catch (e) { cfg = {} }
}
```

Then add to the returned data object:

```javascript
colCountOptions: [6, 12, 24],
gridConfig: {
    colCount: (cfg._gridSettings || {}).colCount || 12,
    rowHeight: (cfg._gridSettings || {}).rowHeight || 80,
    autoCompress: (cfg._gridSettings || {}).autoCompress !== undefined ? (cfg._gridSettings || {}).autoCompress : true,
    minHeight: (cfg._gridSettings || {}).minHeight || 0,
},
```

Remove `columnOptions: [1, 2, 3, 4, 5, 6]`.

- [ ] **Step 3: Update save() to include grid settings and set columns to null**

In the `save()` method, the grid settings must be merged into the config for ALL category types (not just resources). Add this code **after** the existing resources config block (the `if (this.form.type === 'resources') { ... }` block):

```javascript
// Merge grid settings into config for all category types
var existingConfig = {}
if (payload.config) {
    // Resources type already set payload.config above
    existingConfig = JSON.parse(payload.config)
} else {
    var rawConfig = this.category.config || {}
    if (typeof rawConfig === 'string') {
        try { existingConfig = JSON.parse(rawConfig) } catch (e) { existingConfig = {} }
    } else {
        existingConfig = Object.assign({}, rawConfig)
    }
}
existingConfig._gridSettings = {
    colCount: this.gridConfig.colCount,
    rowHeight: this.gridConfig.rowHeight,
    autoCompress: this.gridConfig.autoCompress,
    minHeight: this.gridConfig.minHeight,
}
payload.config = JSON.stringify(existingConfig)
```

Also change `columns: isDefault ? this.form.columns : null` to `columns: null` in the payload — this explicitly clears the old columns value after migration.

- [ ] **Step 4: Update category watcher to sync gridConfig**

In the `category` watcher handler, after the existing `resourceConfig` update, add JSON parsing and gridConfig sync:

```javascript
if (typeof cfg === 'string') {
    try { cfg = JSON.parse(cfg) } catch (e) { cfg = {} }
}
var gs = cfg._gridSettings || {}
this.gridConfig = {
    colCount: gs.colCount || 12,
    rowHeight: gs.rowHeight || 80,
    autoCompress: gs.autoCompress !== undefined ? gs.autoCompress : true,
    minHeight: gs.minHeight || 0,
}
```

Note: the watcher already has `var cfg = newVal.config || {}` — add the JSON parsing right after that line.

- [ ] **Step 5: Add translations**

Add to all `l10n/*.json` files:
- `"Grid columns"` — DE: `"Rasterspalten"`
- `"Row height (px)"` — DE: `"Zeilenhöhe (px)"`
- `"Auto-arrange cards"` — DE: `"Karten automatisch anordnen"`
- `"Minimum height (rows)"` — DE: `"Mindesthöhe (Zeilen)"`

- [ ] **Step 6: Build and verify**

```bash
cd /var/www/nextcloud/apps/linkboard && npm run build
```

Expected: Build succeeds.

- [ ] **Step 7: Commit**

```bash
cd /var/www/nextcloud/apps/linkboard
git add src/components/Editor/CategoryEditor.vue l10n/
git commit -m "feat: add grid settings to CategoryEditor (colCount, rowHeight, autoCompress, minHeight)"
```

---

### Task 8: Update ServiceEditor — remove _colSpan, add "Move to category", preserve _layout

**Files:**
- Modify: `src/components/Editor/ServiceEditor.vue`

- [ ] **Step 1: Remove _colSpan input**

Find the `_colSpan` NcTextField (the one with `:label="t('linkboard', 'Card columns')"` and `setWidgetConfigValue('_colSpan', $event)`). Remove that entire `<div class="service-editor__field">` block.

Keep the `_itemsPerRow` field. If the `_colSpan` and `_itemsPerRow` fields are wrapped together in a `<template v-if="form.widgetType && form.widgetType !== 'resources'">`, keep the template wrapper for `_itemsPerRow`.

- [ ] **Step 2: Update "Move to category" to use existing categoryOptions**

The ServiceEditor already has a category dropdown at line 33 using `categoryOptions` computed (which maps `{ value: cat.id, label: cat.name }`). This existing dropdown already allows changing the service's category via `form.categoryId`. No additional "Move to category" dropdown is needed — the existing one already serves this purpose. Just verify it works correctly (it should, since `save()` already sends `categoryId` in the payload).

- [ ] **Step 3: Preserve _layout in widget type change watcher**

In the widget type change watcher (the one watching `'form.widgetType'`), find these lines:

```javascript
var savedColSpan = oldCfg._colSpan
...
if (savedColSpan) cfg._colSpan = savedColSpan
```

Replace with:

```javascript
var savedLayout = oldCfg._layout
...
if (savedLayout) cfg._layout = savedLayout
```

Remove the `savedColSpan` variable and its usage entirely.

- [ ] **Step 4: Build and verify**

```bash
cd /var/www/nextcloud/apps/linkboard && npm run build
```

Expected: Build succeeds.

- [ ] **Step 5: Commit**

```bash
cd /var/www/nextcloud/apps/linkboard
git add src/components/Editor/ServiceEditor.vue
git commit -m "feat: remove _colSpan from editor, preserve _layout on widget type change"
```

---

### Task 9: Final build, manual testing, and cleanup

**Files:**
- Modify: `js/linkboard-main.js` (built artifact)

- [ ] **Step 1: Full production build**

```bash
cd /var/www/nextcloud/apps/linkboard && npm run build
```

Expected: Build succeeds with no errors or warnings.

- [ ] **Step 2: Manual browser testing checklist**

Open the LinkBoard dashboard in a browser and verify:

1. Existing cards appear in correct positions (migration ran)
2. Click lock/unlock icon → cards show dashed borders in edit mode
3. Drag a card → it moves smoothly, snaps to grid
4. Resize a card (bottom-right handle from vue-grid-layout) → smooth resize in both width and height
5. Cards persist position after page reload
6. CategoryEditor → grid settings (colCount, rowHeight, autoCompress) work
7. Toggle autoCompress off → cards stay in place with gaps
8. Create a new service → it gets a default position
9. Move a service to another category via ServiceEditor category dropdown
10. Collapse/expand a category → layout re-renders correctly
11. On small screen (< 768px) → cards go to single column via responsive breakpoints

- [ ] **Step 3: Commit built assets**

```bash
cd /var/www/nextcloud/apps/linkboard
git add js/linkboard-main.js
git commit -m "build: production assets for grid layout feature"
```

---

### Task 10: Update documentation

**Files:**
- Modify: `README.md`
- Modify: `CHANGELOG.md`

- [ ] **Step 1: Update README.md**

Add to the features list:
- Grafana-style grid layout with free drag, resize, and placement of cards
- Configurable grid granularity (6, 12, or 24 columns per category)
- Auto-arrange toggle per category
- Edit mode with lock/unlock toggle

- [ ] **Step 2: Update CHANGELOG.md**

Add entry under `## [Unreleased]` or the next version:

```markdown
### Added
- Grafana-style grid layout: free drag, resize, and placement of Service Cards within categories
- Configurable grid settings per category (column count, row height, auto-arrange, min-height)
- Lock/unlock edit mode toggle replaces pencil icon

### Changed
- Service Cards now use vue-grid-layout instead of CSS Grid with fixed column spans
- Category columns setting replaced by grid settings (colCount: 6/12/24)

### Removed
- Cross-category drag-and-drop (use category dropdown in Service Editor instead)
- Fixed column-span resize handle on Service Cards (replaced by vue-grid-layout handles)
```

- [ ] **Step 3: Commit**

```bash
cd /var/www/nextcloud/apps/linkboard
git add README.md CHANGELOG.md
git commit -m "docs: update README and CHANGELOG for grid layout feature"
```
