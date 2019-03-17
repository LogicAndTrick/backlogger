<template>
  <div class="game">

    <div v-if="loading" class="has-text-centered margin-vertical-4" key="L">

      <font-awesome-icon icon="spinner" size="3x" pulse></font-awesome-icon>

    </div>
    <form v-else-if="editing" class="box margin-vertical-1" enctype="multipart/form-data" method="post" key="E" @submit.prevent="saveEdit">
      
      <div v-if="saving" class="has-text-centered margin-vertical-4" key="S">
        <font-awesome-icon icon="spinner" size="3x" pulse></font-awesome-icon>
      </div>
      <div v-else key="ED">
        <div class="field is-horizontal">
          <div class="field-label is-normal">
            <label class="label">Name</label>
          </div>
          <div class="field-body">
            <div class="field">
              <p class="control">
                <input class="input" type="text" name="name" placeholder="Name" :value="game.name">
              </p>
            </div>
          </div>
        </div>

        <div class="field is-horizontal">
          <div class="field-label is-normal">
            <label class="label">Summary</label>
          </div>
          <div class="field-body">
            <div class="field">
              <div class="control">
                <textarea class="textarea" name="summary">{{game.summary}}</textarea>
              </div>
            </div>
          </div>
        </div>

        <div class="field is-horizontal">
          <div class="field-label is-normal">
            <label class="label">Cover (upload)</label>
          </div>
          <div class="field-body">
            <div class="field">
              <div class="control">
                <div class="file has-name is-fullwidth">
                  <label class="file-label">
                    <input class="file-input" type="file" name="cover" ref="filename" @change="updateFilename" @input="checkIfYouAreAnIdiot">
                    <span class="file-cta">
                      <span class="file-icon">
                        <font-awesome-icon icon="upload"></font-awesome-icon>
                      </span>
                      <span class="file-label">
                        Choose a file…
                      </span>
                    </span>
                    <span class="file-name">
                      Leave blank to leave unchanged
                    </span>
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="field is-horizontal">
          <div class="field-label is-normal">
            <label class="label">Cover (url)</label>
          </div>
          <div class="field-body">
            <div class="field">
              <div class="control">
                <input class="input" type="text" name="cover_url" ref="fileurl" placeholder="Leave blank to leave unchanged" @input="checkIfYouAreAnIdiot">
              </div>
              <p class="help" v-if="youAreAnIdiot">Don't try and set both an upload and a url cover, idiot</p>
            </div>
          </div>
        </div>

        <div class="field is-horizontal">
          <div class="field-label">
            <!-- Left empty for spacing -->
          </div>
          <div class="field-body">
            <div class="field is-grouped">
              <div class="control">
                <button class="button is-primary" type="submit">Save</button>
              </div>
              <div class="control">
                <button class="button is-text" type="button" @click="editing = false">Cancel</button>
              </div>
            </div>
          </div>
        </div>
      </div>

    </form>
    <div v-else class="box margin-vertical-1" key="D">

      <div class="columns">
        <div class="column is-one-quarter" v-if="coverImage">
          <figure class="image">
            <img :src="coverImage" />
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
            <font-awesome-icon icon="spinner" pulse></font-awesome-icon> Please wait...
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
            <div class="control is-expanded"></div>
            <div>
              <button type="button" class="button is-success is-small" @click="editing = true">Edit</button>
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
      otherUser: null,
      editing: false,
      youAreAnIdiot: false
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
    coverImage() {
      let cover = this.game.custom_cover;
      if (!cover) {
        let img = this.game.cover && this.game.cover.image_id || 'nocover_qhhlj6';
        cover = `https://images.igdb.com/igdb/image/upload/t_cover_big/${img}.jpg`;
      } else {
        cover = this.$store.state.baseUrl + 'images/' + cover;
      }
      return cover;
    },
    releases() {
      let platforms = _.keyBy(this.$store.state.platforms, 'id');
      let regions = _.keyBy(this.$store.state.regions, 'id');
      return _.chain(this.game.release_dates)
        .map(rd => {
          return {
            date: rd.date,
            year: new Date(rd.date * 1000).getFullYear() || 'Unknown',
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
    },
    updateFilename() {
      if (!this.$refs.filename) return;
      let fname = this.$refs.filename.files.length > 0
        ? this.$refs.filename.files[0].name
        : 'Leave blank to leave unchanged';
      this.$el.getElementsByClassName('file-name')[0].innerHTML = fname;
    },
    async saveEdit() {
      this.saving = true;
      let fd = new FormData(this.$el.getElementsByTagName('form')[0]);
      fd.append('user_id', this.$store.state.user.id);
      fd.append('game_id', this.game.id);
      let result = await methods.upload(this.$store.state.baseUrl, 'edit', fd);
      this.$store.commit('update', result);
      this.saving = false;
      this.editing = false;
    },
    checkIfYouAreAnIdiot() {
      let fn = this.$refs.filename;
      let fu = this.$refs.fileurl;
      this.youAreAnIdiot = fn && fu && fn.files.length > 0 && fu.value.length > 0;
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
  .file-cta {
    border-color: #dfd4b1;
  }
  .file-name {
    background-color: #FDF6E3;
    border-color: #dfd4b1;
    color: #839496;
  }
  .file-label:hover .file-name {
    border-color: #dfd4b1;
  }
</style>

