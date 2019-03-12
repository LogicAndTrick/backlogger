import Vue from 'vue'
import Router from 'vue-router'
import Home from './views/Home.vue'
import Login from './views/Login.vue'
import Add from './views/Add.vue'
import Game from './views/Game.vue'
import Users from './views/Users.vue'
import Settings from './views/Settings.vue'

Vue.use(Router)

const router = new Router({
  routes: [
    {
      path: '/',
      name: '',
      component: Home
    },
    {
      path: '/login',
      name: 'login',
      component: Login
    },
    {
      path: '/users',
      name: 'users',
      component: Users
    },
    {
      path: '/:user',
      name: 'home',
      component: Home
    },
    {
      path: '/:user/settings',
      name: 'settings',
      component: Settings
    },
    {
      path: '/:user/add',
      name: 'add',
      component: Add
    },
    {
      path: '/:user/game/:slug',
      name: 'game',
      component: Game
    }
  ]
});

export default router;