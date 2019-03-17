import Vue from 'vue'
import Vuex from 'vuex'
import _ from 'lodash';

import platforms from '../data/platforms.json';
import regions from '../data/regions.json';
import statuses from '../data/statuses.json';

Vue.use(Vuex);

export default new Vuex.Store({
  state: {
    baseUrl: process.env.NODE_ENV == 'production' ? '/' : 'http://localhost:85/',
    platforms: platforms,
    regions: regions,
    statuses: statuses,
    user: null
  },
  mutations: {
    login(state, user) {
      state.user = user;
    },
    add(state, game) {
      state.user.games.push(game);
    },
    update(state, game) {
      let g = _.find(state.user.games, x => x.id == game.id);
      for (const key in game) {
        g[key] = game[key];
      }
    },
    remove(state, gameId) {
      let idx = _.findIndex(state.user.games, x => x.id == gameId);
      if (idx >= 0) state.user.games.splice(idx, 1);
    }
  },
  actions: {

  }
})
