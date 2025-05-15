<template>
	<k-panel class="k-panel-inside k-preview-view" :data-version-id="versionId">
		<header class="k-preview-view-header">
			<k-button-group>
				<k-button
					:link="back"
					:responsive="true"
					:title="$t('back')"
					icon="angle-left"
					size="sm"
					variant="filled"
				>
				</k-button>
				<k-button
					class="k-preview-view-title"
					:icon="$panel.isLoading ? 'loader' : 'title'"
					:dropdown="true"
					@click="$refs.tree.toggle()"
				>
					{{ title }}
				</k-button>
				<k-dropdown ref="tree" theme="dark" class="k-preview-view-tree">
					<k-page-tree :current="id" @click.stop @select="navigate" />
				</k-dropdown>
			</k-button-group>

			<k-button-group v-if="versionId === 'edit'" layout="collapsed">
				<k-button
					icon="mobile"
					size="sm"
					variant="filled"
					@click="viewport('small')"
				/>
				<k-button
					icon="tablet"
					size="sm"
					variant="filled"
					@click="viewport('medium')"
				/>
				<k-button
					icon="display"
					size="sm"
					variant="filled"
					@click="viewport('large')"
				/>
			</k-button-group>

			<k-button-group>
				<k-view-buttons :buttons="buttons" />
			</k-button-group>
		</header>
		<main class="k-preview-view-grid" :data-view="view">
			<template v-if="versionId === 'edit'">
				<k-preview-browser
					ref="browser"
					v-bind="browserProps('changes')"
					@discard="onDiscard"
					@navigate="onNavigate"
					@submit="onSubmit"
				/>
				<div class="k-preview-fields">
					<header class="k-preview-fields-header">
						<k-model-tabs
							:tab="tab.name"
							:tabs="tabsLinkingToPreview"
							class="k-drawer-tabs"
						/>

						<k-view-buttons :buttons="foo" size="xs" variant="none" />
					</header>

					<k-sections
						:blueprint="blueprint"
						:content="content"
						:empty="$t('page.blueprint', { blueprint: $esc(blueprint) })"
						:lock="lock"
						:parent="api"
						:tab="tab"
						@input="onInput"
						@submit="onSubmit"
					/>
				</div>
			</template>
			<template v-else-if="versionId === 'compare'">
				<k-preview-browser
					v-bind="browserProps('latest')"
					@discard="onDiscard"
					@navigate="onNavigate"
					@submit="onSubmit"
				/>
				<k-preview-browser
					v-bind="browserProps('changes')"
					@discard="onDiscard"
					@navigate="onNavigate"
					@submit="onSubmit"
				/>
			</template>
			<template v-else>
				<k-preview-browser
					v-bind="browserProps(versionId)"
					@discard="onDiscard"
					@navigate="onNavigate"
					@submit="onSubmit"
				/>
			</template>
		</main>
	</k-panel>
</template>

<script>
import { clone } from "@/helpers/object.js";
import ModelView from "@/components/Views/ModelView.vue";

export default {
	extends: ModelView,
	props: {
		back: String,
		versionId: String,
		src: Object,
		title: String,
		foo: Array
	},
	data() {
		return {
			view: "large"
		};
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
		this.$events.on("keydown.esc", this.exit);
		this.$events.on("content.save", this.onChanges);
		this.$events.on("page.changeTitle", this.onChanges);
		this.$events.on("page.sort", this.onChanges);
		this.$events.on("file.sort", this.onChanges);
		this.$events.on("section.loaded", this.fixLinksInSection);
	},
	unmounted() {
		this.$events.off("keydown.esc", this.exit);
		this.$events.off("content.save", this.onChanges);
		this.$events.off("page.changeTitle", this.onChanges);
		this.$events.off("page.sort", this.onChanges);
		this.$events.off("file.sort", this.onChanges);
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
		},
		browserProps(versionId) {
			return {
				editor: this.editor,
				hasDiff: this.hasDiff,
				isLocked: this.isLocked,
				modified: this.modified,
				label: this.$t("version." + versionId),
				src: this.src[versionId],
				versionId: versionId
			};
		},
		exit() {
			if (this.$panel.overlays().length > 0) {
				return;
			}

			this.$panel.view.open(this.link);
		},
		navigate(page) {
			if (page.id === this.id) {
				return;
			}

			this.$refs.tree.close();

			if (page.id === "/") {
				return this.$panel.view.open("site/preview/" + this.versionId);
			}

			const url = this.$api.pages.url(page.id, "preview/" + this.versionId);
			this.$panel.view.open(url);
		},
		onChanges() {
			this.$refs.browser.reload();
		},
		onNavigate(redirect) {
			this.$panel.view.reload({ query: { redirect } });
		},
		viewport(size) {
			this.view = size;
		}
	}
};
</script>

<style>
.k-preview-view {
	position: fixed;
	inset: 0;
	height: 100%;
	display: grid;
	grid-template-rows: auto 1fr;
}
.k-preview-view-header {
	container-type: inline-size;
	display: flex;
	gap: var(--spacing-2);
	justify-content: space-between;
	align-items: center;
	padding: var(--spacing-3);
}
.k-preview-view-tree {
	--tree-branch-color-back: transparent;
	--tree-branch-hover-color-back: var(--color-gray-800);
	--tree-branch-selected-color-back: var(--color-blue-800);

	width: 20rem;
}

.k-preview-view-grid {
	display: flex;
	justify-content: center;
	padding: var(--spacing-3);
	padding-top: 0;
	gap: var(--spacing-3);
	max-height: calc(100vh - 56px);
}
@media screen and (max-width: 60rem) {
	.k-preview-view-grid {
		flex-direction: column;
	}
	.k-preview-view-title {
		display: none;
	}
}
.k-preview-view :where(.k-preview-browser, .k-preview-fields) {
	flex-grow: 1;
	flex-basis: 50%;
}

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
.k-preview-fields > .k-sections {
	padding: var(--spacing-6) var(--spacing-3);
	overflow-y: auto;
	height: 100%;
}

.k-preview-view-grid[data-view="small"] .k-preview-browser {
	flex-basis: 33.33%;
}
.k-preview-view-grid[data-view="small"] .k-preview-fields {
	flex-basis: 66.66%;
}
.k-preview-view-grid[data-view="medium"] .k-preview-browser {
	flex-basis: 45%;
}
.k-preview-view-grid[data-view="medium"] .k-preview-fields {
	flex-basis: 55%;
}
.k-preview-view-grid[data-view="large"] .k-preview-browser {
	flex-basis: 66.66%;
}
.k-preview-view-grid[data-view="large"] .k-preview-fields {
	flex-basis: 33.33%;
}
</style>
