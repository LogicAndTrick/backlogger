<template>
  <div class="game">

    <div class="box margin-vertical-1">
      <div class="columns">
        <div class="column is-one-quarter" v-if="game.cover">
          <figure class="image">
            <img :src="'https://images.igdb.com/igdb/image/upload/t_cover_big/' + game.cover.image_id + '.jpg'" />
          </figure>
          <h3 class="title is-4 margin-top-1">Release dates</h3>
          <div class="content">
            <ul>
              <li v-for="rd in releases" :key="rd.id">
                {{rd.year}} - {{rd.platform.abbreviation}}
                <small>{{rd.region ? '(' + rd.region.name + ')' : ''}}</small>
              </li>
            </ul>
          </div>
        </div>
        <div class="column">
          <div class="columns">
            <div class="column">
              <h1 class="title">
                <a :href="game.url" target="_blank">{{game.name}}</a>
                <small class="subtitle">
                  {{formatReleaseDate(game.first_release_date)}}
                </small>
              </h1>
            </div>
          </div>
          <p class="summary">{{game.summary}}</p>
          <div class="tags margin-top-2" v-if="game.genres || game.themes">
            <span v-for="g in game.genres || []" :key="'g' + g.id" class="tag is-primary">{{g.name}}</span>
            <span v-for="t in game.themes || []" :key="'t' + t.id" class="tag is-info margin-right-1">{{t.name}}</span>
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
  name: 'game',
  data() {
    return {
    }
  },
  computed: {
    game() {
      return _.find(this.$store.state.user.games, g => g.slug == this.$route.params.slug);
    },
    releases() {
      let platforms = _.keyBy(this.$store.state.platforms, 'id');
      let regions = _.keyBy(this.$store.state.regions, 'id');
      return _.chain(this.game.release_dates)
        .map(rd => {
          return {
            date: rd.date,
            year: new Date(rd.date * 1000).getFullYear(),
            platform: platforms[rd.platform],
            region: regions[rd.region]
          };
        })
        .filter(x => !!x.platform)
        .sortBy('date')
        .value();
    }
  },
  methods: {
    formatReleaseDate(date) {
      return date ? new Date(date * 1000).getFullYear() : '';
    }
  }
}
</script>

<style lang="scss">
.summary {
  white-space: pre-wrap;
}
</style>

