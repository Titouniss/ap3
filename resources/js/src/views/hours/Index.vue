<template>
    <div id="page-hours-list">
        <div class="vx-card p-6">
            <div class="d-theme-dark-light-bg flex flex-row justify-start pb-3">
                <feather-icon icon="FilterIcon" svgClasses="h-6 w-6" />
                <h4 class="ml-3">Filtres</h4>
            </div>
            <div class="flex flex-wrap justify-center items-end">
                <div style="min-width: 15em">
                    <infinite-select
                        header="Projet"
                        model="project"
                        label="name"
                        v-model="filters.project_id"
                        @focus="clearRefreshDataTimeout"
                    />
                </div>
                <vs-dropdown vs-trigger-click class="cursor-pointer mx-4">
                    <div
                        class="p-3 rounded-lg border border-solid d-theme-border-grey-light cursor-pointer flex items-center justify-between text-lg font-medium w-32"
                    >
                        <span class="mx-2 leading-none">
                            {{ period_types[filters.period_type].name }}
                        </span>
                        <feather-icon
                            icon="ChevronDownIcon"
                            svgClasses="h-4 w-4"
                        />
                    </div>

                    <vs-dropdown-menu>
                        <vs-dropdown-item
                            v-for="period_type in period_type_names"
                            :key="period_type"
                            @click="setPeriodType(period_type)"
                        >
                            <span class="flex items-center">
                                {{ period_types[period_type].name }}
                            </span>
                        </vs-dropdown-item>
                    </vs-dropdown-menu>
                </vs-dropdown>
                <div style="min-width: 15em">
                    <infinite-select
                        v-if="authorizedTo('show', 'users')"
                        header="Utilisateur"
                        label="lastname"
                        model="user"
                        v-model="filters.user_id"
                        :item-fields="['lastname', 'firstname']"
                        :item-text="
                            item => `${item.lastname} ${item.firstname}`
                        "
                        @focus="clearRefreshDataTimeout"
                    />
                </div>
            </div>
            <div class="flex flex-wrap items-center">
                <vs-row
                    v-if="!isFullFilter"
                    vs-justify="center"
                    vs-align="center"
                    vs-type="flex"
                    vs-w="12"
                    class="mt-6"
                >
                    <vs-col
                        vs-w="12"
                        vs-type="flex"
                        vs-justify="center"
                        vs-align="center"
                    >
                        <vs-button
                            v-if="isPeriodFilter"
                            radius
                            color="primary"
                            type="border"
                            icon-pack="feather"
                            icon="icon-chevron-left"
                            @click="removeFromFilterDate"
                        ></vs-button>
                        <div class="m-3 flex" style="width: 300px">
                            <vs-row vs-type="flex" vs-justify="center">
                                <vs-col vs-type="flex" vs-justify="center">
                                    <h5 v-if="isPeriodFilter">
                                        {{ filterDate }}
                                    </h5>
                                </vs-col>
                                <vs-col vs-type="flex" vs-justify="center">
                                    <h5
                                        class="mt-1"
                                        v-if="
                                            this.filters.period_type === 'week'
                                        "
                                    >
                                        {{ currentWeek }}
                                    </h5>
                                </vs-col>
                                <vs-col vs-type="flex" vs-justify="center">
                                    <flat-pickr
                                        v-if="!isPeriodFilter"
                                        :config="configDatePicker()"
                                        placeholder="Date"
                                        v-model="filters.date"
                                        @on-open="clearRefreshDataTimeout"
                                    />
                                </vs-col>
                            </vs-row>
                        </div>
                        <vs-button
                            v-if="isPeriodFilter"
                            radius
                            color="primary"
                            type="border"
                            icon-pack="feather"
                            icon="icon-chevron-right"
                            @click="addToFilterDate"
                        ></vs-button>
                    </vs-col>
                </vs-row>
            </div>
        </div>
        <div class="vx-card p-6 mt-1">
            <div class="d-theme-dark-light-bg flex flex-row justify-start pb-3">
                <feather-icon icon="BarChart2Icon" svgClasses="h-6 w-6" />
                <h4 class="ml-3">Résumé</h4>
            </div>
            <vs-row
                v-if="showSummary"
                vs-justify="center"
                vs-align="center"
                vs-type="flex"
                vs-w="12"
            >
                <vs-col
                    vs-w="6"
                    vs-type="flex"
                    vs-justify="center"
                    vs-align="center"
                >
                    Heures travaillées sur la période :
                    {{ " " + getStats("total") }}
                </vs-col>
                <!-- v-if="stats.overtime" -->
                <vs-col
                    vs-w="6"
                    vs-type="flex"
                    vs-justify="center"
                    vs-align="center"
                >
                    {{ lostTimeOrOvertime() + " " + getStats("overtime") }}
                </vs-col>
            </vs-row>
            <vs-row
                v-else
                vs-justify="center"
                vs-align="center"
                vs-type="flex"
                vs-w="12"
            >
                Veuillez renseigner des heures sur cette période afin d'avoir le
                résumé
            </vs-row>
            <vs-row
                vs-justify="center"
                vs-align="center"
                vs-type="flex"
                vs-w="12"
                class="mt-6"
            >
            </vs-row>
        </div>
        <div class="vx-card p-6 mt-1 mb-base">
            <div
                class="d-theme-dark-light-bg flex flex-row justify-between items-center pb-3"
            >
                <vs-row
                    v-if="showSummary"
                    vs-justify="center"
                    vs-align="center"
                    vs-type="flex"
                    vs-w="12"
                >
                    <vs-col
                        vs-w="4"
                        vs-type="flex"
                        vs-justify="start"
                        vs-align="center"
                    >
                        <feather-icon icon="ClockIcon" svgClasses="h-6 w-6" />
                        <h4 class="ml-3">Heures effectuées</h4>
                    </vs-col>
                    <!-- v-if="stats.overtime" -->
                    <vs-col
                        vs-w="4"
                        vs-type="flex"
                        vs-justify="center"
                        vs-align="center"
                    >
                        <div class="px-6 py-2" v-if="authorizedTo('publish')">
                            <vs-button @click="readRecord">
                                {{
                                    isAdmin
                                        ? "Gérer les heures"
                                        : "Gérer mes heures"
                                }}
                            </vs-button>
                        </div>
                    </vs-col>
                    <vs-col
                        vs-w="4"
                        vs-type="flex"
                        vs-justify="end"
                        vs-align="center"
                    >
                        <vs-button type="border" @click="onExport">
                            <div class="flex flex-row">
                                <feather-icon
                                    icon="DownloadIcon"
                                    svgClasses="h-5 w-5"
                                    class="mr-2"
                                />
                                Exporter
                            </div>
                        </vs-button>
                    </vs-col>
                </vs-row>
            </div>
            <div class="flex flex-wrap items-center">
                <div class="flex-grow">
                    <vs-row type="flex">
                        <!-- <vs-button class="mb-4 md:mb-0" @click="gridApi.exportDataAsCsv()">Export as CSV</vs-button> -->

                        <multiple-actions
                            model="hours"
                            model-plurial="hours"
                            :uses-soft-delete="false"
                            :items="selectedItems"
                            @on-action="
                                () => {
                                    this.onAction();
                                    this.refreshData();
                                }
                            "
                        />

                        <!-- TABLE ACTION COL-2: SEARCH & EXPORT AS CSV -->
                        <vs-input
                            class="ml-5"
                            v-model="searchQuery"
                            @input="updateSearchQuery"
                            placeholder="Rechercher..."
                        />
                    </vs-row>
                </div>
                <!-- ITEMS PER PAGE -->
                <vs-dropdown vs-trigger-click class="cursor-pointer">
                    <div
                        class="p-4 border border-solid d-theme-border-grey-light rounded-full d-theme-dark-bg cursor-pointer flex items-center justify-between font-medium"
                    >
                        <span class="mr-2">
                            {{ currentPage * perPage - (perPage - 1) }}
                            -
                            {{
                                total - currentPage * perPage > 0
                                    ? currentPage * perPage
                                    : total
                            }}
                            sur {{ total }}
                        </span>
                        <feather-icon
                            icon="ChevronDownIcon"
                            svgClasses="h-4 w-4"
                        />
                    </div>
                    <!-- <vs-button class="btn-drop" type="line" color="primary" icon-pack="feather" icon="icon-chevron-down"></vs-button> -->
                    <vs-dropdown-menu>
                        <vs-dropdown-item @click="itemsPerPage = 10">
                            <span>10</span>
                        </vs-dropdown-item>
                        <vs-dropdown-item @click="itemsPerPage = 20">
                            <span>20</span>
                        </vs-dropdown-item>
                        <vs-dropdown-item @click="itemsPerPage = 30">
                            <span>30</span>
                        </vs-dropdown-item>
                        <vs-dropdown-item @click="itemsPerPage = 50">
                            <span>50</span>
                        </vs-dropdown-item>
                    </vs-dropdown-menu>
                </vs-dropdown>
            </div>

            <!-- AgGrid Table -->
            <ag-grid-vue
                ref="agGridTable"
                :components="components"
                :gridOptions="gridOptions"
                class="ag-theme-material w-100 my-4 ag-grid-table"
                overlayLoadingTemplate="Chargement..."
                :columnDefs="columnDefs"
                :defaultColDef="defaultColDef"
                :rowData="hoursData"
                rowSelection="multiple"
                colResizeDefault="shift"
                :animateRows="true"
                :floatingFilter="false"
                :enableRtl="$vs.rtl"
                @selection-changed="onSelectedItemsChanged"
            ></ag-grid-vue>

            <vs-pagination :total="totalPages" :max="7" v-model="currentPage" />
        </div>

        <edit-form :itemId="itemIdToEdit" v-if="itemIdToEdit" />
    </div>
</template>

<script>
import { AgGridVue } from "ag-grid-vue";
import "@sass/vuexy/extraComponents/agGridStyleOverride.scss";

import EditForm from "./EditForm.vue";

// Store Module
import moduleHoursManagement from "@/store/hours-management/moduleHoursManagement.js";
import moduleProjectManagement from "@/store/project-management/moduleProjectManagement.js";
import moduleUserManagement from "@/store/user-management/moduleUserManagement.js";

// FlatPickr import
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import { French as FrenchLocale } from "flatpickr/dist/l10n/fr.js";

// Cell Renderer
import CellRendererActions from "@/components/cell-renderer/CellRendererActions.vue";
import CellRendererRelations from "./cell-renderer/CellRendererRelations.vue";

// Unavailabilities
import UnavailabilitiesIndex from "../unavailabilities/Index.vue";

// Components
import InfiniteSelect from "@/components/inputs/selects/InfiniteSelect";
import MultipleActions from "@/components/inputs/buttons/MultipleActions.vue";

// Mixins
import { multipleActionsMixin } from "@/mixins/lists";

import moment from "moment";
import _ from "lodash";

var model = "hours";
var modelPlurial = "hours";
var modelTitle = "Heures";

moment.locale("fr");

export default {
    mixins: [multipleActionsMixin],
    components: {
        AgGridVue,
        flatPickr,
        EditForm,
        // Cell Renderer
        CellRendererActions,
        CellRendererRelations,
        //Unavailabilities
        UnavailabilitiesIndex,

        // Components
        InfiniteSelect,
        MultipleActions
    },
    data() {
        return {
            searchQuery: "",
            page: 1,
            perPage: 10,
            totalPages: 0,
            total: 0,

            id_user: null,
            // AgGrid
            gridApi: null,
            gridOptions: {
                localeText: { noRowsToShow: "Aucune heure à afficher" }
            },
            defaultColDef: {
                sortable: true,
                resizable: true,
                suppressMenu: true
            },
            columnDefs: [
                {
                    filter: false,
                    width: 40,
                    checkboxSelection: true,
                    headerCheckboxSelectionFilteredOnly: false,
                    headerCheckboxSelection: true
                },
                {
                    headerName: "Date",
                    field: "date",
                    cellRenderer: data => {
                        moment.locale("fr");
                        return moment(data.data.start_at).format("D MMMM YYYY");
                    }
                },
                {
                    headerName: "Durée",
                    field: "duration"
                },
                {
                    headerName: "Opérateur",
                    field: "user",
                    cellRenderer: data => {
                        return (
                            data.data.user.firstname +
                            " " +
                            data.data.user.lastname
                        );
                    }
                },
                {
                    headerName: "Description",
                    field: "description"
                },
                {
                    headerName: "Projet",
                    field: "project",
                    cellRendererFramework: "CellRendererRelations"
                },
                {
                    sortable: false,
                    headerName: "Actions",
                    field: "transactions",
                    type: "numericColumn",
                    cellRendererFramework: "CellRendererActions",
                    cellRendererParams: {
                        model: "hours",
                        modelPlurial: "hours",
                        usesSoftDelete: false,
                        withPrompt: true,
                        name: data =>
                            data.duration == "01:00:00"
                                ? `l'heure du ${
                                      data.start_at.split(" ")[0]
                                  } pour le projet ${data.project}`
                                : `les ${
                                      data.duration.split(":")[0]
                                  } heures du ${
                                      data.start_at.split(" ")[0]
                                  } pour le projet ${data.project.name}`
                    }
                }
            ],

            // Cell Renderer Components
            components: {
                CellRendererActions,
                CellRendererRelations
            },

            // Excel
            headerTitle: [
                "Id",
                "Utilisateur",
                "Projet",
                "Date",
                "Durée",
                "Description"
            ],
            headerVal: [
                "id",
                "user",
                "project",
                "date",
                "duration",
                "description"
            ],

            // Filters
            filters: {
                project_id: null,
                user_id: null,
                date: moment(),
                period_type: "month",
                hours_taken: null
            },
            hours_type_names: [
                "Heures supplémentaires payées",
                "Utilisation heures supplémentaires",
                "Jours fériés",
                "Rendez-vous privé",
                "Congés payés",
                "Période de cours",
                "Arrêt de travail"/*,
                "Autre..."*/
            ],
            period_type_names: ["date", "day", "week", "month", "year", "full"],
            period_types: {
                date: {
                    name: "Date",
                    symbol: "d",
                    format: "D MMMM YYYY"
                },
                day: {
                    name: "Jour",
                    symbol: "d",
                    format: "D MMMM YYYY"
                },
                week: {
                    name: "Semaine",
                    symbol: "w",
                    format: "[Semaine] w, YYYY"
                },
                month: {
                    name: "Mois",
                    symbol: "M",
                    format: "MMMM YYYY"
                },
                year: {
                    name: "Année",
                    symbol: "y",
                    format: "YYYY"
                },
                full: {
                    name: "Total",
                    symbol: null,
                    format: null
                }
            },
            currentWeek: "",
            configDatePicker: () => ({
                disableMobile: "true",
                enableTime: false,
                locale: FrenchLocale,
                altFormat: "j F Y",
                altInput: true
            }),
            refreshDataTimeout: null,

            // Stats
            stats: { total: 0 }
        };
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
            deep: true
        }
    },
    computed: {
        isAdmin() {
            return this.$store.state.AppActiveUser.is_admin;
        },
        isManager() {
            return this.$store.state.AppActiveUser.is_manager;
        },
        isFullFilter() {
            return this.filters.period_type === "full";
        },
        isPeriodFilter() {
            return this.filters.period_type !== "date";
        },
        filterDate() {
            moment.locale("fr");
            if (this.filters.period_type === "week") {
                let startWeek = moment(this.filters.date)
                    .startOf("isoWeek")
                    .format("dddd D MMM");

                let endWeek = moment(this.filters.date)
                    .endOf("isoWeek")
                    .format("dddd D MMM");

                this.currentWeek = startWeek + " - " + endWeek;
            }

            return moment(this.filters.date).format(
                this.period_types[this.filters.period_type].format
            );
        },
        itemIdToEdit() {
            return (
                this.$store.getters["hoursManagement/getSelectedItem"].id || 0
            );
        },
        users() {
            return this.$store.getters["userManagement/getItems"];
        },
        hoursData() {
            return this.$store.getters["hoursManagement/getItems"];
        },
        showSummary() {
            if (
                this.stats.length === undefined &&
                typeof this.stats.total === "object"
            ) {
                return false;
            } else {
                return true;
            }
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
        itemsPerPage: {
            get() {
                return this.perPage;
            },
            set(val) {
                this.perPage = val;
                this.currentPage = 1;
            }
        },
        currentPage: {
            get() {
                return this.page;
            },
            set(val) {
                this.page = val;
                this.refreshData();
            }
        }
    },
    methods: {
        authorizedTo(action, model = modelPlurial) {
            return this.$store.getters.userHasPermissionTo(
                `${action} ${model}`
            );
        },
        clearRefreshDataTimeout() {
            if (this.refreshDataTimeout) {
                clearTimeout(this.refreshDataTimeout);
            }
        },
        getStats(name) {
            if (name === "overtime") {
                if (this.stats["overtime"]) {
                    return (this.stats[name]
                        ? parseFloat(this.stats[name])
                        : 0
                    ).toFixed(2);
                } else {
                    return (this.stats["lost_time"]
                        ? parseFloat(this.stats["lost_time"])
                        : 0
                    ).toFixed(2);
                }
            } else {
                return (this.stats[name]
                    ? parseFloat(this.stats[name])
                    : 0
                ).toFixed(2);
            }
        },
        lostTimeOrOvertime() {
            if (this.stats["overtime"]) {
                return "Heures supplémentaires sur la période :";
            } else {
                return "Heures manquantes sur la période :";
            }
        },
        addToFilterDate() {
            this.filters.date = moment(this.filters.date).add(
                1,
                this.period_types[this.filters.period_type].symbol
            );
        },
        removeFromFilterDate() {
            this.filters.date = moment(this.filters.date).subtract(
                1,
                this.period_types[this.filters.period_type].symbol
            );
        },
        setPeriodType(type) {
            this.filters.period_type = type;
            this.filters.date =
                type === "date" || type === "full" ? null : moment();
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
                        const { total, last_page } = data.pagination;
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
        setColumnFilter(column, val) {
            const filter = this.gridApi.getFilterInstance(column);
            let modelObj = null;

            if (val !== "all") {
                modelObj = { type: "equals", filter: val };
            }

            filter.setModel(modelObj);
            this.gridApi.onFilterChanged();
        },
        resetColFilters() {
            // Reset Grid Filter
            this.gridApi.setFilterModel(null);
            this.gridApi.onFilterChanged();

            // Reset Filter Options
            this.hoursFilter = this.statusFilter = this.isVerifiedFilter = this.departmentFilter = {
                label: "All",
                value: "all"
            };

            this.$refs.filterCard.removeRefreshAnimation();
        },
        updateSearchQuery(val) {
            this.gridApi.setQuickFilter(val);
        },
        onResize(event) {
            if (this.gridApi) {
                // refresh the grid
                this.gridApi.refreshView();

                // resize columns in the grid to fit the available space
                this.gridApi.sizeColumnsToFit();
            }
        },
        addRecord() {
            this.$router.push(`/${modelPlurial}/${model}-add/`).catch(() => {});
        },
        readRecord() {
            this.$router
                .push(`/${modelPlurial}/${model}-view/`)
                .catch(() => {});
        },
        goToUnavailabilities() {
            this.$router.push({
                path:
                    `/users/user-profil-edit/` +
                    this.$store.state.AppActiveUser.id,
                query: { tab: 1 }
            });
        },
        onExport() {
            import("@/vendor/Export2Excel").then(excel => {
                const data = this.formatJson(this.headerVal, this.hoursData);
                excel.export_json_to_excel({
                    header: this.headerTitle,
                    data,
                    filename:
                        moment().format("YYYY-MM-DD HH:mm") +
                        "_Heures_effectuées",
                    autoWidth: true,
                    bookType: "xlsx"
                });
            });
        },
        formatJson(filterVal, jsonData) {
            return jsonData.map(v =>
                filterVal.map(j => {
                    let value;
                    switch (j) {
                        case "user":
                            value = v[j].email;
                            break;
                        case "project":
                            value = v[j].name;
                            break;
                        default:
                            value = v[j];
                            break;
                    }
                    return value;
                })
            );
        }
    },
    mounted() {
        this.gridApi = this.gridOptions.api;

        // Hide user column ?
        this.gridOptions.columnApi.setColumnVisible(
            "user",
            this.isAdmin || this.isManager
        );

        window.addEventListener("resize", this.onResize);
        if (this.gridApi) {
            // refresh the grid
            this.gridApi.refreshView();

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
        if (!moduleHoursManagement.isRegistered) {
            this.$store.registerModule(
                "hoursManagement",
                moduleHoursManagement
            );
            moduleHoursManagement.isRegistered = true;
        }
        if (!moduleProjectManagement.isRegistered) {
            this.$store.registerModule(
                "projectManagement",
                moduleProjectManagement
            );
            moduleProjectManagement.isRegistered = true;
        }
        if (!moduleUserManagement.isRegistered) {
            this.$store.registerModule("userManagement", moduleUserManagement);
            moduleUserManagement.isRegistered = true;
        }

        if (this.authorizedTo("read", "users")) {
            this.$store.dispatch("userManagement/fetchItems");
        } else {
            this.filters.user_id = this.$store.state.AppActiveUser.id;
        }
    },
    beforeDestroy() {
        window.removeEventListener("resize", this.onResize());
        moduleProjectManagement.isRegistered = false;
        moduleUserManagement.isRegistered = false;
        moduleHoursManagement.isRegistered = false;
        this.$store.unregisterModule("hoursManagement");
        this.$store.unregisterModule("projectManagement");
        this.$store.unregisterModule("userManagement");
    }
};
</script>
