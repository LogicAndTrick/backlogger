<template>
  <div class="home">
    
    <div class="columns is-mobile">
      <div class="column is-one-quarter">
        
        <h1 class="title is-6">Name</h1>
        <div class="field">
          <input type="text" class="input" v-model="name" />
        </div>
        
        <h1 class="title is-6">Platform</h1>
        <div class="field" v-for="p in platforms" :key="p.id">
          <b-checkbox v-model="platform" :native-value="p.id">{{p.abbreviation}}</b-checkbox>
        </div>
        
        <h1 class="title is-6">Release Year</h1>
        <div class="field" v-for="y in release_years" :key="y">
          <b-checkbox v-model="year" :native-value="y">{{y}}</b-checkbox>
        </div>

      </div>
      <div class="column">
        
        <div class="grid">

          <router-link
            :to="{ name: 'game', params: { slug: g.slug }}" class="grid-item" v-for="g in games" :key="g.id"
            :style="{ 'background-image': 'url(\'https://images.igdb.com/igdb/image/upload/t_cover_big/' + g.cover.image_id + '.jpg\')' }"
          >
            <span class="image">
            </span>
          </router-link>

        </div>

      </div>
    </div>

  </div>
</template>

<script>
import _ from 'lodash';

export default {
  name: 'home',
  data() {
    return {
      name: '',
      platform: [],
      year: []
    }
  },
  computed: {
    all_games() {
      return this.$store.state.user.games;
    },
    games() {
      return _.chain(this.all_games)
        .filter(g => {
          if (!this.name) return true;
          return g.name.toLowerCase().indexOf(this.name.toLowerCase()) >= 0;
        })
        .filter(g => {
          if (this.platform.length == 0) return true;
          return _.some(g.release_dates, r => this.platform.indexOf(r.platform) >= 0);
        })
        .filter(g => {
          if (this.year.length == 0) return true;
          return this.year.indexOf(new Date(g.first_release_date * 1000).getFullYear()) >= 0;
        })
        .value();
    },
    platforms() {
      let platforms = this.$store.state.platforms;
      let gamePlatforms = _.chain(this.all_games)
        .map('release_dates')
        .flatten()
        .map('platform')
        .uniq()
        .value();
      return _.filter(platforms, p => gamePlatforms.indexOf(p.id) >= 0);
    },
    release_years() {
      return _.chain(this.all_games)
        .map('first_release_date')
        .map(x => new Date(x * 1000).getFullYear())
        .uniq()
        .sort()
        .value();
    }
  }
}
</script>

<style lang="scss">
$grid-padding: 5px;

.grid {
  display: flex;
  flex-flow: row wrap;
  margin: -$grid-padding;

  .grid-item {
    flex: 0 0 auto;
    width: 250px;
    height: 250px;
    margin: $grid-padding;
    display: flex;
    background-position: center;
    background-size: cover;
  }
}
</style>

