<template>
  <div class="game">

    <div v-if="loading" class="has-text-centered margin-vertical-4" key="L">
      <i class="fas fa-spinner fa-pulse fa-3x"></i>
    </div>
    <div v-else class="box margin-vertical-1" key="D">
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
          <h1 class="title">
            <a :href="game.url" target="_blank">{{game.name}}</a>
            <small class="subtitle">
              {{formatReleaseDate(game.first_release_date)}}
            </small>
          </h1>
          
          <p class="title is-4" v-if="saving">
            <span class="fas fa-spinner fa-pulse"></span> Please wait...
          </p>
          <div v-else-if="canEdit" class="field is-grouped">
            <div class="control select">
              <select v-model="status" @change="updateStatus">
                <option v-for="s in statuses" :value="s" :key="s">{{s}}</option>
              </select>
            </div>
            <div class="control">
              <button class="button is-danger" @click="deleteGame">Delete</button>
            </div>
            <div class="control">
              <label class="checkbox">
                <input type="checkbox" v-model="hidden" @change="updateStatus" />
                Don't show in public profile
              </label>
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
      status: null,
      hidden: false,
      saving: false,
      loading: true,
      otherUser: null
    }
  },
  mounted() {
    this.status = this.game && this.game.status || 'Backlog';
    this.hidden = this.game && this.game.hidden || false;
    this.updateUser();
  },
  computed: {
    user() {
      return this.otherUser || this.$store.state.user || { games: [] };
    },
    canEdit() {
      return !this.otherUser;
    },
    game() {
      return _.find(this.user.games, g => g.slug == this.$route.params.slug);
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
    },
    statuses() {
      return this.$store.state.statuses;
    }
  },
  methods: {
    updateUser() {
      this.loading = true;
      let login = this.$store.state.user;
      if (!login) {
        this.$router.push({ name: 'login' });
        return;
      }
      let view = this.$route.params.user || login.name;
      if (view === 'me') view = login.name;
      if (login.name != view) {
        methods.request(this.$store.state.baseUrl, 'user', {
          name: view
        }).then(user => {
          this.otherUser = user;
          this.loading = false;
        });
      } else {
        this.otherUser = null;
        this.loading = false;
      }
    },
    formatReleaseDate(date) {
      return date ? new Date(date * 1000).getFullYear() : '';
    },
    async deleteGame() {
      this.saving = true;

      await methods.request(this.$store.state.baseUrl, 'remove', {
        user_id: this.$store.state.user.id,
        game_id: this.game.id
      });

      this.$store.commit('remove', this.game.id);
      this.saving = false;
      this.$router.push({ name: 'home' });
    },
    async updateStatus() {
      this.saving = true;
      let result = await methods.request(this.$store.state.baseUrl, 'update', {
        user_id: this.$store.state.user.id,
        game_id: this.game.id,
        data: {
          status: this.status,
          hidden: this.hidden
        }
      });
      this.$store.commit('update', result);
      this.saving = false;
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
  .summary {
    white-space: pre-wrap;
  }
  .checkbox {
    padding-top: 0.6em;
  }
</style>

