<template>
    <div class="home-ghost">

        <div class="col-12 home-ghost__section-1 ">
            <nav class="navbar navbar-toggleable-md navbar-light bg-faded">
                <a class="navbar-brand" href="#">
                    <img src="/images/logo.png" alt="logo" width="60px">
                </a>

                <form class="form-inline my-2 my-lg-0">
                    <router-link class="hidden-xs-down mr-4" :to="{name: 'plan'}">START YOUR FREE TRIAL</router-link>
                    <router-link class="btn btn-secondary my-2 my-sm-0" :to="{name: 'login'}">LOGIN IN</router-link>
                </form>
            </nav>

            <div class="hidden-lg-down ovarlay-lg">
            </div>
            <div class="hidden-xl-up ovarlay-lg ovarlay-sm">
            </div>
            <div class="details">
                <h2>All Your TV In One Place</h2>
                <h4>
                    Watch thousands of shows and movies, with plans starting at $7.99/month.
                </h4>
                <router-link class="btn btn-lg btn-secondary mt-3" :to="{name: 'plan'}">START FREE TRAIL</router-link>
            </div>

        </div>
        <div class="col-12 home-ghost__section-2 ">

            <div class="row">


                <div class="col-sm-6 mt-5">
                    <div class="preview-image">
                        <div class="preview-animation">

                        </div>
                    </div>

                </div>
                <div class="col-sm-6 mt-5">

                    <div class="details">

                        <h1>
                            Watch Thousands of Shows and Movies<br> Anytime, Anywhere
                        </h1>

                    </div>

                </div>
            </div>


            <div class="col-12 mt-5 select-plan">

                <div class="text-center">
                    <h1>
                        Select Your Plan
                    </h1>
                    <h3> No contracts, commitments, or equipment rentals.</h3>
                </div>

                <div class="mt-5">
                <div class="col-sm-10 offset-sm-1">

                <div class="row">
                     <div class="col-12 col-sm-4 col mt-3 text-center card-columns-plan" v-for="(item, index) in planList" :key="index"  @click="plan = item.plan_id; NEXT()">
                            <div class="card-plan" :class="{active: plan === item.plan_id}">
                                <h3>{{item.plan_name}}</h3>
                                <h1>${{item.plan_amount /100}}
                                    <small>/mo</small>
                                </h1>
                                <i v-if="item.plan_trial !== null">{{item.plan_trial}} days free</i>
                            </div>
                        </div>
                    </div>
                </div>
                </div>

            </div>

        </div>


        <!-- END navbar -->


        <div class="col-12 mt-4 p-0">
            <div class="row">
                <footer id="mainFooter">
                   <div class="foot">Copyright © 2019 Shashety All rights reserved.</div>
                </footer>
            </div>
        </div>
    </div>

</template>

<script>
    import {mapState} from "vuex";

    export default {

        data() {
            return {
                plan: 0,
            }
        },

        computed: mapState({
            data: state => state.home.footer,
            planList: state => state.home.plan

        }),

        watch: {
            plan(val) {
                localStorage.setItem('_plan', this.plan);
            }
        },


        created() {
            this.$store.dispatch("GET_HOME_FOOTER_DETAILS");
            this.$store.dispatch("GET_HOME_PLAN");

        },

        methods: {
            NEXT() {
                localStorage.setItem('_plan', this.plan);
                this.$router.push({
                    name: 'signup'
                });
            },
            EXIT() {
                this.$router.push({name: 'home'});
            }
        }
    };
</script>
