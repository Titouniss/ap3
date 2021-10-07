<template>
    <div>
        <div class="vx-card p-6">
            <div class="d-theme-dark-light-bg flex flex-row justify-start pb-3">
                <feather-icon icon="FilterIcon" svgClasses="h-6 w-6" />
                <h4 class="ml-3">Filtres</h4>
            </div>
            <div class="flex flex-wrap justify-center items-end">
                <div style="min-width: 15em" class="cursor-pointer mx-4">
                    <v-select
                        label="name"
                        v-model="filters.hours_taken"
                        :options="hours_type_names"
                        @search:focus="clearRefreshDataTimeout"
                        class="w-full"
                    >
                        <template #header>
                            <div style="opacity: 0.8">Heures prises</div>
                        </template>
                    </v-select>
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
                    <v-select
                        v-if="authorizedTo('show', 'users')"
                        label="lastname"
                        :options="users"
                        v-model="filters.user_id"
                        :reduce="user => user.id"
                        @search:focus="clearRefreshDataTimeout"
                        class="w-full"
                    >
                        <template #header>
                            <div style="opacity: 0.8">Utilisateur</div>
                        </template>
                        <template #option="user">
                            <span>
                                {{ `${user.lastname} ${user.firstname}` }}
                            </span>
                        </template>
                    </v-select>
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
        <div class="mb-base">
            <br />
            <h6 class="mb-4">Heures supplémentaires</h6>
            <div class="flex items-center mb-4">
                <span class="ml-4">Total :</span>
                <span class="ml-4"
                    >{{ overtimes.toFixed(2) }}
                    {{ overtimes > 1 ? "heures" : "heure" }}</span
                >
            </div>
            <div class="flex items-center mb-4">
                <span class="ml-4">{{
                    usedOvertimes > 1 ? "Utilisées :" : "Utilisée :"
                }}</span>
                <span class="ml-4"
                    >{{ usedOvertimes.toFixed(2) }}
                    {{ usedOvertimes > 1 ? "heures" : "heure" }}</span
                >
            </div>
            <div class="flex items-center mb-4">
                <span class="ml-4">{{
                    payedOvertimes > 1 ? "Payées :" : "Payée :"
                }}</span>
                <span class="ml-4"
                    >{{ payedOvertimes.toFixed(2) }}
                    {{ payedOvertimes > 1 ? "heures" : "heure" }}</span
                >
            </div>
            <div class="flex items-center mb-4">
                <span class="ml-4">Reste à utiliser :</span>
                <span class="ml-4"
                    >{{
                        overtimes > 0
                            ? (
                                  overtimes -
                                  usedOvertimes -
                                  payedOvertimes
                              ).toFixed(2)
                            : 0
                    }}
                    {{
                        overtimes - (usedOvertimes - payedOvertimes) > 1
                            ? "heures"
                            : "heure"
                    }}</span
                >
            </div>
            <add-payed-hours-form
                v-if="isAdmin || isManager"
                :id_user="user_id"
                @on-submit="fetchOvertimes"
                :fetchOvertimes="fetchOvertimes"
            />
        </div>

        <div class="mb-base">
            <h6 class="mb-4">Indisponibilités</h6>
            <add-form
                :id_user="user_id"
                :fetch-overtimes="fetchOvertimes"
                :work-hours="workHours"
                @on-submit="fetchOvertimes"
            />
            <div class="flex flex-wrap items-center">
                <!-- ITEMS PER PAGE -->
                <div class="flex-grow">
                    <vs-row type="flex">
                        <!-- <vs-button class="mb-4 md:mb-0" @click="gridApi.exportDataAsCsv()">Export as CSV</vs-button> -->

                        <multiple-actions
                            model="unavailability"
                            model-plurial="unavailabilities"
                            :uses-soft-delete="false"
                            :items="selectedItems"
                            @on-action="
                                () => {
                                    this.onAction();
                                    this.fetchOvertimes();
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
                class="ag-theme-material w-full my-4 ag-grid-table"
                overlayLoadingTemplate="Chargement..."
                :columnDefs="columnDefs"
                :defaultColDef="defaultColDef"
                :rowData="unavailabilitiesData"
                rowSelection="multiple"
                colResizeDefault="shift"
                :animateRows="true"
                :floatingFilter="false"
                :enableRtl="$vs.rtl"
                @firstDataRendered="onResize"
                @selection-changed="onSelectedItemsChanged"
            ></ag-grid-vue>

            <vs-pagination :total="totalPages" :max="7" v-model="currentPage" />

            <edit-form
                v-if="itemIdToEdit"
                :itemId="itemIdToEdit"
                @on-submit="fetchOvertimes"
                :fetchOvertimes="fetchOvertimes"
            />
        </div>
    </div>
</template>

<script>
import { AgGridVue } from "ag-grid-vue";
import "@sass/vuexy/extraComponents/agGridStyleOverride.scss";
import vSelect from "vue-select";

//CRUD
import AddForm from "./AddForm.vue";
import AddPayedHoursForm from "./AddPayedHoursForm.vue";
import EditForm from "./EditForm.vue";

// Store Module
import moduleUnavailabilityManagement from "@/store/unavailability-management/moduleUnavailabilityManagement.js";
import moduleDealingHoursManagement from "@/store/dealing-hours-management/moduleDealingHoursManagement.js";
import moduleHoursManagement from "@/store/hours-management/moduleHoursManagement.js";
import moduleProjectManagement from "@/store/project-management/moduleProjectManagement.js";
import moduleUserManagement from "@/store/user-management/moduleUserManagement.js";

// FlatPickr import
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import { French as FrenchLocale } from "flatpickr/dist/l10n/fr.js";

// Cell Renderer
import CellRendererActions from "@/components/cell-renderer/CellRendererActions.vue";

// Components
import MultipleActions from "@/components/inputs/buttons/MultipleActions.vue";
import InfiniteSelect from "@/components/inputs/selects/InfiniteSelect";

// Mixins
import { multipleActionsMixin } from "@/mixins/lists";

import moment from "moment";
import _ from "lodash";

var model = "unavailabilitie";
var modelPlurial = "unavailabilities";
var modelTitle = "Indisponibilité";

moment.locale("fr");

export default {
    mixins: [multipleActionsMixin],
    components: {
        AgGridVue,
        vSelect,
        AddForm,
        EditForm,
        flatPickr,
        // Cell Renderer
        CellRendererActions,
        AddPayedHoursForm,

        // Components
        InfiniteSelect,
        MultipleActions
    },
    data() {
        return {
            overtimes: 0,
            usedOvertimes: 0,
            payedOvertimes: 0,

            searchQuery: "",
            page: 1,
            perPage: 10,
            totalPages: 0,
            total: 0,

            // AgGrid
            gridApi: null,
            gridOptions: {
                localeText: {
                    noRowsToShow: "Aucune indisponibilité à afficher"
                }
            },
            defaultColDef: {
                sortable: true,
                resizable: true,
                suppressMenu: true
            },
            columnDefs: [
                {
                    filter: false,
                    maxWidth: 40,
                    checkboxSelection: true,
                    headerCheckboxSelectionFilteredOnly: true,
                    headerCheckboxSelection: true
                },
                {
                    headerName: "Début",
                    field: "starts_at",
                    filter: true,
                    valueFormatter: param => this.formatDateTime(param.value)
                },
                {
                    headerName: "Fin",
                    field: "ends_at",
                    filter: true,
                    valueFormatter: param => this.formatDateTime(param.value)
                },
                {
                    headerName: "Motif",
                    field: "reason",
                    filter: true
                },
                {
                    sortable: false,
                    headerName: "Actions",
                    field: "transactions",
                    type: "numericColumn",
                    cellRendererFramework: "CellRendererActions",
                    cellRendererParams: {
                        model: "unavailability",
                        modelPlurial: "unavailabilities",
                        withPrompt: true,
                        name: data =>
                            `l'indisponibilité du ${moment(
                                data.starts_at
                            ).format("DD/MM/YYYY [à] HH:mm")} au ${moment(
                                data.ends_at
                            ).format("DD/MM/YYYY [à] HH:mm")}`,
                        usesSoftDelete: false,
                        onDelete: this.fetchOvertimes
                    }
                }
            ],

            // Cell Renderer Components
            components: {
                CellRendererActions
            },
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
                "Arrêt de travail",
                "Autre..."
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
            workHours: [],

            // Stats
            stats: { total: 0 }
        };
    },
    watch: {
        filters: {
            handler(value, prev) {
                if (!_.isEqual(value, prev)) {
                    if (this.refreshDataTimeout) {
                        clearTimeout(this.refreshDataTimeout);
                    }
                    this.refreshDataTimeout = setTimeout(() => {
                        this.page = 1;
                        this.refreshData();
                        if (value.user_id !== prev.user_id) {
                            this.fetchOvertimes();
                        }
                    }, 1500);
                }
            },
            deep: true
        },
        filterParams: {
            handler(value, prev) {
                if (!_.isEqual(value, prev)) {
                    this.clearRefreshDataTimeout();
                    this.refreshDataTimeout = setTimeout(() => {
                        this.page = 1;
                        this.refreshData();
                        if (value.user_id !== prev.user_id) {
                            this.fetchOvertimes();
                            this.fetchWorkHours();
                        }
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
        filterParams() {
            const filter = {};
            if (this.filters.hours_taken) {
                filter.hours_taken = this.filters.hours_taken;
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
        unavailabilitiesData() {
            return this.$store.getters["unavailabilityManagement/getItems"];
        },
        itemIdToEdit() {
            return (
                this.$store.getters["unavailabilityManagement/getSelectedItem"]
                    .id || 0
            );
        },
        user_id() {
            return this.isAdmin || this.isManager
                ? this.filters.user_id
                : this.$store.state.AppActiveUser.id;
        },
        users() {
            return this.$store.getters["userManagement/getItems"];
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
        refreshData() {
            this.$vs.loading();
            this.$store
                .dispatch("unavailabilityManagement/fetchItems", {
                    ...this.filters,
                    page: this.currentPage,
                    per_page: this.perPage,
                    q: this.searchQuery || undefined,
                    order_by: "starts_at",
                    order_by_desc: 1
                })
                .then(data => {
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
        fetchOvertimes() {
            if (this.user_id) {
                this.$store
                    .dispatch(
                        "dealingHoursManagement/getOvertimes",
                        this.user_id
                    )
                    .then(data => {
                        if (data && data.status === 200) {
                            this.overtimes = data.data.success.overtimes;
                            this.usedOvertimes =
                                data.data.success.usedOvertimes;
                            this.payedOvertimes =
                                data.data.success.payedOvertimes;
                        } else {
                            this.$vs.notify({
                                color: "error",
                                title: "Erreur",
                                text: `Impossible d'afficher les heures supplémentaires`
                            });
                            this.overtimes = 0;
                        }
                    })
                    .catch(err => {
                        console.error(err);
                    });
            }
        },
        fetchWorkHours() {
            if (this.user_id) {
                this.$store
                    .dispatch("userManagement/fetchItem", this.user_id)
                    .then(data => {
                        if (data.success)
                            this.workHours = data.payload.work_hours;
                    });
            }
        },
        authorizedTo(action, model = modelPlurial) {
            return this.$store.getters.userHasPermissionTo(
                `${action} ${model}`
            );
        },
        formatDateTime(value) {
            return moment(value).format("DD-MM-YYYY HH:mm");
        },
        updateSearchQuery(val) {
            this.gridApi.setQuickFilter(val);
        },
        parseDateTime(dateTime) {
            let date = dateTime.split(" ")[0];
            let hour = dateTime.split(" ")[1];
            hour = hour.split(":")[0] + ":" + hour.split(":")[1];
            return date + " à " + hour;
        },
        onResize(event) {
            if (this.gridApi) {
                // refresh the grid
                this.gridApi.redrawRows();

                // resize columns in the grid to fit the available space
                this.gridApi.sizeColumnsToFit();
            }
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
        clearRefreshDataTimeout() {
            if (this.refreshDataTimeout) {
                clearTimeout(this.refreshDataTimeout);
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
        }
    },
    mounted() {
        this.fetchOvertimes();
        this.fetchWorkHours();
        this.gridApi = this.gridOptions.api;

        window.addEventListener("resize", this.onResize);
        if (this.gridApi) {
            // refresh the grid
            this.gridApi.redrawRows();

            // resize columns in the grid to fit the available space
            this.gridApi.sizeColumnsToFit();

            this.gridApi.showLoadingOverlay();
        }

        /* =================================================================
      NOTE:
      Header is not aligned properly in RTL version of agGrid table.
      However, we given fix to this issue. If you want more robust solution please contact them at gitHub
    ================================================================= */
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
        if (!moduleUnavailabilityManagement.isRegistered) {
            this.$store.registerModule(
                "unavailabilityManagement",
                moduleUnavailabilityManagement
            );
            moduleUnavailabilityManagement.isRegistered = true;
        }
        if (!moduleDealingHoursManagement.isRegistered) {
            this.$store.registerModule(
                "dealingHoursManagement",
                moduleDealingHoursManagement
            );
            moduleDealingHoursManagement.isRegistered = true;
        }
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

        moduleUnavailabilityManagement.isRegistered = false;
        moduleDealingHoursManagement.isRegistered = false;
        moduleProjectManagement.isRegistered = false;
        moduleUserManagement.isRegistered = false;
        moduleHoursManagement.isRegistered = false;
        this.$store.unregisterModule("hoursManagement");
        this.$store.unregisterModule("projectManagement");
        this.$store.unregisterModule("userManagement");
        this.$store.unregisterModule("unavailabilityManagement");
        this.$store.unregisterModule("dealingHoursManagement");
    }
};
</script>
