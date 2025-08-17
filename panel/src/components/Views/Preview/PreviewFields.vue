<template>
	<div class="k-preview-fields">
		<header class="k-preview-fields-header">
			<k-model-tabs
				:tab="tab.name"
				:tabs="tabsLinkingToPreview"
				class="k-drawer-tabs"
			/>

			<k-view-buttons :buttons="buttons" size="xs" variant="none" />
		</header>

		<k-sections
			:blueprint="blueprint"
			:content="content"
			:empty="$t('page.blueprint', { blueprint: $esc(blueprint) })"
			:lock="lock"
			:parent="api"
			:tab="tab"
			@input="$emit('input', $event)"
			@submit="$emit('submit', $event)"
		/>
	</div>
</template>

<script>
import { clone } from "@/helpers/object.js";

export default {
	props: {
		api: String,
		blueprint: String,
		buttons: Array,
		content: Object,
		tab: Object,
		tabs: Array,
		lock: Boolean
	},
	computed: {
		tabsLinkingToPreview() {
			const tabs = clone(this.tabs);

			for (const tab in tabs) {
				delete tabs[tab].link;
				tabs[tab].click = (e) => {
					e?.preventDefault();
					this.$panel.view.reload({ query: { tab: tabs[tab].name } });
				};
			}

			return tabs;
		}
	},
	mounted() {
		this.$events.on("section.loaded", this.fixLinksInSection);
	},
	unmounted() {
		this.$events.off("section.loaded", this.fixLinksInSection);
	},
	methods: {
		fixLinksInSection(section) {
			for (const link of section.$el.querySelectorAll(
				".k-item-title > .k-link"
			)) {
				link.__vue__.onClick = (e) => {
					const url = link.__vue__.to;

					if (url.match(/^\/pages\/[^\/]+$/)) {
						e.preventDefault();
						this.$panel.view.open(url + "/preview/edit");
					}
				};
			}
		}
	}
};
</script>

<style>
.k-preview-fields {
	border: 1px solid var(--color-border);
	border-radius: var(--rounded-lg);
	overflow: clip;
}
.k-preview-fields-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	gap: var(--spacing-6);
	background: var(--preview-browser-color-background);
	height: var(--input-height);
	border-bottom: 1px solid var(--color-border);
}
.k-preview-fields-header:has(> :only-child) {
	justify-content: flex-end;
}
.k-preview-fields-header .k-view-buttons {
	padding-inline: var(--spacing-2);
}
.k-preview-fields-header .k-tabs {
	flex-grow: 1;
	margin-bottom: 0;
	justify-content: start;
}
.k-preview-fields-header .k-tabs-button[aria-current="true"]::after {
	bottom: -1px;
}
.k-preview-fields > .k-sections {
	padding: var(--spacing-6) var(--spacing-3);
	overflow-y: auto;
	height: 100%;
}
</style>
