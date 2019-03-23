import Vue from 'vue'
import App from './App.vue'
import router from './router'
import Buefy from 'buefy'
import './assets/scss/app.scss'
import store from './store'

import { library } from '@fortawesome/fontawesome-svg-core'
import { faSpinner, faUser, faSearch, faPlus, faUpload, faChevronDown, faChevronUp, faQuestion, faPlay, faCheck, faPause, faBan, faSquare, faTh, faThLarge, faList, faSortAlphaDown, faPlayCircle } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

library.add(faSpinner, faUser, faSearch, faPlus, faUpload, faChevronDown, faChevronUp, faQuestion, faPlay, faCheck, faPause, faBan, faSquare, faTh, faThLarge, faList, faSortAlphaDown, faPlayCircle);
Vue.component('font-awesome-icon', FontAwesomeIcon);

Vue.use(Buefy)

Vue.config.productionTip = false

new Vue({
  router,
  store,
  render: h => h(App)
}).$mount('#app')
