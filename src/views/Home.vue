<template>
  <div class="home">
    
    <div v-if="loading" class="has-text-centered margin-vertical-4" key="L">
      <i class="fas fa-spinner fa-pulse fa-3x"></i>
    </div>
    <div v-else class="columns is-mobile" key="D">
      <div class="column is-narrow filter-column">
        
        <h1 class="title is-5">User: {{user.name}}</h1>

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
        
        <h1 class="title is-6">Status</h1>
        <div class="field" v-for="s in statuses" :key="s">
          <b-checkbox v-model="status" :native-value="s">{{s}}</b-checkbox>
        </div>

      </div>
      <div class="column">
        
        <div class="grid">

          <router-link
            :to="{ name: 'game', params: { user: user.name, slug: g.slug }}" class="grid-item" v-for="g in games" :key="g.id"
            :style="getBackgroundImageStyle(g)"
          >
            <span :class="'status ' + (g.status || 'Backlog').toLowerCase()">
              <span :class="iconForStatus(g.status)"></span>
            </span>
            <span class="info">
              <span class="title is-3">{{g.name}}</span>
              <span class="title is-4">{{formatReleaseDate(g.first_release_date)}}</span>
            </span>
            <span v-if="g.hidden" class="hidden">
              Private
            </span>
          </router-link>

        </div>

      </div>
    </div>

  </div>
</template>

<script>
import methods from "../methods";
import _ from 'lodash';

export default {
  name: 'home',
  data() {
    return {
      name: '',
      platform: [],
      year: [],
      status: _.filter(this.$store.state.statuses, x => x != 'Dropped'),
      loading: true,
      otherUser: null
    }
  },
  mounted() {
    this.updateUser();
  },
  computed: {
    user() {
      return this.otherUser || this.$store.state.user || { games: [] };
    },
    ignoredPlatforms() {
      return this.user.ignored_platforms || [];
    },
    all_games() {
      return this.user.games;
    },
    games() {
      return _.chain(this.all_games)
        .filter(g => {
          return !g.hidden || !this.otherUser;
        })
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
        .filter(g => {
          if (this.status.length == 0) return true;
          return this.status.indexOf(g.status || 'Backlog') >= 0;
        })
        .sortBy('name')
        .value();
    },
    platforms() {
      let platforms = this.$store.state.platforms;
      let gamePlatforms = _.chain(this.all_games)
        .map('release_dates')
        .flatten()
        .map('platform')
        .difference(this.ignoredPlatforms)
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
      let view = this.$route.params.user;
      if (!view) {
        this.$router.push({ name: 'home', params: { user: login.name } });
        return;
      }
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
    iconForStatus(status) {
      status = status || 'Backlog';
      switch (status) {
        case 'Tentative':
          return 'fas fa-question';
        case 'Backlog':
          return '';
        case 'Playing':
          return 'fas fa-play';
        case 'Completed':
          return 'fas fa-check';
        case 'Stalled':
          return 'fas fa-pause';
        case 'Dropped':
          return 'fas fa-ban';
      }
      return '';
    },
    getBackgroundImageStyle(game) {
      let cover = game.custom_cover;
      if (!cover) {
        let img = game.cover && game.cover.image_id || 'nocover_qhhlj6';
        cover = `https://images.igdb.com/igdb/image/upload/t_cover_big/${img}.jpg`;
      }
      return {
        'background-image': `url('${cover}')`
      };
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
$grid-padding: 5px;

.filter-column {
  min-width: 200px;
}

.grid {
  display: flex;
  flex-flow: row wrap;
  margin: -$grid-padding;

  .grid-item {
    flex: 0 0 auto;
    width: 250px;
    //height: 250px;
    margin: $grid-padding;
    display: flex;
    background-position: center;
    background-size: cover;
    background-repeat: no-repeat;
    position: relative;

    &::before {
      content: "";
      display: inline-block;
      width: 1px;
      height: 0;
      padding-bottom: 100%;
    }

    @media (max-width: 2000px) { width: calc(100% / 5 - #{$grid-padding} * 2); }
    @media (max-width: 1500px) { width: calc(100% / 4 - #{$grid-padding} * 2); }
    @media (max-width: 1100px) { width: calc(100% / 3 - #{$grid-padding} * 2); }
    @media (max-width:  800px) { width: calc(100% / 2 - #{$grid-padding} * 2); }
    @media (max-width:  600px) { width: calc(100% / 1 - #{$grid-padding} * 2); }
  }

  .status {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    padding: 20%;

    svg {
      width: 100%;
      height: 100%;
    }

    &.completed {
      background-color: rgba(0, 228, 30, 0.5);
      color: #49ff39;
    }

    &.playing {
      background-color: rgba(0, 2, 146, 0.5);
      color: #9db5ff;
    }

    &.tentative {
      background-color: rgba(128, 128, 128, 0.5);
      color: #ffea2d;
    }

    &.stalled {
      background-color: rgba(128, 128, 128, 0.5);
      color: #ed4dfc;
    }

    &.dropped {
      background-color: rgba(121, 0, 0, 0.5);
      color: #ff4747;
    }
  }

  .info {
    padding: 10px;
    text-align: center;

    background-color: rgba(0, 0, 0, 0.75);
    
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;

    opacity: 0;
    transition: opacity 0.2s ease;

    &:hover {
      opacity: 1;
    }

    .title {
      display: block;
      color: #4dace7;
      text-shadow: black 0px 0px 2px;
    }
  }

  .hidden { 
    position: absolute;
    top: auto;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(128, 0, 0, 0.6);
    color: white;
    text-align: center;
  }
}
</style>

