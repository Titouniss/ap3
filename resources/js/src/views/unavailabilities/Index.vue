<template>
    <div>
        <div class="mb-base">
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
                        (overtimes - usedOvertimes - payedOvertimes).toFixed(2)
                    }}
                    {{
                        overtimes - (usedOvertimes - payedOvertimes) > 1
                            ? "heures"
                            : "heure"
                    }}</span
                >
            </div>
            <add-payed-hours-form
                :id_user="user_id"
                v-if="isAdmin || isManager"
            />
        </div>

        <div class="mb-base">
            <h6 class="mb-4">Indisponibilités</h6>
            <add-form :id_user="user_id" />
            <div class="flex flex-wrap items-center">
                <!-- ITEMS PER PAGE -->
                <div class="flex-grow">
                    <vs-row type="flex">
                        <!-- <vs-button class="mb-4 md:mb-0" @click="gridApi.exportDataAsCsv()">Export as CSV</vs-button> -->

                        <!-- ACTION - DROPDOWN -->
                        <vs-dropdown vs-trigger-click class="cursor-pointer">
                            <div
                                class="p-3 shadow-drop rounded-lg d-theme-dark-light-bg cursor-pointer flex items-end justify-center text-lg font-medium w-32"
                            >
                                <span class="mr-2 leading-none">Actions</span>
                                <feather-icon
                                    icon="ChevronDownIcon"
                                    svgClasses="h-4 w-4"
                                />
                            </div>

                            <vs-dropdown-menu>
                                <vs-dropdown-item
                                    @click="this.confirmDeleteRecord"
                                    v-if="authorizedTo('delete')"
                                >
                                    <span class="flex items-center">
                                        <feather-icon
                                            icon="TrashIcon"
                                            svgClasses="h-4 w-4"
                                            class="mr-2"
                                        />
                                        <span>Supprimer</span>
                                    </span>
                                </vs-dropdown-item>
                            </vs-dropdown-menu>
                        </vs-dropdown>

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
            ></ag-grid-vue>

            <vs-pagination :total="totalPages" :max="7" v-model="currentPage" />

            <edit-form :itemId="itemIdToEdit" v-if="itemIdToEdit" />
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

// Cell Renderer
import CellRendererActions from "@/components/cell-renderer/CellRendererActions.vue";
import moment from "moment";
import _ from "lodash";

var model = "unavailabilitie";
var modelPlurial = "unavailabilities";
var modelTitle = "Indisponibilité";

export default {
    components: {
        AgGridVue,
        vSelect,
        AddForm,
        EditForm,

        // Cell Renderer
        CellRendererActions,
        AddPayedHoursForm
    },
    props: {
        filters: {
            type: Object,
            default: () => ({
                user_id: null,
                date: moment(),
                period_type: "month",
                hours_taken: null
            })
        }
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
                        usesSoftDelete: false
                    }
                }
            ],

            // Cell Renderer Components
            components: {
                CellRendererActions
            }
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
            const requests = [];

            requests.push(
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
            );

            // refresh Overtimes
            if (this.user_id) {
                requests.push(
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
                        })
                );
            }

            Promise.all(requests).finally(() => this.$vs.loading.close());
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
        confirmDeleteRecord() {
            let selectedRow = this.gridApi.getSelectedRows();
            let singleUnavailabilities = selectedRow[0];

            this.$vs.dialog({
                type: "confirm",
                color: "danger",
                title: "Confirmer suppression",
                text:
                    this.gridApi.getSelectedRows().length > 1
                        ? `Voulez vous vraiment supprimer ces indisponibilités ?`
                        : `Voulez vous vraiment supprimer l'indisponibilité du
              ${this.parseDateTime(
                  singleUnavailabilities.starts_at
              )} au ${this.parseDateTime(singleUnavailabilities.ends_at)} ?`,
                accept: this.deleteRecord,
                acceptText: "Supprimer",
                cancelText: "Annuler"
            });
        },
        deleteRecord() {
            this.gridApi.getSelectedRows().map(selectRow => {
                this.$store
                    .dispatch("unavailabilityManagement/removeItems", [
                        selectRow.id
                    ])
                    .then(() => {
                        this.showDeleteSuccess();
                    })
                    .catch(err => {
                        console.error(err);
                    });
            });
        },
        showDeleteSuccess() {
            this.$vs.notify({
                color: "success",
                title: modelTitle,
                text: `${modelTitle} supprimée`
            });
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
    },
    beforeDestroy() {
        window.removeEventListener("resize", this.onResize());

        moduleUnavailabilityManagement.isRegistered = false;
        moduleDealingHoursManagement.isRegistered = false;
        this.$store.unregisterModule("unavailabilityManagement");
        this.$store.unregisterModule("dealingHoursManagement");
    }
};
</script>
