import { createApp } from 'vue';
import App from './App.vue';
import { setupFeedReaderGlass as setupFeedReaderEnhancements } from './components/feed-reader/enhancements.js';
import './styles/index.css';

createApp(App).mount('#app');
setupFeedReaderEnhancements();
