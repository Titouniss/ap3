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
        {{ GetAllStatsUsers }}
        {{ GetAllStatsProjects }}
        <div class="vx-card mx-auto p-8 mt-5 ">
            <div class="vx-row justify-around p-2 " style="width: 60%">
                <div class="col self-center">
                    <small class="small-radio-circle">
                        <vs-radio
                            class="date-label"
                            v-model="filters.formatDate"
                            vs-value="month"
                        >
                            Mois
                        </vs-radio>
                        <vs-radio
                            class="small-radio-circle"
                            v-model="filters.formatDate"
                            vs-value="year"
                        >
                            Année
                        </vs-radio>
                    </small>
                    <datepicker
                        placeholder="Date de livraison"
                        class="pickadate"
                        name="date"
                        :format="formatDatePicker"
                        :language="langFr"
                        :minimumView="filters.formatDate"
                        :maximumView="'year'"
                        :initialView="filters.formatDate"
                        v-model="filters.date"
                    >
                    </datepicker>
                </div>
                <div class="col self-center">
                    <simple-select
                        header="statut"
                        label="name"
                        v-model="filters.status"
                        :reduce="item => item.key"
                        :options="statusOption"
                    />
                </div>
                <div class="col self-center">
                    <infinite-select
                        header="Projet"
                        model="project"
                        label="name"
                        v-model="filters.project_id"
                        @focus="clearRefreshDataTimeout"
                    />
                </div>
            </div>
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
                                <p class="text-dark text-xs">Estimées</p>
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
                    <h6 class="text-dark HoursProject">heures par projet</h6>
                </div>
                <vs-row class="flex-col mb-3">
                    <div class="HoursByProject">
                        <vue-apex-charts class="HoursProjectFilled" ref="donut1" width="260" type="donut"
                                         :options="chartOptions1" :series="chartSeries1"></vue-apex-charts>
                        <div class="HoursProjectEmpty"> il n'y a pas de projet correspondant aux filtres</div>
                    </div>
                </vs-row>
            </vx-card>
            <vx-card class="analytics-container mr-4">
                <div class="vs-row flex mb-6">
                    <h6 class="text-dark">Heures par opérateur</h6>
                </div>
                <vs-row class="flex-col mb3">
                    <div class="HoursByOperate">
                        <vue-apex-charts class="HoursOperateFilled" ref="donut2" width="260" type="donut"
                                         :options="chartOptions2" :series="chartSeries2"></vue-apex-charts>
                        <div class="HoursOperateEmpty"> il n'y a pas d'utilisateurs correspondant aux filtres</div>
                    </div>
                </vs-row>
            </vx-card>
            <!--<vx-card class="analytics-container ">
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
        <div class="vx-card w-full mt-5 p-6">
            <!-- AgGrid Table -->
            <ag-grid-vue
                ref="agGridTable"
                :components="components"
                :gridOptions="gridOptions"
                class="ag-theme-material w-100 my-4 ag-grid-table"
                overlayLoadingTemplate="Chargement..."
                :columnDefs="columnDefs"
                :defaultColDef="defaultColDef"
                :rowData="projectsData, hoursData"
                rowSelection="multiple"
                colResizeDefault="shift"
                :animateRows="true"
                :floatingFilter="false"
                :enableRtl="$vs.rtl"
            ></ag-grid-vue>

        </div>
    </div>
</template>

<script>
import VueApexCharts from "vue-apexcharts";
import Datepicker from "vuejs-datepicker";
import moment from "moment";
import vSelect from 'vue-select'
//Components
import "@sass/vuexy/extraComponents/agGridStyleOverride.scss";
import SimpleSelect from "@/components/inputs/selects/SimpleSelect";
import InfiniteSelect from "@/components/inputs/selects/InfiniteSelect";
//Store Module
import moduleProjectManagement from "@/store/project-management/moduleProjectManagement.js";
import moduleHoursManagement from "@/store/hours-management/moduleHoursManagement";
import moduleDealingHoursManagement from "@/store/dealing-hours-management/moduleDealingHoursManagement";
import moduleWorkareaManagement from "@/store/workarea-management/moduleWorkareaManagement";


import {AgGridVue} from "ag-grid-vue";
import {fr} from "vuejs-datepicker/dist/locale";
import StatisticsCardLine from "../../components/statistics-cards/StatisticsCardLine";
import _, {words} from "lodash";
import IndexTasks from "./../tasks/Index.vue";
//cell-renderer
import CellRendererRelations from "./cell-renderer/CellRendererRelations.vue";
import CellRendererStatus from "./cell-renderer/CellRendererStatus.vue";
import CellRendererActions from "@/components/cell-renderer/CellRendererActions.vue";
import CellRendererLink from "./cell-renderer/CellRendererLink.vue";

export default {
    components: {
        StatisticsCardLine,
        Datepicker,
        VueApexCharts,
        AgGridVue,
        InfiniteSelect,
        SimpleSelect,
        IndexTasks,
        'v-select': vSelect,
        name: 'DonutExample',

        // Cell Renderer
        CellRendererActions,
        CellRendererLink,
        CellRendererRelations,
        CellRendererStatus,

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

            chartSeries2: [],
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

            // AgGrid
            gridApi: null,
            gridOptions: {
                localeText: {noRowsToShow: "Aucun projet à afficher"}
            },
            defaultColDef: {
                sortable: true,
                resizable: true,
                suppressMenu: true
            },
            columnDefs: [
                {
                    headerName: "Nom",
                    field: "name",
                    filter: true,
                    cellRendererFramework: "CellRendererLink"
                },
                {
                    headerName: "Avancement",
                    field: "status",
                    cellRendererFramework: "CellRendererStatus"
                },
                {
                    headerName: "Temps Estimé",
                    field: "estimated time",
                    filter: true,
                    cellRendererFramework: "CellRendererRelations",
                },
                {
                    headerName: "Temps réel",
                    field: "achieved time",
                    filter: true,
                    cellRendererFramework: "CellRendererRelations",
                },
                {
                    headerName: "Différence",
                    field: "diff time",
                    filter: true,
                    cellRendererFramework: "CellRendererRelations",
                },
            ],
            // Cell Renderer Components
            components: {
                CellRendererLink,
                CellRendererActions,
                CellRendererRelations,
                CellRendererStatus
            },
            filters: {
                status: null,
                project_id: null,
                date: null,
                formatDate: "year",
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
                //this.fetchHours();
            },
            deep: true
        }
    },
    computed: {
        projectsData() {
            if (!this.$store.state.projectManagement) {
                return [];
            }
            return this.$store.getters["projectManagement/getItems"]
        },
        hoursData() {
            if (!this.$store.state.hoursManagement) {
                return [];
            }
            return this.$store.getters["hoursManagement/getItems"];

        },
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
                console.log("hey")
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
            if (this.projectsData) {
                this.projectsData.map(row => {
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
            if (this.projectsData) {
                this.projectsData.map(row => {
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
            this.NamesUsers
            return time;
        },
        GetAllStatsProjects() {
            let hours = []
            let names = []
            let estimatedTime = 0
            let achievedTime = 0
            if (this.projectsData) {
                this.projectsData.map(row => {
                    if (row.progress.nb_task_time_done) {
                        hours[row.id] = row.progress.nb_task_time_done + "h"
                        names[row.id] = row.name
                        estimatedTime += row.progress.nb_task_time
                        console.log(ato )
                    }
                    if (row.progress.nb_task_time_done) {
                        achievedTime += row.progress.nb_task_time_done
                    }
                })
            }
            hours = hours.map(a => a.toFixed(2));
            hours = hours.map(Number);
            this.estimatedTime = estimatedTime.toFixed(1)
            this.achievedTime = achievedTime.toFixed(1)
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
        GetAllStatsUsers() {
            let hours = []
            const names = []
            const formatDateFilterMonth = (date) => {
                let formatted_date = date.getFullYear() + "-"
                    + ("0" + (date.getMonth() + 1)).slice(-2)
                return formatted_date;
            }
            const formatDateProjectMonth = (date) => {
                let formatted_date = date.split(" ").splice(0, 1).join("")
                formatted_date = formatted_date.split("-").splice(0, 2).join("-")
                return formatted_date
            }
            const formatDateFilterYear = (date) => {
                let formatted_date = date.getFullYear()
                return formatted_date;
            }
            const formatDateProjectYear = (date) => {
                let formatted_date = date.split(" ").splice(0, 1).join("")
                formatted_date = formatted_date.split("-").splice(0, 1)
                return formatted_date
            }
            this.hoursData.map(row => {
                if (row.durationInFloatHour) {
                    if (this.filters.status == null && this.filters.date == null) {
                        hours[row.user_id] = row.durationInFloatHour
                        names[row.user_id] = row.user.firstname
                    }
                    if (this.filters.status !== null && this.filters.date !== null){
                        if (this.filters.formatDate === "year") {
                            if (formatDateProjectYear(row.project.date) == formatDateFilterYear(this.filters.date) && row.project.status == this.filters.status) {
                                hours[row.user_id] = row.durationInFloatHour
                                names[row.user_id] = row.user.firstname

                            }
                        }
                        if (this.filters.formatDate === "month") {
                            if (formatDateProjectMonth(row.project.date) == formatDateFilterMonth(this.filters.date) && row.project.status == this.filters.status) {
                                hours[row.user_id] = row.durationInFloatHour
                                names[row.user_id] = row.user.firstname
                            }
                        }
                    }
                    if (row.project.status == this.filters.status && this.filters.date == null) {
                        hours[row.user_id] = row.durationInFloatHour
                        names[row.user_id] = row.user.firstname
                    }
                    if (this.filters.status == null && this.filters.date !== null ) {
                        if (this.filters.formatDate === "year") {
                            if (formatDateProjectYear(row.project.date) == formatDateFilterYear(this.filters.date)) {
                                hours[row.user_id] = row.durationInFloatHour
                                names[row.user_id] = row.user.firstname

                            }
                        }
                        if (this.filters.formatDate === "month") {
                            if (formatDateProjectMonth(row.project.date) == formatDateFilterMonth(this.filters.date)) {
                                hours[row.user_id] = row.durationInFloatHour
                                names[row.user_id] = row.user.firstname
                            }
                        }
                    }
                }
            });

            hours = hours.map(a => a.toFixed(2));
            hours = hours.map(Number);
            if (this.$refs.donut2) {
                this.$refs.donut2.updateSeries(hours.filter(Boolean))
                this.$refs.donut2.updateOptions({labels: names.filter(Boolean)})
            }
            if (hours.filter(Boolean).length === 0) {
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
            }
            return ''
        },

    },
    methods: {
        diffTimeData() {
            let time = 0;
            if (this.achievedTime && this.estimatedTime) {
                time = (this.estimatedTime - this.achievedTime)
            }
            return time.toFixed(1)
        },
        diffBudgetData() {
            let budget = 0;
            if (this.diffTimeData()) {
                budget = (this.diffTimeData() * '55')
            }
            return budget.toFixed(2)
        },
        refreshData() {
            /*this.$vs.loading();
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
                .finally(() => this.$vs.loading.close());*/
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
                    ...this.filterParams,
                    page: this.page,
                    per_page: this.perPage,
                    q: this.searchQuery || undefined,
                    company_id: this.filters.company_id || undefined,
                    customer_id: this.filters.customer_id || undefined,
                    project: this.filters.project_id || undefined,
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

            this.$store.dispatch("hoursManagement/fetchItems", {
            })
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
        },
        onResize(event) {
            if (this.gridApi) {
                // refresh the grid
                this.gridApi.redrawRows();

                // resize columns in the grid to fit the available space
                this.gridApi.sizeColumnsToFit();
            }
        }
    },
    mounted() {
        this.gridApi = this.gridOptions.api;

        window.addEventListener("resize", this.onResize);
        if (this.gridApi) {
            // refresh the grid
            this.gridApi.redrawRows();

            // resize columns in the grid to fit the available space
            this.gridApi.sizeColumnsToFit();

            this.gridApi.showLoadingOverlay();
        }
        if (this.$vs.rtl) {
            const header = this.$refs.agGridTable.$el.querySelector(
                ".ag-header-container"
            );
            header.style.left = `-${String(
                Number(header.style.transform.slice(11, -3)) + 9
            )}px`;
        }
    },
    created() {
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
        this.projectsData;
        this.hoursData;
    },
    beforeDestroy() {
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
.small-radio-circle .vs-radio--circle {
    transform: scale(0.5) !important;
}

.small-radio-circle .vs-radio--borde {
    transform: scale(0.5) !important;
}
</style>
