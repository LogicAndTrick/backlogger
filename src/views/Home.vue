<template>
  <div class="home">
    
    <div v-if="loading" class="has-text-centered margin-vertical-4" key="L">
      <font-awesome-icon icon="spinner" size="3x" pulse></font-awesome-icon>
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
        
        <b-collapse :open="false">
          <h1 class="title is-6 margin-bottom-1" slot="trigger" slot-scope="props">
            Release Year
            <font-awesome-icon v-if="props.open" key="O" icon="chevron-up"></font-awesome-icon>
            <font-awesome-icon v-else key="C" icon="chevron-down"></font-awesome-icon>
          </h1>
          <div class="field" v-for="y in release_years" :key="y">
            <b-checkbox v-model="year" :native-value="y">{{y}}</b-checkbox>
          </div>
        </b-collapse>
        
        <b-collapse :open="false">
          <h1 class="title is-6 margin-bottom-1" slot="trigger" slot-scope="props">
            Status
            <font-awesome-icon v-if="props.open" key="O" icon="chevron-up"></font-awesome-icon>
            <font-awesome-icon v-else key="C" icon="chevron-down"></font-awesome-icon>
          </h1>
          <div class="field" v-for="s in statuses" :key="s">
            <b-checkbox v-model="status" :native-value="s">{{s}}</b-checkbox>
          </div>
        </b-collapse>

      </div>
      <div class="column">

        <div class="field is-grouped">
          <div class="control has-icons-left">
            <div class="select">
              <select v-model="sort">
                <option selected>Name</option>
                <option selected>Release year</option>
                <option selected>Status</option>
              </select>
            </div>
            <span class="icon is-left">
              <font-awesome-icon icon="sort-alpha-down"></font-awesome-icon>
            </span>
          </div>
          <div class="control">
            <button type="button" :class="'button ' + (size == 'mega' ? '' : 'is-text')" @click="size = 'mega'">
              <span class="icon is-small">
                <font-awesome-icon icon="square"></font-awesome-icon>
              </span> 
            </button>
          </div>
          <div class="control">
            <button type="button" :class="'button ' + (size == 'large' ? '' : 'is-text')" @click="size = 'large'">
              <span class="icon is-small">
                <font-awesome-icon icon="th-large"></font-awesome-icon>
              </span> 
            </button>
          </div>
          <div class="control">
            <button type="button" :class="'button ' + (size == 'small' ? '' : 'is-text')" @click="size = 'small'">
              <span class="icon is-small">
                <font-awesome-icon icon="th"></font-awesome-icon>
              </span> 
            </button>
          </div>
          <div class="control">
            <button type="button" :class="'button ' + (size == 'list' ? '' : 'is-text')" @click="size = 'list'">
              <span class="icon is-small">
                <font-awesome-icon icon="list"></font-awesome-icon>
              </span> 
            </button>
          </div>
        </div>
        
        <div :class="'grid size-' + size">

          <router-link
            :to="{ name: 'game', params: { user: user.name, slug: g.slug }}" class="grid-item" v-for="g in games" :key="g.id + ' ' + size"
            :style="getBackgroundImageStyle(g)"
          >
            <span class="cover">
              <img :src="getImageUrl(g)" alt="cover" />
              <span :class="'status ' + (g.status || 'Backlog').toLowerCase()">
                <font-awesome-icon v-if="iconForStatus(g.status)" :icon="iconForStatus(g.status)"></font-awesome-icon>
              </span>
              <span class="info">
                <span class="title is-3">{{g.name}}</span>
                <span class="title is-4">{{formatReleaseDate(g.first_release_date)}}</span>
              </span>
              <span v-if="g.hidden" class="hidden">
                Private
              </span>
            </span>
            <span class="details">
              <h3 class="title is-3">
                {{g.name}}
                <small class="subtitle">
                  {{g.status || 'Backlog'}} - {{formatReleaseDate(g.first_release_date)}}
                </small>
              </h3>
              <div class="tags" v-if="g.genres || g.themes">
                <span v-for="e in g.genres || []" :key="'e' + e.id" class="tag is-primary">{{e.name}}</span>
                <span v-for="t in g.themes || []" :key="'t' + t.id" class="tag is-info margin-right-1">{{t.name}}</span>
              </div>
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
      otherUser: null,
      sort: 'Status',
      size: 'large'
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
          return this.year.indexOf(new Date(g.first_release_date * 1000).getFullYear() || 'Unknown') >= 0;
        })
        .filter(g => {
          if (this.status.length == 0) return true;
          return this.status.indexOf(g.status || 'Backlog') >= 0;
        })
        .sortBy(x => (x.name || '').toLowerCase())
        .sortBy(x => {
          switch (this.sort) {
            case 'Release year':
              return new Date(x.first_release_date * 1000).getFullYear() || '9999';
            case 'Status':
              let sts = (x.status || 'Backlog');
              return this.statuses.indexOf(sts);
            default:
              return (x.name || '').toLowerCase();
          }
        })
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
        .map(x => new Date(x * 1000).getFullYear() || 'Unknown')
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
          return 'question';
        case 'Backlog':
          return '';
        case 'Playing':
          return 'play';
        case 'Completed':
          return 'check';
        case 'Stalled':
          return 'pause';
        case 'Dropped':
          return 'ban';
        case 'Up next':
          return 'play-circle';
      }
      return '';
    },
    getImageUrl(game) {
      let cover = game.custom_cover;
      if (!cover) {
        let img = game.cover && game.cover.image_id || 'nocover_qhhlj6';
        cover = `https://images.igdb.com/igdb/image/upload/t_cover_big/${img}.jpg`;
      } else {
        cover = this.$store.state.baseUrl + 'images/' + cover;
      }
      return cover;
    },
    getBackgroundImageStyle(game) {
      let cover = this.getImageUrl(game);
      return {
        'background-image': `url('${cover}')`
      };
    }
  },
  watch: {
    $route() {
      this.updateUser();
    },
    size() {
      return;
      let grids = this.$el.getElementsByClassName('grid-item');
      _.each(grids, g => g.classList.remove('grid-item'));
      this.$nextTick(() => _.each(grids, g => g.classList.add('grid-item')));
    }
  }
}
</script>

<style lang="scss" scoped>
$grid-padding: 5px;

.filter-column {
  min-width: 200px;

  .title > svg {
    float: right;
  }
}

.grid {
  display: flex;
  flex-flow: row wrap;
  margin: -$grid-padding;

  .grid-item {
    flex: 0 0 auto;
    margin: $grid-padding;
    display: flex;
    background-position: center;
    background-size: cover;
    background-repeat: no-repeat;
    position: relative;
    flex-flow: row nowrap;

    &::before {
      content: "";
      display: inline-block;
      width: 1px;
      height: 0;
      padding-bottom: 100%;
    }

    .details, .cover img {
      display: none;
    }
  }

  &.size-mega {
    .grid-item {
      @media (max-width: 2000px) { width: calc(100% / 3 - #{$grid-padding} * 2); }
      @media (max-width: 1500px) { width: calc(100% / 2 - #{$grid-padding} * 2); }
      @media (max-width: 1100px) { width: calc(100% / 1 - #{$grid-padding} * 2); }
    }
  }

  &.size-large {
    .grid-item {
      @media (max-width: 2000px) { width: calc(100% / 5 - #{$grid-padding} * 2); }
      @media (max-width: 1500px) { width: calc(100% / 4 - #{$grid-padding} * 2); }
      @media (max-width: 1100px) { width: calc(100% / 3 - #{$grid-padding} * 2); }
      @media (max-width:  800px) { width: calc(100% / 2 - #{$grid-padding} * 2); }
      @media (max-width:  600px) { width: calc(100% / 1 - #{$grid-padding} * 2); }
    }
  }

  &.size-small {
    .grid-item {
      @media (max-width: 2000px) { width: calc(100% / 7 - #{$grid-padding} * 2); }
      @media (max-width: 1500px) { width: calc(100% / 6 - #{$grid-padding} * 2); }
      @media (max-width: 1100px) { width: calc(100% / 5 - #{$grid-padding} * 2); }
      @media (max-width:  800px) { width: calc(100% / 4 - #{$grid-padding} * 2); }
      @media (max-width:  600px) { width: calc(100% / 3 - #{$grid-padding} * 2); }
    }
  }

  .status {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    padding: 40%;

    svg {
      width: 100%;
      height: 100%;
    }

    &.completed {
      background-color: rgba(0, 228, 30, 0.25);
      color: #49ff39;
    }

    &.playing {
      background-color: rgba(0, 2, 146, 0.25);
      color: #9db5ff;
    }

    &.tentative {
      background-color: rgba(128, 128, 128, 0.25);
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

    &.up.next {
      background-color: rgba(0, 112, 146, 0.25);
      color: #9dfcff;
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

  &.size-list {
    .grid-item {
      width: 100%;
      background-image: none !important;
      padding: 1em;
      border-radius: 0.5em;
      align-items: flex-start;

      background: rgba(0, 0, 0, 0.35);

      &:hover {
        background: rgba(0, 0, 0, 0.5);
      }
      
      &::before {
        display: none;
      }

      .info {
        display: none;
      }

      .cover {
        position: relative;
        margin-right: 2em;
        flex: 0 0 auto;
      }
      
      img {
        display: block;
        height: 100px;
        margin: 0 auto;
      }

      .details {
        display: block;
      }
    }
  }
}
</style>

