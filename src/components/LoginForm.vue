<template>
  <div class="section">
      <form @submit.prevent="login">
        <div class="field has-addons">
          <div class="control">
            <input class="input" v-model="password" type="text" placeholder="Password">
          </div>
          <div class="control">
            <button class="button is-info" :disabled="loading">
              <font-awesome-icon icon="user"></font-awesome-icon>
              &nbsp; Login
            </button>
          </div>
        </div>
        <p class="help">Your login will be saved for future visits.</p>
      </form>
  </div>
</template>

<script>
import methods from "../methods";

export default {
  name: 'LoginForm',
  data() {
    return {
      password: '',
      loading: false
    };
  },
  methods: {
    async login() {
      this.loading = true;
      let result = null;
      try {
        result = await methods.request(this.$store.state.baseUrl, 'login', {
          password: this.password
        });
      } catch {
        // ... 
      }
      this.loading = false;
      if (result) {
        result.password = this.password;
        this.$emit('login', result);
      }
    }
  }
}
</script>
