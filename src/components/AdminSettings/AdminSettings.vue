<template>
	<div class="linkboard-admin">
		<h2>LinkBoard</h2>

		<div class="linkboard-admin__section">
			<h3>{{ t('linkboard', 'Restrict app to groups') }}</h3>
			<p class="linkboard-admin__hint">
				{{ t('linkboard', 'If no groups are selected, LinkBoard is available to all users.') }}
			</p>
			<NcSelect
				v-model="selectedGroups"
				:multiple="true"
				:options="availableGroups"
				:placeholder="t('linkboard', 'Search groups…')"
				label="displayName"
				track-by="id"
				:close-on-select="false"
				:loading="searchingGroups"
				@search="searchGroups">
				<template #noOptions>
					{{ t('linkboard', 'No groups found') }}
				</template>
			</NcSelect>
		</div>

		<div class="linkboard-admin__section">
			<h3>{{ t('linkboard', 'Global LinkBoard') }}</h3>
			<NcCheckboxRadioSwitch
				:checked="globalBoardEnabled"
				type="switch"
				@update:checked="onGlobalBoardToggle">
				{{ t('linkboard', 'Show a global LinkBoard for all users') }}
			</NcCheckboxRadioSwitch>
			<template v-if="globalBoardEnabled">
				<p class="linkboard-admin__hint">
					{{ t('linkboard', 'Select a user whose LinkBoard will be displayed to all users. Only admins can edit this board.') }}
				</p>
				<NcSelect
					v-model="selectedBoardUser"
					:options="availableBoards"
					:placeholder="t('linkboard', 'Select user…')"
					label="displayName"
					track-by="userId"
					:loading="loadingBoards"
					@open="loadBoards">
					<template #option="{ displayName, categoryCount }">
						{{ displayName }} ({{ t('linkboard', '{n} categories', { n: categoryCount }) }})
					</template>
					<template #noOptions>
						{{ t('linkboard', 'No boards found') }}
					</template>
				</NcSelect>
			</template>
		</div>

		<div class="linkboard-admin__section">
			<h3>{{ t('linkboard', 'Status check interval') }}</h3>
			<div class="linkboard-admin__range-row">
				<input type="range" min="1" max="30" step="1"
					:value="statusCheckIntervalMin"
					@input="statusCheckIntervalMin = parseInt($event.target.value)">
				<span>{{ t('linkboard', '{n} minutes', { n: statusCheckIntervalMin }) }}</span>
			</div>
		</div>

		<NcButton type="primary" :disabled="saving" @click="save">
			{{ saving ? t('linkboard', 'Saving…') : t('linkboard', 'Save') }}
		</NcButton>

		<NcNoteCard v-if="saved" type="success">
			{{ t('linkboard', 'Settings saved') }}
		</NcNoteCard>
	</div>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { NcSelect, NcButton, NcNoteCard, NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { t } from '@nextcloud/l10n'

var debounceTimer = null

export default {
	name: 'AdminSettings',
	components: { NcSelect, NcButton, NcNoteCard, NcCheckboxRadioSwitch },
	data() {
		var config = loadState('linkboard', 'admin-config')
		return {
			selectedGroups: config.allowedGroups || [],
			availableGroups: config.allowedGroups || [],
			statusCheckIntervalMin: Math.round((config.statusCheckInterval || 300) / 60),
			globalBoardEnabled: config.globalBoardEnabled || false,
			selectedBoardUser: config.globalBoardUser || null,
			availableBoards: config.globalBoardUser ? [config.globalBoardUser] : [],
			loadingBoards: false,
			searchingGroups: false,
			saving: false,
			saved: false,
		}
	},
	methods: {
		t,
		onGlobalBoardToggle(checked) {
			this.globalBoardEnabled = checked
			if (checked && this.availableBoards.length <= 1) {
				this.loadBoards()
			}
		},
		loadBoards() {
			this.loadingBoards = true
			var url = generateUrl('/apps/linkboard/api/v1/admin/boards')
			axios.get(url)
				.then((response) => {
					this.availableBoards = response.data
				})
				.finally(() => {
					this.loadingBoards = false
				})
		},
		searchGroups(query) {
			clearTimeout(debounceTimer)
			debounceTimer = setTimeout(() => {
				this.searchingGroups = true
				var url = generateUrl('/apps/linkboard/api/v1/admin/groups')
				axios.get(url, { params: { search: query } })
					.then((response) => {
						this.availableGroups = response.data
					})
					.finally(() => {
						this.searchingGroups = false
					})
			}, 300)
		},
		save() {
			this.saving = true
			this.saved = false
			var url = generateUrl('/apps/linkboard/api/v1/admin/settings')
			var data = {
				statusCheckInterval: this.statusCheckIntervalMin * 60,
				allowedGroups: this.selectedGroups.map(g => g.id),
				globalBoardEnabled: this.globalBoardEnabled,
				globalBoardUser: this.selectedBoardUser ? this.selectedBoardUser.userId : '',
			}
			axios.put(url, data)
				.then(() => {
					this.saved = true
					setTimeout(() => { this.saved = false }, 3000)
				})
				.finally(() => {
					this.saving = false
				})
		},
	},
}
</script>

<style scoped>
.linkboard-admin {
	max-width: 700px;
	padding: 20px;
}
.linkboard-admin__section {
	margin-bottom: 24px;
}
.linkboard-admin__hint {
	color: var(--color-text-maxcontrast);
	margin-bottom: 8px;
}
.linkboard-admin__range-row {
	display: flex;
	align-items: center;
	gap: 12px;
}
.linkboard-admin__range-row input[type="range"] {
	flex: 1;
	max-width: 300px;
}
</style>
