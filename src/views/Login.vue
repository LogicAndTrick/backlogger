<template>
  <div class="login">

      <login-form @login="login" />

  </div>
</template>

<script>
import LoginForm from '@/components/LoginForm.vue';
import cookie from 'js-cookie';

export default {
  name: 'login',
  components: {
    LoginForm
  },
  methods: {
    login(user) {
      this.$store.commit('login', user);
      cookie.set('password', user.password, { expires: 365 });
      this.$router.push({ name: 'home', params: { user: user.name } });
    }
  }
}
</script>

<style lang="scss" scoped>

</style>

