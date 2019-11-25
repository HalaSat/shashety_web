<template>
    <div class="categories">
        <div class="spinner-load" v-if="spinner_loading">
            <Loader></Loader>
        </div>

        <!-- END spinner load -->
        <div class="k1_manage_table" v-if="!spinner_loading">
            <h5 class="title p-2">Discover Categories</h5>

            <div class="table-responsive mt-2" v-if="data.categories !== null">
                <div class="table table-hover">
                    <thead>
                    <th>Name</th>
                    <th>Section</th>
                    <th>Created date</th>
                    <th>Updated date</th>
                    <th>Control</th>
                    </thead>
                    <tbody>
                    <tr v-for="(item, index) in data.categories" :key="index">
                        <td>{{item.name}}</td>
                        <td v-if="item.kind == 'movie'">Movies Page</td>
                        <td v-if="item.kind == 'series'">TV Show Page</td>
                        <td v-if="item.kind == 'kids'">Kids Page</td>
                        <td v-if="item.kind == 'live'">Live TV Page</td>
                        <td>{{item.created_at}}</td>
                        <td>{{item.updated_at}}</td>
                        <td>
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-danger btn-sm mr-2" @click="DELETE(item.id,index)" :id="item.id">
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </div>
            </div>
            <div v-else class="text-center">
                <h4>No result were found</h4>
            </div>

        </div>
    </div>
</template>

<script>
    const alertify = require("alertify.js");
    import { mapState } from "vuex";
    import Loader from "../components/loader";

    export default {
        data() {
            return {
                kind: 'movie'
            };
        },
        components: {
            Loader
        },
        computed: mapState({
            data: state => state.categories.data,
            button_loading: state => state.categories.button_loading,
            spinner_loading: state => state.categories.spinner_loading
        }),
        created() {
            this.$store.dispatch("GET_ALL_DISCOVER_CATEGORIES");
        },
        methods: {
            DELETE(id, key) {
                swal({
                    title: "Are you sure to delete?",
                    icon: "warning",
                    text: "All videos and subtitles will removed!",
                    buttons: true,
                    dangerMode: true
                }).then(willDelete => {
                    if (willDelete) {
                        this.$store.dispatch("DELETE_CATEGORY", {
                            id,
                            key
                        });
                    }
                });

        }
    } 
};
</script>
