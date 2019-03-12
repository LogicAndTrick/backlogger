<template>
  <div class="add">

    <form method="post" @submit.prevent="search">
      <div class="field has-addons">
        <div class="control is-expanded">
          <input v-model="query" class="input is-size-4" type="text" placeholder="Type a name">
        </div>
        <div class="control">
          <div class="select">
            <select v-model="platform" class="is-size-4">
              <option :value="null">(All)</option>
              <option v-for="p in platforms" :value="p.id" :key="p.id">
                {{p.name}}
              </option>
            </select>
          </div>
        </div>
        <div class="control">
          <button type="submit" class="button is-info is-size-4" :disabled="query.length < 3" @click.prevent="search">
            <span class="fas fa-search"></span>
          </button>
        </div>
      </div>
    </form>

    <div v-if="searching" class="has-text-centered margin-vertical-4">
      <i class="fas fa-spinner fa-pulse fa-3x"></i>
    </div>
    <div v-else-if="results.length">
      <div v-for="r in results" :key="r.id">
        <div class="box margin-vertical-1">
          <div class="columns">
            <div class="column is-one-quarter" v-if="r.cover">
              <figure class="image">
                <img :src="'https://images.igdb.com/igdb/image/upload/t_cover_big/' + r.cover.image_id + '.jpg'" />
              </figure>
            </div>
            <div class="column">
              <div class="columns">
                <div class="column">
                  <h1 class="title">
                    <a :href="r.url" target="_blank">{{r.name}}</a>
                  </h1>
                  <small class="subtitle">
                    {{formatReleaseDate(r.first_release_date)}}
                    &mdash;
                    {{formatReleasePlatforms(r.release_dates)}}
                  </small>
                </div>
                <div class="column is-one-fifth">
                  <button class="button is-success is-pulled-right" @click="add(r)">
                    <span class="fas fa-plus"></span> &nbsp; Add
                  </button>
                </div>
              </div>
              <p class="summary">{{r.summary}}</p>
              <div class="tags margin-top-2" v-if="r.genres || r.themes">
                <span v-for="g in r.genres || []" :key="'g' + g.id" class="tag is-primary">{{g.name}}</span>
                <span v-for="t in r.themes || []" :key="'t' + t.id" class="tag is-info margin-right-1">{{t.name}}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
  </div>
</template>

<script>
import methods from "../methods";
import _ from 'lodash';

export default {
  name: 'add',
  data() {
    return {
      query: '',
      platform: null,
      searching: false,
      results: []
    }
  },
  mounted() {
    this.updateUser();
  },
  computed: {
    ignoredPlatforms() {
      return this.$store.state.user.ignored_platforms || [];
    },
    platforms() {
      return _.chain(this.$store.state.platforms)
        .filter(x => {
          return this.ignoredPlatforms.indexOf(x.id) < 0;
        })
        .value();
    },
    indexedPlatforms() {
      return _.indexBy(this.platforms, 'id');
    }
  },
  methods: {
    updateUser() {
      let login = this.$store.state.user;
      let view = this.$route.params.user;
      if (!login || view !== login.name) {
        this.$router.push('/');
        return;
      }
      this.$nextTick(() => {
        this.$el.getElementsByTagName('input')[0].focus();
      });
    },
    async search() {
      if (this.query.length < 3) return;
      this.searching = true;
      this.results = [];
      
      let result = await methods.request(this.$store.state.baseUrl, 'search', {
        query: this.query,
        platform: this.platform
      });

      this.searching = false;
      this.results = result;
    },
    formatReleasePlatforms(releaseDates) {
      let platforms = _.chain(releaseDates)
        .map('platform')
        .uniq()
        .map(p => {
          return _.find(this.platforms, { id: p });
        })
        .map('name')
        .compact()
        .value();
      return platforms.join(', ') || 'Other platform';
    },
    formatReleaseDate(date) {
      return date ? new Date(date * 1000).getFullYear() : 'Unknown';
    },
    async add(game) {
      
      this.searching = true;

      let result = await methods.request(this.$store.state.baseUrl, 'add', {
        user_id: this.$store.state.user.id,
        game_id: game.id
      });
      this.$store.commit('add', result);
      this.$router.push({ name: 'game', params: { slug: result.slug } });
    }
  },
  watch: {
    $route() {
      this.updateUser();
    }
  }
}
</script>

<style lang="scss" scoped>
.title {
  margin-bottom: 0.25rem !important;
}
.summary {
  white-space: pre-wrap;
}
</style>

