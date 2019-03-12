<template>
  <div class="settings">
      
    <div v-if="saving" class="has-text-centered margin-vertical-4" key="L">
      <i class="fas fa-spinner fa-pulse fa-3x"></i>
    </div>
    <div v-else key="D">
      <h3 class="title">Platforms to exclude</h3>
      <h4 class="subtitle">Never show these platforms</h4>

      <div class="field" v-for="p in platforms" :key="p.id">
        <b-checkbox v-model="ignoredPlatforms" :native-value="p.id">{{p.abbreviation}} - {{p.name}}</b-checkbox>
      </div>

      <button type="button" class="button is-primary" @click="save">
        Save
      </button>
    </div>

  </div>
</template>

<script>
import methods from "../methods";

export default {
  name: 'settings',
  data() {
    return {
      saving: false,
      ignoredPlatforms: []
    }
  },
  mounted() {
    this.updateUser();
  },
  computed: {
    platforms() {
      return this.$store.state.platforms;
    },
  },
  methods: {
    updateUser() {
      let login = this.$store.state.user;
      let view = this.$route.params.user;
      if (!login || view !== login.name) {
        this.$router.push('/');
        return;
      }
      this.ignoredPlatforms = login.ignored_platforms || [];
    },
    async save() {
      this.saving = true;
      
      let result = await methods.request(this.$store.state.baseUrl, 'settings', {
        user_id: this.$store.state.user.id,
        data: {
          ignored_platforms: this.ignoredPlatforms
        }
      });

      this.$store.commit('login', result);
      this.saving = false;
      this.$router.push('/');
    },
  },
  watch: {
    $route() {
      this.updateUser();
    }
  }
}
</script>

<style lang="scss">
.summary {
  white-space: pre-wrap;
}
</style>

