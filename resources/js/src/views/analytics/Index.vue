<!-- =========================================================================================
  File Name: AnalysticsList.vue
  Description: Analystics List page
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
========================================================================================== -->
<template>
    <div class="h-screen flex-col w-full">
         {{GetAllStatsProjects}}
        <div class="vx-card mx-auto p-8 mt-5 ">
            <!-- <div class="vx-row justify-around p-2 " style="width: 60%">
                <div class="col self-center">
                    <datepicker
                        placeholder="Période sélectionnée"
                        class="pickDate"
                        name="date"
                        :format="formatDatePicker"
                        :minimumView="'month'"
                        :maximumView="'year'"
                        v-model="filters.date">
                    </datepicker>
                </div>
                <div class="col self-center">
                    <simple-select
                        header=""
                        label="name"
                        placeholder="Statut du projet"
                        v-model="filters.status"
                        :reduce="item => item.key"
                        :options="statusOption"
                    />
                </div>
                <div class="col self-center">
                    <InfiniteSelect
                        header=""
                        label="name"
                        placeholder="Projet"
                        model="project"
                        v-model="filters.project_id"
                        @focus="clearRefreshDataTimeout"
                    />
                    <simple-select
                        header=""
                        label="name"
                        placeholder="Statut du projet"
                        v-model="filters.status"
                        :reduce="item => item.key"
                        :options="statusOption"
                    />
                </div>
                <div class="col self-center">
                    <feather-icon class="cursor-pointer"
                                  icon="SearchIcon"
                                  svgClasses="h-7 w-7"
                                  style="color: #000"
                    />
                </div>

            </div> -->
        </div>
        <div class=" mx-auto mt-5 flex">
            <vx-card class="analytics-container mr-4">
                <div class="vs-row flex mb-16">
                    <h6 class="text-dark">Statistiques</h6>
                </div>
                <vs-row class="flex-col mb-3">
                    <vs-col class="flex mb-20">
                        <vs-col class="mr-6" vs-type="flex" vs-justify="flex-start" vs-align="left" vs-w="5">
                            <feather-icon icon="CalendarIcon" svgClasses="h-6 w-6" class="mr-3"/>
                            <div class="flex-col">
                                <div class="flex items-center">
                                    <span class="text-dark font-bold text-xs">
                                        {{ estimatedTime }}h

                                    </span>
                                </div>
                                <p class="text-dark text-xs">Plannifiées</p>
                            </div>
                        </vs-col>
                        <vs-col vs-type="flex" vs-justify="flex-start" vs-align="left" vs-w="5">
                            <feather-icon icon="CalendarIcon" svgClasses="h-6 w-6" class="mr-3"/>
                            <div class="flex-col">
                                <div class="flex items-center">
                                    <span class="text-dark font-bold text-xs">
                                        {{ achievedTime }}h
                                    </span>
                                </div>
                                <p class="text-dark text-xs">Effectuées</p>
                            </div>
                        </vs-col>
                    </vs-col>
                    <vs-col class="flex ">
                        <vs-col
                            vs-type="flex"
                            vs-justify="flex-start"
                            vs-align="center"
                            vs-w="12"
                        >
                            <div id="color-background " class="mr-3 rounded-full">
                                <feather-icon
                                    icon="BarChart2Icon"
                                    v-if="diffTimeData === '0'"
                                    class="rounded-full bg-warning text-white"
                                    style="background-color: orange; padding: 0.4rem"
                                    svgClasses="h-6 w-6"
                                />
                                <feather-icon
                                    icon="BarChart2Icon"
                                    v-if="diffTimeData >= '0'"
                                    class="rounded-full bg-success text-white"
                                    style="background-color: yellowgreen; padding: 0.4rem; "
                                    svgClasses="h-6 w-6"
                                />
                                <feather-icon
                                    icon="BarChart2Icon"
                                    v-if="diffTimeData <= '0'"
                                    class="rounded-full bg-danger text-white"
                                    style="background-color: orangered; padding: 0.4rem"
                                    svgClasses="h-6 w-6"
                                />
                            </div>
                            <div class="flex-col">
                                <div class="flex items-center">
                                    <span class="text-dark font-bold text-xs"> {{ diffTimeData() }} h </span>
                                </div>
                                <p class="text-dark text-xs">D'écart</p>
                            </div>
                        </vs-col>
                        <vs-col
                            vs-type="flex"
                            vs-justify="flex-start"
                            vs-align="center"
                            vs-w="12"
                        >
                            <div class="color-background mr-3 rounded-full">
                                <feather-icon
                                    icon="BarChart2Icon"
                                    v-if="diffBudgetData === '0'"
                                    class="rounded-full bg-warning text-white"
                                    style="background-color: orange; padding: 0.4rem"
                                    svgClasses="h-6 w-6"
                                />
                                <feather-icon
                                    icon="BarChart2Icon"
                                    v-if="diffBudgetData >= '0'"
                                    class="rounded-full bg-success text-white"
                                    style="background-color: yellowgreen; padding: 0.4rem; "
                                    svgClasses="h-6 w-6"
                                />
                                <feather-icon
                                    icon="BarChart2Icon"
                                    v-if="diffBudgetData <= '0'"
                                    class="rounded-full bg-danger text-white"
                                    style="background-color: orangered; padding: 0.4rem"
                                    svgClasses="h-6 w-6"
                                />
                            </div>
                            <div class="flex-col">
                                <div class="flex items-center">
                                    <span class="text-dark font-bold text-xs"> {{ diffBudgetData() }} €</span>
                                </div>
                                <p class="text-dark text-xs">D'écart</p>
                            </div>
                        </vs-col>
                    </vs-col>
                </vs-row>
            </vx-card>
            <vx-card class="analytics-container mr-4">
                <div class="vs-row flex mb-6">
                    <h6 class="text-dark">heures par projet</h6>
                </div>
                <vs-row class="flex-col mb-3">
                    <div class="HoursByProject">
                        <vue-apex-charts class="HoursProjectFilled" ref="donut1" width="260" type="donut"
                                         :options="chartOptions1" :series="chartSeries1"></vue-apex-charts>
                        <div class="HoursProjectEmpty"> il n'y a pas de projet correspondant aux filtres</div>
                    </div>
                </vs-row>
            </vx-card>
            <!-- <vx-card class="analytics-container mr-4">
                <div class="vs-row flex mb-6">
                    <h6 class="text-dark">Heures par opérateur</h6>
                </div>
                <vs-row class="flex-col mb3">
                    <div class="HoursByOperate">
                        <vue-apex-charts class="HoursOperateFilled" ref="donut2" width="260" type="donut"
                                         :options="chartOptions2" :series="HoursUser"></vue-apex-charts>
                        <div class="HoursOperateEmpty"> il n'y a pas d'utilisateurs correspondant aux filtres</div>
                    </div>
                </vs-row>
            </vx-card>
            <vx-card class="analytics-container ">
                <div class="vs-row flex mb-6">
                    <h6 class="text-dark">Heures par pôle de production</h6>
                </div>
                <vs-row class="flex-col mb-3">
                    <div class="HoursByProd">
                        <vue-apex-charts ref="donut" width="260" type="donut" :options="chartOptions"
                                         :series="HoursWorkAreas()"></vue-apex-charts>
                    </div>
                </vs-row>
            </vx-card> -->
        </div>
    </div>
</template>

<script>
import VueApexCharts from "vue-apexcharts";
import Datepicker from "vuejs-datepicker";
import moment from "moment";
import vSelect from 'vue-select'
//Components
import SimpleSelect from "@/components/inputs/selects/SimpleSelect";
import InfiniteSelect from "@/components/inputs/selects/InfiniteSelect";

//Store Module
import moduleProjectManagement from "@/store/project-management/moduleProjectManagement.js";
import moduleUserManagement from "@/store/user-management/moduleUserManagement.js";
import moduleHoursManagement from "@/store/hours-management/moduleHoursManagement";
import moduleDealingHoursManagement from "@/store/dealing-hours-management/moduleDealingHoursManagement";
import moduleWorkareaManagement from "@/store/workarea-management/moduleWorkareaManagement";


import {fr} from "vuejs-datepicker/dist/locale";
import StatisticsCardLine from "../../components/statistics-cards/StatisticsCardLine";
import _, {words} from "lodash";
import IndexTasks from "./../tasks/Index.vue";


export default {
    components: {
        StatisticsCardLine,
        Datepicker,
        VueApexCharts,
        InfiniteSelect,
        SimpleSelect,
        IndexTasks,
        'v-select': vSelect,
        name: 'DonutExample',

    },
    data() {
        return {
            searchQuery: "",
            perPage: this.$router.history.current.query.perPage || 50,
            langFr: fr,
            stats: {total: 0},

            statusOption: [
                {key: null, name: "-"},
                {key: "todo", name: "À faire"},
                {key: "doing", name: "En cours"},
                {key: "waiting", name: "Terminé, en attente de livraison"},
                {key: "done", name: "Livré"}
            ],

            chartOptions: {
                labels: [],
                dataLabels: {
                    enabled: true,
                    formatter: function (val) {
                        return val.toFixed(2) + "%"
                    },
                },
                legend: {show: false},
            },
            series: [20, 20],

            chartSeries1: [],
            chartOptions1: {
                labels: [],
                dataLabels: {
                    enabled: true,
                    formatter: function (val) {
                        return val.toFixed(2) + "%"
                    },
                },
                legend: {show: false},
            },
            series1: [20, 20],

            chartOptions2: {
                labels: [],
                dataLabels: {
                    enabled: true,
                    formatter: function (val) {
                        return val.toFixed(2) + "%"
                    },
                },
                legend: {show: false},
            },
            series2: [20, 20],

            estimatedTime: 0,
            achievedTime: 0,

            filters: {
                status: null,
                project_id: null,
                date: null,
                formatDate: "month, year"
            }
        }
    },
    watch: {
        filterParams: {
            handler(value, prev) {
                if (!_.isEqual(value, prev)) {
                    this.clearRefreshDataTimeout();
                    this.refreshDataTimeout = setTimeout(() => {
                        this.page = 1;
                        this.refreshData();
                    }, 1500);
                }
            },
        },
        filters: {
            handler(val, prev) {
                this.fetchProjects();
                this.fetchHours();
            },
            deep: true
        }
    },
    computed: {
        formatDatePicker() {
            return this.filters.formatDate === "year" ? "yyyy" : "MMM yyyy";
        },
        itemIdToEdit() {
            return (
                this.$store.getters["hoursManagement/getSelectedItem"].id || 0
            );
        },
        filterParams() {
            const filter = {};

            if (this.filters.project_id) {
                filter.project_id = this.filters.project_id;
            }

            if (this.filters.user_id) {
                filter.user_id = this.filters.user_id;
            }
            if (this.filters.date) {
                filter.date = moment(this.filters.date).format("DD-MM-YYYY");
                if (this.isPeriodFilter) {
                    filter.period_type = this.filters.period_type;
                }
            }
            return filter;
        },
        estimatedTimeData() {
            let time = 0;
            if (this.projectsData()) {
                this.projectsData().map(row => {
                    if (row.tasks) {
                        row.tasks.map(data => {
                            time += data.estimated_time
                        })
                    }
                })
            }
            return time;
        },
        achievedTimeData() {
            let time = 0;
            if (this.projectsData()) {
                this.projectsData().map(row => {
                    if (row.tasks) {
                        if (row.tasks.length === 0) {
                            time += 0
                        } else {
                            row.tasks.map(data => {
                                time += data.time_spent
                            })
                        }
                    }
                })
            }
            // this.NamesProjects
            this.NamesUsers
            return time;
        },
        // NamesProjects() {
        //     let names = [];
        //     if (this.projectsData()) {
        //         this.projectsData().map(row => {
        //             names[row.id] = row.name
        //         })
        //     }
        //     names = names.filter(Boolean)
        //     if (this.$refs.donut1) {
        //         this.$refs.donut1.updateOptions({labels: names});
        //     }
        //     return names;
        // },
        NamesUsers() {
            let names = [];
            if (this.hoursData()) {
                this.hoursData().map(row => {
                    let map = new Map(Object.entries(row.user))
                    names[map.get("id")] = map.get("firstname")
                })
            }
            names = names.filter(Boolean)
            if (this.$refs.donut2 != null) {
                this.$refs.donut2.updateOptions({labels: names})
            }
            return names
        },

        // HoursProject() {
        //     var hour = [];
        //     if (this.projectsData()) {
        //         this.projectsData().map(row => {
        //             if (this.filters.status === null && this.filters.date === null && this.filters.project_id === null) {
        //                 if (typeof hour[row.id] === 'undefined') {
        //                     hour[row.id] = 0
        //                 }

        //                 if (row.progress) {
        //                     hour[row.id] = row.progress.nb_task_time_done
        //                 }
        //             }
        //             if (this.filters.status !== null) {
        //                 const result = this.projectsData().filter(row => {
        //                     return row.status.match(this.filters)
        //                 })
        //                 result.map(row => {
        //                     if (typeof hour[row.id] === 'undefined') {
        //                         hour[row.id] = 0
        //                     }
        //                     if (row.progress) {
        //                         hour[row.id] += row.progress.nb_task_time_done
        //                     }

        //                 })
        //             }
        //             if (this.filters.date !== null) {
        //                 const formatDateFilter = (date) => {
        //                     let formatted_date = date.getFullYear() + "-"
        //                         + ("0" + (date.getMonth() + 1)).slice(-2)
        //                     return formatted_date;
        //                 }
        //                 const formatDateProject = (date) => {
        //                     let formatted_date = date.split(" ").splice(0, 1).join("")
        //                     formatted_date = formatted_date.split("-").splice(0, 2).join("-")
        //                     return formatted_date
        //                 }
        //                 const result = this.projectsData().filter(row => {
        //                     return formatDateProject(row.date).match(formatDateFilter(this.filters.date))
        //                 })
        //                 if (result.length === 0) {
        //                     hour[row.id] = 0
        //                 } else {
        //                     result.map(row => {
        //                         if (typeof hour[row.id] === 'undefined') {
        //                             hour[row.id] = 0
        //                         }
        //                         if (row.progress) {
        //                             hour[row.id] += row.progress.nb_task_time_done
        //                         }
        //                     })
        //                 }
        //             }
        //             if (this.filters.project_id !== null) {
        //                 console.log(this.filters.project_id)
        //             }
        //         })
        //     }


        //     if (hour.filter(Boolean).length === 0) {
        //         for (const el of document.getElementsByClassName('HoursProjectEmpty')) {
        //             el.style.display = "inline-block"
        //         }
        //         for (const el of document.getElementsByClassName('HoursProjectFilled')) {
        //             el.style.display = "none"
        //         }
        //     } else {
        //         for (const el of document.getElementsByClassName('HoursProjectEmpty')) {
        //             el.style.display = "none"
        //         }
        //         for (const el of document.getElementsByClassName('HoursProjectFilled')) {
        //             el.style.display = "inline-block"
        //         }
        //         return hour.filter(Boolean)
        //     }
        // },
         GetAllStatsProjects () {
            const hours = []
            const names = []
            let estimatedTime = 0
            let achievedTime = 0

            if (this.projectsData()) {
                this.projectsData().map(row => {
                    if (row.progress.nb_task_time_done) {
                        hours[row.id] = row.progress.nb_task_time_done     
                        names[row.id] = row.name     
                        estimatedTime += row.progress.nb_task_time  
                    }
                    if (row.progress.nb_task_time_done) {
                        achievedTime += row.progress.nb_task_time_done
                    }
                })
            }

            this.estimatedTime = estimatedTime
            this.achievedTime = achievedTime
            
            if (this.$refs.donut1) {
                this.$refs.donut1.updateSeries(hours.filter(Boolean))
                this.$refs.donut1.updateOptions({labels: names.filter(Boolean)})
            }

            if (hours.filter(Boolean).length === 0) {
                for (const el of document.getElementsByClassName('HoursProjectEmpty')) {
                    el.style.display = "inline-block"
                }
                for (const el of document.getElementsByClassName('HoursProjectFilled')) {
                    el.style.display = "none"
                }
            } else {
                for (const el of document.getElementsByClassName('HoursProjectEmpty')) {
                    el.style.display = "none"
                }
                for (const el of document.getElementsByClassName('HoursProjectFilled')) {
                    el.style.display = "inline-block"
                }
            }

            return ''
        },
        HoursUser() {
            var hour = [];
            if (this.hoursData()) {
                this.hoursData().map(row => {
                    if (this.filters.status === null && this.filters.date === null && this.filters.project_id === null) {
                        if (typeof hour[row.user_id] === 'undefined') {
                            hour[row.user_id] = 0
                        }
                        hour[row.user_id] += row.durationInFloatHour
                    }
                    if (this.filters.status !== null) {
                        const result = this.hoursData().filter(row => {
                            return row.project.status.match(this.filters.status)
                        })
                        if (result.length === 0) {
                            hour = []
                        } else {
                            result.map(row => {
                                if (typeof hour[row.id] === 'undefined') {
                                    hour[row.user_id] = 0
                                }
                                hour[row.user_id] += row.durationInFloatHour
                            })
                        }
                    }
                    if (this.filters.date !== null) {
                        const formatDateFilter = (date) => {
                            let formatted_date = date.getFullYear() + "-"
                                + ("0" + (date.getMonth() + 1)).slice(-2)
                            return formatted_date;
                        }
                        const formatDateProject = (date) => {
                            let formatted_date = date.split(" ").splice(0, 1).join("")
                            formatted_date = formatted_date.split("-").splice(0, 2).join("-")
                            return formatted_date
                        }
                        const result = this.hoursData().filter(row => {
                            return formatDateProject(row.project.date).match(formatDateFilter(this.filters.date))
                        })
                        if (result.length === 0) {
                            hour[row.user_id] = 0
                        } else {
                            result.map(row => {
                                if (typeof hour[row.user_id] === 'undefined') {
                                    hour[row.user_id] = 0
                                }
                                hour[row.user_id] = row.durationInFloatHour
                            })
                        }
                    }
                })
            }
            if (hour.filter(Boolean).length === 0) {
                for (const el of document.getElementsByClassName('HoursOperateEmpty')) {
                    el.style.display = "inline-block"
                }
                for (const el of document.getElementsByClassName('HoursOperateFilled')) {
                    el.style.display = "none"
                }
            } else {
                for (const el of document.getElementsByClassName('HoursOperateEmpty')) {
                    el.style.display = "none"
                }
                for (const el of document.getElementsByClassName('HoursOperateFilled')) {
                    el.style.display = "inline-block"
                }
                return hour.filter(Boolean)
            }
        }
    },
    methods: {
        userData() {
            if (!this.$store.state.userManagement) {
                return [];
            }
            return this.$store.getters["userManagement/getItems"];
        },
        hoursData() {
            if (!this.$store.state.hoursManagement) {
                return [];
            }
            return this.$store.getters["hoursManagement/getItems"];
        },
        projectsData() {
            if (!this.$store.state.projectManagement) {
                return [];
            }
            return this.$store.getters["projectManagement/getItems"]
        },
        WorkAreasData() {
            if (!this.$store.state.workAreaManagement) {
                return [];
            }
            return this.$store.getters["workAreaManagement/getItems"];
        },
        diffTimeData() {
            let time = 0;
            if (this.achievedTime && this.estimatedTime) {
                time = (this.estimatedTime - this.achievedTime)
            }
            return time
        },
        diffBudgetData() {
            let budget = 0;
            if (this.diffTimeData()) {
                budget = (this.diffTimeData() * '55')
            }
            return budget
        },
        HoursWorkAreas() {
            var hour = [];
            if (this.hoursData()) {
                this.hoursData().map(row => {
                    //console.log(row)
                    //console.log(this.WorkAreasData())
                })
            }
        },
        refreshData() {
            this.$vs.loading();
            // refresh Hours
            this.$store
                .dispatch("hoursManagement/fetchItems", {
                    ...this.filterParams,
                    page: this.currentPage,
                    per_page: this.perPage,
                    q: this.searchQuery || undefined,
                    order_by: "start_at",
                    order_by_desc: 1
                })
                .then(data => {
                    this.stats = data.stats;

                    if (data.pagination) {
                        const {total, last_page} = data.pagination;
                        this.totalPages = last_page;
                        this.total = total;
                    }
                })
                .catch(error => {
                    this.$vs.notify({
                        title: "Erreur",
                        text: error.message,
                        iconPack: "feather",
                        icon: "icon-alert-circle",
                        color: "danger"
                    });
                })
                .finally(() => this.$vs.loading.close());
        },
        clearRefreshDataTimeout() {
            if (this.refreshDataTimeout) {
                clearTimeout(this.refreshDataTimeout);
            }
        },
        onPageChanged() {
            this.fetchProjects();
        },
        fetchProjects() {
            const that = this;
            this.$vs.loading();

            let month = null;
            let year = null;
            if (this.filters.date && this.filters.formatDate === "year") {
                year = moment(this.filters.date).format("YYYY");
            }
            if (this.filters.date && this.filters.formatDate === "month") {
                year = moment(this.filters.date).format("YYYY");
                month = moment(this.filters.date).format("M");
            }

            this.$store
                .dispatch("projectManagement/fetchItems", {
                    page: this.page,
                    per_page: this.perPage,
                    q: this.searchQuery || undefined,
                    company_id: this.filters.company_id || undefined,
                    customer_id: this.filters.customer_id || undefined,
                    status: this.filters.status || undefined,
                    year: year || undefined,
                    month: month || undefined,
                    hours: ["hours"],
                    tasks: ["tasks"],
                    deleted_at: this.filters.deleted_at || undefined,
                    order_by: "status"
                })
                .then(data => {
                    that.projectsLoaded = true;
                    this.projects_data = data.payload;


                    if (data.pagination) {
                        const {total, last_page} = data.pagination;
                        this.totalPages = last_page;
                        this.total = total;
                    }

                    that.$vs.loading.close();
                })
                .catch(err => {
                    console.error(err);
                });
        },
        fetchHours() {
            this.$store.dispatch("hoursManagement/fetchItems")
                .then((data) => {
                })
                .catch(err => {
                    console.error(err);
                });
        },
        fetchWorkAreas() {
            this.$store.dispatch("workAreaManagement/fetchItems")
                .then((data) => {
                    this.workAreas_data = data.payload;
                })
                .catch(err => {
                    console.error(err);
                })
        }
    },
    created() {
        if (!moduleUserManagement.isRegistered) {
            this.$store.registerModule("userManagement", moduleUserManagement);
            moduleUserManagement.isRegistered = true;
        }
        if (!moduleProjectManagement.isRegistered) {
            this.$store.registerModule(
                "projectManagement",
                moduleProjectManagement
            );
            moduleProjectManagement.isRegistered = true;
        }
        if (!moduleHoursManagement.isRegistered) {
            this.$store.registerModule(
                "hoursManagement",
                moduleHoursManagement
            );
            moduleHoursManagement.isRegistered = true;
        }
        if (!moduleDealingHoursManagement.isRegistered) {
            this.$store.registerModule(
                "dealingHoursManagement",
                moduleDealingHoursManagement
            );
            moduleDealingHoursManagement.isRegistered = true;
        }
        if (!moduleWorkareaManagement.isRegistered) {
            this.$store.registerModule(
                "workAreaManagement",
                moduleWorkareaManagement
            );
            moduleWorkareaManagement.isRegistered = true;
        }
        moment.locale("fr");
        this.fetchProjects();
        this.fetchHours();
        this.fetchWorkAreas();
        this.projectsData();
        this.hoursData();
    },
    beforeDestroy() {
        moduleUserManagement.isRegistered = false;
        this.$store.unregisterModule("userManagement");
        moduleProjectManagement.isRegistered = false;
        this.$store.unregisterModule("projectManagement");
        moduleHoursManagement.isRegistered = false;
        this.$store.unregisterModule("hoursManagement");
        moduleDealingHoursManagement.isRegistered = false;
        this.$store.unregisterModule("dealingHoursManagement");
        moduleWorkareaManagement.isRegistered = false;
        this.$store.unregisterModule("workAreaManagement");
    }
}
</script>

<style>

</style>
