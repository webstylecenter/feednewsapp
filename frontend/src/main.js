import { createApp } from 'vue';
import App from './App.vue';
import { setupFeedReaderGlass } from './feedReaderGlass.js';
import './app.css';
import './feedReaderGlass.css';
import './feedReaderGlassRuntimeFixes.css';
import './feedReaderGlassBlurFix.css';

createApp(App).mount('#app');
setupFeedReaderGlass();
