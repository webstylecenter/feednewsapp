import { createApp } from 'vue';
import App from './App.vue';
import { setupFeedReaderGlass as setupFeedReaderEnhancements } from './components/feed-reader/enhancements.js';
import { applyTheme } from './theme.js';
import './styles/index.css';

applyTheme();
createApp(App).mount('#app');
setupFeedReaderEnhancements();