import PreviewBrowser from "./PreviewBrowser.vue";
import PreviewFields from "./PreviewFields.vue";
import PreviewView from "./PreviewView.vue";

export default {
	install(app) {
		app.component("k-preview-browser", PreviewBrowser);
		app.component("k-preview-fields", PreviewFields);
		app.component("k-preview-view", PreviewView);
	}
};
