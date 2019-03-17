<template>
  <div id="app">
    
    <nav class="navbar is-info is-fixed-top">
      <div class="navbar-brand">
        <router-link class="navbar-item" to="/">
          backlogger
        </router-link>

        <template v-if="loggedIn">
          <router-link :to="'/' + user.name + '/'" class="navbar-item">Home</router-link>
          <router-link :to="'/' + user.name + '/add'" class="navbar-item">Add games</router-link>
          <router-link :to="'/' + user.name + '/settings'" class="navbar-item">Settings</router-link>
          <router-link to="/users" class="navbar-item">Other users</router-link>
          <a class="navbar-item" @click.prevent="logout">Log out</a>
        </template>
        <template v-else>
          <router-link to="/login" class="navbar-item">Login</router-link>
        </template>
      </div>
    </nav>
    
    <div key="a" v-if="loading" class="has-text-centered margin-vertical-4">
      <font-awesome-icon icon="spinner" size="3x" pulse></font-awesome-icon>
    </div>
    <div key="b" v-else class="section">
      <router-view />
    </div>

  </div>
</template>

<script>
import cookie from 'js-cookie';
import methods from "./methods";

export default {
  name: 'app',
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
    logout() {
      this.user = null;
      cookie.set('password', '', { expires: 365 });
      this.$router.push({ name: 'login' });
    }
  }
}
</script>


<style lang="scss">
.app-container {
  
}
</style>
