<template>
  <div id="app">
    
    <nav class="navbar is-info is-fixed-top">
      <div class="navbar-brand">
        <router-link class="navbar-item" to="/">
          backlogger
        </router-link>

        <a role="button" :class="'navbar-burger burger ' + (navExpanded ? 'is-active' : '')" @click="navExpanded = !navExpanded">
          <span aria-hidden="true"></span>
          <span aria-hidden="true"></span>
          <span aria-hidden="true"></span>
        </a>
      </div>

      <div id="navbar-expander" :class="'navbar-menu ' + (navExpanded ? 'is-active' : '')">
        <div class="navbar-start">

          <template v-if="loggedIn">
            <router-link to="/" class="navbar-item">Home</router-link>
            <router-link to="/add" class="navbar-item">Add games</router-link>
            <a class="navbar-item" @click.prevent="logout">Log out</a>
          </template>
          <template v-else>
            <router-link to="/" class="navbar-item">Login</router-link>
          </template>

        </div>
      </div>
    </nav>
    
    <div key="a" v-if="loading" class="has-text-centered margin-vertical-4">
      <i class="fas fa-spinner fa-pulse fa-3x"></i>
    </div>
    <div key="b" v-else class="section">
      <router-view v-if="loggedIn" />
      <login-form v-else @login="login" />
    </div>

  </div>
</template>

<script>
import LoginForm from '@/components/LoginForm.vue';
import cookie from 'js-cookie';
import methods from "./methods";

export default {
  name: 'app',
  components: {
    LoginForm
  },
  data: function() {
    return {
      loading: true,
      navExpanded: false
    };
  },
  mounted() {
    var password = cookie.get('password');
    if (password) {
      methods.request(this.$store.state.baseUrl, 'login', {
        password: password
      }).then(user => {
        this.user = user;
        this.loading = false;
      });
    } else {
      this.loading = false;
    }
  },
  computed: {
    loggedIn() {
      return !!this.user;
    },
    user: {
      get() { return this.$store.state.user; },
      set(user) { this.$store.commit('login', user); }
    }
  },
  methods: {
    login(user) {
      this.user = user;
      cookie.set('password', user.password, { expires: 365 });
    },
    logout() {
      this.user = null;
      cookie.set('password', '', { expires: 365 });
    }
  }
}
</script>


<style lang="scss">
.app-container {
  
}
</style>
