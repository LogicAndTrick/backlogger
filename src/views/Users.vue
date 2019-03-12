<template>
  <div class="users">

    <div v-if="loading" class="has-text-centered margin-vertical-4">
      <i class="fas fa-spinner fa-pulse fa-3x"></i>
    </div>
    <div v-else>
      <h1 class="title">Spy on other users</h1>
      <ul>
        <li v-for="u in users" :key="u.id">
          <router-link :to="'/' + u.name">{{u.name}}</router-link>
        </li>
      </ul>
    </div>

  </div>
</template>

<script>
import methods from "../methods";
import _ from 'lodash';

export default {
  name: 'users',
  data() {
    return {
      loading: true,
      users: []
    }
  },
  mounted() {
    methods.request(this.$store.state.baseUrl, 'users', {}).then(users => {
      this.loading = false;
      this.users = users;
    });
  },
  computed: {
    
  }
}
</script>

<style lang="scss" scoped>

.users {
  font-size: 1.4em;
}

</style>

