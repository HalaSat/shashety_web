import Vue from 'vue';
import Vuex from 'vuex';
import movies from './modules/movies';
import series from './modules/series';
import channels from './modules/channels';
import tops from './modules/tops';
import actors from './modules/actors';
import reports from './modules/reports';
import subtitles from './modules/subtitles';
import users from './modules/users';
import admins from './modules/admins';
import support from './modules/support';
import categories from './modules/categories';
import discoverCategories from './modules/discover-categories';
import event from './modules/event';
Vue.use(Vuex);

export default new Vuex.Store({
    namespaced: true,
    strict: false,
    modules: {
        movies,
        series,
        channels,
        tops,
        actors,
        reports,
        subtitles,
        users,
        admins,
        support,
        categories,
        discoverCategories,
        event,
    }
});
