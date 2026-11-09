import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import './assets/base.css'
import './assets/styles/home.css'
import './assets/styles/layout-header.css'
import './assets/styles/motion.css'
createApp(App).use(router).mount('#app')
