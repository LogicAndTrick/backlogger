import Vue from 'vue'
import Vuex from 'vuex'

import platforms from '../data/platforms.json';
import regions from '../data/regions.json';

Vue.use(Vuex);

export default new Vuex.Store({
  state: {
    baseUrl: 'http://localhost:85/',
    platforms: platforms,
    regions: regions,
    user: null
  },
  mutations: {
    login(state, user) {
      state.user = user;
    },
    add(state, game) {
      state.user.games.push(game);
    }
  },
  actions: {

  }
})
