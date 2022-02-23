<!-- =========================================================================================
  File Name: SupplyList.vue
  Description: Supply List page
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
========================================================================================== -->

<template>
    <div id="page-supplies-list">
        <div class="vx-card p-6">
            <vs-row
                vs-type="flex"
                vs-justify="space-between"
                vs-align="center"
                vs-w="12"
            >
                <vs-col
                    vs-type="flex"
                    vs-justify="flex-start"
                    vs-align="center"
                    vs-w="2"
                    vs-sm="6"
                >
                    <add-form />
                </vs-col>
                <vs-col
                    vs-type="flex"
                    vs-justify="flex-end"
                    vs-align="center"
                    vs-w="2"
                    vs-sm="6"
                >
                    <refresh-module />
                </vs-col>
            </vs-row>
            <div class="flex flex-wrap items-center">
                <div class="flex-grow">
                    <vs-row vs-type="flex">
                        <!-- <vs-button class="mb-4 md:mb-0" @click="gridApi.exportDataAsCsv()">Export as CSV</vs-button> -->

                        <multiple-actions
                            model="supply"
                            model-plurial="supplies"
                            :items="selectedItems"
                            :foot-notes="{
                                restore: restoreFootNote,
                                archive: archiveFootNote,
                                delete: deleteFootNote
                            }"
                            @on-action="onAction"
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
                            {{
                                currentPage * paginationPageSize -
                                    (paginationPageSize - 1)
                            }}
                            -
                            {{
                                suppliesData.length -
                                    currentPage * paginationPageSize >
                                0
                                    ? currentPage * paginationPageSize
                                    : suppliesData.length
                            }}
                            sur {{ suppliesData.length }}
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
                :rowData="suppliesData"
                rowSelection="multiple"
                colResizeDefault="shift"
                :animateRows="true"
                :floatingFilter="false"
                :pagination="false"
                :paginationPageSize="paginationPageSize"
                :suppressPaginationPanel="true"
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

//CRUD
import AddForm from "./AddForm.vue";
import EditForm from "./EditForm.vue";

// Store Module
import moduleSupplyManagement from "@/store/supply-management/moduleSupplyManagement.js";
import moduleCompanyManagement from "@/store/company-management/moduleCompanyManagement.js";
// import moduleWorkareaManagement from "@/store/workarea-management/moduleWorkareaManagement.js";
import moduleTaskManagement from "@/store/task-management/moduleTaskManagement.js";

// Cell Renderer
import CellRendererLink from "./cell-renderer/CellRendererLink.vue";
import CellRendererRelations from "./cell-renderer/CellRendererRelations.vue";
import CellRendererActions from "@/components/cell-renderer/CellRendererActions.vue";
import CellRendererItemsList from "@/components/cell-renderer/CellRendererItemsList.vue";

// Components
import RefreshModule from "@/components/inputs/buttons/RefreshModule.vue";
import MultipleActions from "@/components/inputs/buttons/MultipleActions.vue";

// Mixins
import { multipleActionsMixin } from "@/mixins/lists";

var modelTitle = "Compétence";

export default {
    mixins: [multipleActionsMixin],
    components: {
        AgGridVue,
        AddForm,
        EditForm,

        // Cell Renderer
        CellRendererLink,
        CellRendererActions,
        CellRendererRelations,
        CellRendererItemsList,

        // Components
        RefreshModule,
        MultipleActions
    },
    data() {
        return {
            searchQuery: "",

            // AgGrid
            gridApi: null,
            gridOptions: {
                localeText: { noRowsToShow: "Aucune approvisionnement à afficher" }
            },
            defaultColDef: {
                sortable: true,
                resizable: true,
                suppressMenu: true
            },
            columnDefs: [
                {
                    sortable: false,
                    filter: false,
                    width: 40,
                    checkboxSelection: true,
                    headerCheckboxSelectionFilteredOnly: false,
                    headerCheckboxSelection: true
                },
                {
                    headerName: "Nom",
                    field: "name",
                    filter: true
                },
                {
                    headerName: "Société",
                    field: "company",
                    hide: !this.$store.state.AppActiveUser.is_admin,
                    filter: true,
                    cellRendererFramework: "CellRendererRelations"
                },
                {
                    sortable: false,
                    headerName: "Actions",
                    field: "transactions",
                    type: "numericColumn",
                    cellRendererFramework: "CellRendererActions",
                    cellRendererParams: {
                        model: "supply",
                        modelPlurial: "supplies",
                        name: data => `l'approvisionnement ${data.name}`,
                        withPrompt: true,
                        footNotes: {
                            restore: this.restoreFootNote(false),
                            archive: this.archiveFootNote(false),
                            delete: this.deleteFootNote(false)
                        }
                    }
                }
            ],

            // Cell Renderer Components
            components: {
                CellRendererLink,
                CellRendererActions,
                CellRendererRelations
            }
        };
    },
    computed: {
        suppliesData() {
            return this.$store.state.supplyManagement.supplies;
        },
        paginationPageSize() {
            if (this.gridApi) return this.gridApi.paginationGetPageSize();
            else return 10;
        },
        totalPages() {
            if (this.gridApi) return this.gridApi.paginationGetTotalPages();
            else return 0;
        },
        itemIdToEdit() {
            return this.$store.state.supplyManagement.supply.id || 0;
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
        authorizedTo(action, model = "supplies") {
            return this.$store.getters.userHasPermissionTo(
                `${action} ${model}`
            );
        },
        updateSearchQuery(val) {
            this.gridApi.setQuickFilter(val);
        },
        onResize(event) {
            if (this.gridApi) {
                // refresh the grid
                this.gridApi.redrawRows();

                // resize columns in the grid to fit the available space
                this.gridApi.sizeColumnsToFit();
            }
        },
        restoreFootNote(multiple) {
            return multiple
                ? "Si vous restaurez les approvisionnements elles seront utilisées à nouveau par les tâches associés."
                : "Si vous restaurez l' approvisionnement elle sera utilisée à nouveau les tâches.";
        },
        archiveFootNote(multiple) {
            return multiple
                ? "Si vous archivez les approvisionnements elles seront utilisées à nouveau par les tâches associés."
                : "Si vous archivez l'approvisionnement elle sera utilisée à nouveau les tâches.";
        },
        deleteFootNote(multiple) {
            return multiple
                ? "Si vous supprimez les approvisionnement elles ne seront plus associées à ses tâches."
                : "Si vous supprimez l'approvisionnement elle ne sera plus associée à ses tâches.";
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
        if (!moduleSupplyManagement.isRegistered) {
            this.$store.registerModule(
                "supplyManagement",
                moduleSupplyManagement
            );
            moduleSupplyManagement.isRegistered = true;
        }
        if (!moduleCompanyManagement.isRegistered) {
            this.$store.registerModule(
                "companyManagement",
                moduleCompanyManagement
            );
            moduleCompanyManagement.isRegistered = true;
        }
        // if (!moduleWorkareaManagement.isRegistered) {
        //     this.$store.registerModule(
        //         "workareaManagement",
        //         moduleWorkareaManagement
        //     );
        //     moduleWorkareaManagement.isRegistered = true;
        // }
        if (!moduleTaskManagement.isRegistered) {
            this.$store.registerModule("taskManagement", moduleTaskManagement);
            moduleTaskManagement.isRegistered = true;
        }

        this.$store
            .dispatch("supplyManagement/fetchItems", {
                loads: ["company"],
                order_by: "name",
                with_trashed: true
            })
            .catch(err => {
                console.error(err);
            });
        if (this.authorizedTo("read", "companies")) {
            this.$store.dispatch("companyManagement/fetchItems").catch(err => {
                console.error(err);
            });
        }
        // this.$store.dispatch("workareaManagement/fetchItems").catch(err => {
        //     console.error(err);
        // });

        this.$store.dispatch("taskManagement/fetchItems").catch(err => {
            console.error(err);
        });
    },
    beforeDestroy() {
        window.removeEventListener("resize", this.onResize());

        moduleSupplyManagement.isRegistered = false;
        // moduleCompanyManagement.isRegistered = false;
        // moduleWorkareaManagement.isRegistered = false;
        // moduleTaskManagement.isRegistered = false;

        this.$store.unregisterModule("supplyManagement");
        // this.$store.unregisterModule("companyManagement");
        // this.$store.unregisterModule("workareaManagement");
        // this.$store.unregisterModule("taskManagement");
    }
};
</script>

<style lang="scss">
#page-supplies-list {
    .supplies-list-filters {
        .vs__actions {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-58%);
        }
    }
}
</style>
