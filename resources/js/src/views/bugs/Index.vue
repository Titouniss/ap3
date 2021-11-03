<template>
    <div id="page-bug-list">
        <div class="vx-card p-6">
            <div class="px-4 pt-3 mb-6">
                <vs-button @click="addRecord" class="w-full"
                    >Remonter un bug</vs-button
                >
            </div>
            <div class="flex flex-wrap items-center">
                <div class="flex-grow">
                    <vs-row vs-type="flex">
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
                            {{
                                currentPage * paginationPageSize -
                                    (paginationPageSize - 1)
                            }}
                            -
                            {{
                                bugsData.length -
                                    currentPage * paginationPageSize >
                                0
                                    ? currentPage * paginationPageSize
                                    : bugsData.length
                            }}
                            sur {{ bugsData.length }}
                        </span>
                        <feather-icon
                            icon="ChevronDownIcon"
                            svgClasses="h-4 w-4"
                        />
                    </div>
                    <!-- <vs-button class="btn-drop" type="line" color="primary" icon-pack="feather" icon="icon-chevron-down"></vs-button> -->
                    <vs-dropdown-menu>
                        <vs-dropdown-item
                            @click="gridApi.paginationSetPageSize(10)"
                        >
                            <span>10</span>
                        </vs-dropdown-item>
                        <vs-dropdown-item
                            @click="gridApi.paginationSetPageSize(20)"
                        >
                            <span>20</span>
                        </vs-dropdown-item>
                        <vs-dropdown-item
                            @click="gridApi.paginationSetPageSize(25)"
                        >
                            <span>25</span>
                        </vs-dropdown-item>
                        <vs-dropdown-item
                            @click="gridApi.paginationSetPageSize(30)"
                        >
                            <span>30</span>
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
                :rowData="bugsData"
                rowSelection="multiple"
                colResizeDefault="shift"
                :animateRows="true"
                :floatingFilter="false"
                :pagination="true"
                :paginationPageSize="paginationPageSize"
                :suppressPaginationPanel="true"
                :enableRtl="$vs.rtl"
                :isRowSelectable="row => !isRowDisabled(row.data)"
            ></ag-grid-vue>

            <vs-pagination :total="totalPages" :max="7" v-model="currentPage" />
        </div>
    </div>
</template>

<script>
import { AgGridVue } from "ag-grid-vue";
import "@sass/vuexy/extraComponents/agGridStyleOverride.scss";
import moment from "moment";

// Store Module
import moduleBugManagement from "@/store/bug-management/moduleBugManagement.js";
import moduleUserManagement from "@/store/user-management/moduleUserManagement.js";

// Cell Renderer
import CellRendererActions from "@/components/cell-renderer/CellRendererActions.vue";
import CellRendererLink from "./cell-renderer/CellRendererLink.vue";

var model = "bug";
var modelPlurial = "bugs";
var modelTitle = "Bug";

export default {
    components: {
        AgGridVue,
        // Cell Renderer
        CellRendererActions,
        CellRendererLink
    },
    data() {
        return {
            searchQuery: "",
            // AgGrid
            gridApi: null,
            gridOptions: {
                localeText: { noRowsToShow: "Aucun bug à afficher" }
            },
            defaultColDef: {
                sortable: true,
                resizable: true,
                suppressMenu: true
            },
            columnDefs: [
                {
                    width: 50,
                    cellRendererFramework: "CellRendererLink"
                },
                {
                    headerName: "Module",
                    field: "module"
                },
                {
                    headerName: "Type",
                    field: "type"
                },
                {
                    headerName: "Description",
                    field: "description"
                },
                {
                    headerName: "Remonté par",
                    field: "created_by",
                    cellRenderer: data => {
                        return data.value.lastname + " " + data.value.firstname;
                    }
                },
                {
                    headerName: "Remonté le",
                    field: "created_at",
                    cellRenderer: data => {
                        moment.locale("fr");
                        return moment(data.value).format("DD MMMM YYYY");
                    }
                },
                {
                    headerName: "Statut",
                    field: "status"
                }
            ],

            // Cell Renderer Components
            components: {
                CellRendererActions
            }
        };
    },
    computed: {
        isAdmin() {
            return this.$store.state.AppActiveUser.is_admin;
        },
        itemIdToEdit() {
            return this.$store.state.bugManagement.bug.id || 0;
        },
        bugsData() {
            return this.$store.state.bugManagement.bugs;
        },
        paginationPageSize() {
            if (this.gridApi) return this.gridApi.paginationGetPageSize();
            else return 10;
        },
        totalPages() {
            if (this.gridApi) return this.gridApi.paginationGetTotalPages();
            else return 0;
        },
        currentPage: {
            get() {
                if (this.gridApi)
                    return this.gridApi.paginationGetCurrentPage() + 1;
                else return 1;
            },
            set(val) {
                this.gridApi.paginationGoToPage(val - 1);
            }
        }
    },
    methods: {
        authorizedTo(action, model = modelPlurial) {
            return this.$store.getters.userHasPermissionTo(
                `${action} ${model}`
            );
        },
        isRowDisabled(data) {
            if (this.isAdmin) {
                return data.code === "super_admin";
            } else {
                return data.company_id === null && data.is_public;
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
            this.bugFilter = this.statusFilter = this.isVerifiedFilter = this.departmentFilter = {
                label: "All",
                value: "all"
            };

            this.$refs.filterCard.removeRefreshAnimation();
        },
        updateSearchQuery(val) {
            this.gridApi.setQuickFilter(val);
        },
        confirmDeleteRecord() {
            let selectedRow = this.gridApi.getSelectedRows();
            let singleBug = selectedRow[0];

            this.$vs.dialog({
                type: "confirm",
                color: "danger",
                title: "Confirmer suppression",
                text:
                    this.gridApi.getSelectedRows().length > 1
                        ? `Voulez vous vraiment supprimer ces bugs ?`
                        : `Voulez vous vraiment supprimer le bug ${singleBug.name} ?`,
                accept: this.deleteRecord,
                acceptText: "Supprimer",
                cancelText: "Annuler"
            });
        },
        deleteRecord() {
            this.gridApi.getSelectedRows().map(selectRow => {
                this.$store
                    .dispatch("bugManagement/removeItem", selectRow.id)
                    .then(data => {
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
                text:
                    this.gridApi.getSelectedRows().length > 1
                        ? `Bugs supprimés?`
                        : `Bug supprimé`
            });
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
        deleteFiles() {
            const ids = this.uploadedFiles.map(item => item.id);
            if (ids.length > 0) {
                this.$store
                    .dispatch("documentManagement/removeItems", ids)
                    .then(response => {
                        this.uploadedFiles = [];
                    })
                    .catch(error => {});
            }
        }
    },
    mounted() {
        this.gridApi = this.gridOptions.api;

        window.addEventListener("resize", this.onResize);
        if (this.gridApi) {
            // refresh the grid
            this.gridApi.refreshView();

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
        if (!this.isAdmin) {
            this.$router.push(`/${modelPlurial}/${model}-add/`).catch(() => {});
        } else {
            if (!moduleBugManagement.isRegistered) {
                this.$store.registerModule(
                    "bugManagement",
                    moduleBugManagement
                );
                moduleBugManagement.isRegistered = true;
            }
            if (!moduleUserManagement.isRegistered) {
                this.$store.registerModule(
                    "userManagement",
                    moduleUserManagement
                );
                moduleUserManagement.isRegistered = true;
            }
            this.$store.dispatch("bugManagement/fetchItems").catch(err => {
                console.error(err);
            });
            this.$store.dispatch("userManagement/fetchItems").catch(err => {
                console.error(err);
            });
        }
    },
    beforeDestroy() {
        window.removeEventListener("resize", this.onResize());
        moduleBugManagement.isRegistered = false;
        moduleUserManagement.isRegistered = false;
        this.$store.unregisterModule("bugManagement");
        this.$store.unregisterModule("userManagement");
    }
};
</script>
