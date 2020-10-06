<!-- =========================================================================================
  File Name: workareasList.vue
  Description: workareas List page
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
========================================================================================== -->

<template>
    <div id="page-workareas-list">
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
                                    @click="confirmDeleteRecord('delete')"
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

                                <vs-dropdown-item
                                    @click="confirmDeleteRecord('archive')"
                                >
                                    <span class="flex items-center">
                                        <feather-icon
                                            icon="ArchiveIcon"
                                            svgClasses="h-4 w-4"
                                            class="mr-2"
                                        />
                                        <span>Archiver</span>
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
                                workareasData.length -
                                    currentPage * paginationPageSize >
                                0
                                    ? currentPage * paginationPageSize
                                    : workareasData.length
                            }}
                            sur {{ workareasData.length }}
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
                :rowData="workareasData"
                rowSelection="multiple"
                colResizeDefault="shift"
                :animateRows="true"
                :floatingFilter="false"
                :pagination="true"
                :paginationPageSize="paginationPageSize"
                :suppressPaginationPanel="true"
                :enableRtl="$vs.rtl"
            ></ag-grid-vue>

            <vs-pagination :total="totalPages" :max="7" v-model="currentPage" />
        </div>

        <edit-form :itemId="itemIdToEdit" v-if="itemIdToEdit" />
    </div>
</template>

<script>
var model = "workarea";
var modelPlurial = "workareas";
var modelTitle = "Ilot";

import { AgGridVue } from "ag-grid-vue";
import "@sass/vuexy/extraComponents/agGridStyleOverride.scss";
import vSelect from "vue-select";

// Store Module
import moduleSkillManagement from "@/store/skill-management/moduleSkillManagement.js";
import moduleWorkareaManagement from "@/store/workarea-management/moduleWorkareaManagement.js";
import moduleCompanyManagement from "@/store/company-management/moduleCompanyManagement.js";

//CRUD
import AddForm from "./AddForm.vue";
import EditForm from "./EditForm.vue";

// Cell Renderer
import CellRendererLink from "./cell-renderer/CellRendererLink.vue";
import CellRendererRelations from "./cell-renderer/CellRendererRelations.vue";
import CellRendererRelationSkills from "./cell-renderer/CellRendererRelationSkills.vue";
import CellRendererActions from "@/components/cell-renderer/CellRendererActions.vue";

// Components
import RefreshModule from "@/components/inputs/buttons/RefreshModule.vue";

var modelTitle = "Ilot";

export default {
    components: {
        AgGridVue,
        vSelect,

        AddForm,
        EditForm,

        // Cell Renderer
        CellRendererLink,
        CellRendererActions,
        CellRendererRelations,
        CellRendererRelationSkills,

        // Components
        RefreshModule
    },
    data() {
        return {
            searchQuery: "",

            // AgGrid
            gridApi: null,
            gridOptions: {
                localeText: { noRowsToShow: "Aucun îlot à afficher" }
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
                    headerCheckboxSelectionFilteredOnly: true,
                    headerCheckboxSelection: true,
                    resizable: true
                },
                {
                    headerName: "Nom",
                    field: "name",
                    filter: true
                },
                {
                    headerName: "Société",
                    field: "company",
                    filter: true,
                    cellRendererFramework: "CellRendererRelations"
                },
                {
                    headerName: "Compétences",
                    field: "skills",
                    cellRendererFramework: "CellRendererRelationSkills"
                },
                {
                    headerName: "Maximum opérateurs",
                    field: "max_users",
                    filter: true,
                    width: 110
                },
                {
                    sortable: false,
                    headerName: "Actions",
                    field: "transactions",
                    type: "numericColumn",
                    cellRendererFramework: "CellRendererActions",
                    cellRendererParams: {
                        model: "workarea",
                        modelPlurial: "workareas",
                        withPrompt: true,
                        name: data => `l'îlot ${data.name}`
                    },
                    width: 60
                }
            ],

            // Cell Renderer Components
            components: {
                CellRendererLink,
                CellRendererActions,
                CellRendererRelations,
                CellRendererRelationSkills
            }
        };
    },
    computed: {
        workareasData() {
            return this.$store.state.workareaManagement.workareas;
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
            return this.$store.state.workareaManagement.workarea.id || 0;
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
        updateSearchQuery(val) {
            this.gridApi.setQuickFilter(val);
        },
        addRecord() {
            this.$router.push(`/${modelPlurial}/${model}-add/`).catch(() => {});
        },
        confirmDeleteRecord(type) {
            let selectedRow = this.gridApi.getSelectedRows();
            let singleWorkarea = selectedRow[0];

            this.$vs.dialog({
                type: "confirm",
                color: "danger",
                title:
                    type === "delete"
                        ? "Confirmer suppression"
                        : "Confirmer archivage",
                text:
                    type === "delete" &&
                    this.gridApi.getSelectedRows().length > 1
                        ? `Voulez vous vraiment supprimer ces îlots ?`
                        : type === "delete" &&
                          this.gridApi.getSelectedRows().length === 1
                        ? `Voulez vous vraiment supprimer l'îlot ${singleWorkarea.name} ?`
                        : this.gridApi.getSelectedRows().length > 1
                        ? `Voulez vous vraiment archiver ces îlots ?`
                        : `Voulez vous vraiment archiver l'îlot ${singleWorkarea.name} ?`,
                accept:
                    type === "delete" ? this.deleteRecord : this.archiveRecord,
                acceptText: type === "delete" ? "Supprimer" : "Archiver",
                cancelText: "Annuler"
            });
        },
        deleteRecord() {
            const selectedRowLength = this.gridApi.getSelectedRows().length;

            this.gridApi.getSelectedRows().map(selectRow => {
                this.$store
                    .dispatch(
                        "workareaManagement/forceRemoveItem",
                        selectRow.id
                    )
                    .then(data => {
                        if (selectedRowLength === 1) {
                            this.showDeleteSuccess("delete", selectedRowLength);
                        }
                    })
                    .catch(err => {
                        console.error(err);
                    });
            });
            if (selectedRowLength > 1) {
                this.showDeleteSuccess("delete", selectedRowLength);
            }
        },
        archiveRecord() {
            const selectedRowLength = this.gridApi.getSelectedRows().length;
            this.gridApi.getSelectedRows().map(selectRow => {
                this.$store
                    .dispatch("workareaManagement/removeItem", selectRow.id)
                    .then(data => {
                        if (selectedRowLength === 1) {
                            this.showDeleteSuccess(
                                "archive",
                                selectedRowLength
                            );
                        }
                    })
                    .catch(err => {
                        console.error(err);
                    });
            });
            if (selectedRowLength > 1) {
                this.showDeleteSuccess("archive", selectedRowLength);
            }
        },
        showDeleteSuccess(type, selectedRowLength) {
            this.$vs.notify({
                color: "success",
                title: modelTitle,
                text:
                    type === "delete" && selectedRowLength > 1
                        ? `Îlots supprimés`
                        : type === "delete" && selectedRowLength === 1
                        ? `Îlot supprimé`
                        : selectedRowLength > 1
                        ? `Îlots archivés`
                        : `Îlot archivé`
            });
        },
        onResize(event) {
            if (this.gridApi) {
                // refresh the grid
                this.gridApi.refreshView();

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
            this.gridApi.refreshView();

            // resize columns in the grid to fit the available space
            this.gridApi.showLoadingOverlay();

            this.gridApi.sizeColumnsToFit();
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
        if (!moduleWorkareaManagement.isRegistered) {
            this.$store.registerModule(
                "workareaManagement",
                moduleWorkareaManagement
            );
            moduleWorkareaManagement.isRegistered = true;
        }
        if (!moduleSkillManagement.isRegistered) {
            this.$store.registerModule(
                "skillManagement",
                moduleSkillManagement
            );
            moduleSkillManagement.isRegistered = true;
        }
        if (!moduleCompanyManagement.isRegistered) {
            this.$store.registerModule(
                "companyManagement",
                moduleCompanyManagement
            );
            moduleCompanyManagement.isRegistered = true;
        }
        this.$store.dispatch("workareaManagement/fetchItems").catch(err => {
            console.error(err);
        });
        this.$store.dispatch("companyManagement/fetchItems").catch(err => {
            console.error(err);
        });
        this.$store.dispatch("skillManagement/fetchItems").catch(err => {
            console.error(err);
        });
    },
    beforeDestroy() {
        window.removeEventListener("resize", this.onResize());

        moduleWorkareaManagement.isRegistered = false;
        moduleSkillManagement.isRegistered = false;
        moduleCompanyManagement.isRegistered = false;
        this.$store.unregisterModule("workareaManagement");
        this.$store.unregisterModule("skillManagement");
        this.$store.unregisterModule("companyManagement");
    }
};
</script>

<style lang="scss">
#page-workareas-list {
    .workareas-list-filters {
        .vs__actions {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-58%);
        }
    }
}
</style>
