<!-- =========================================================================================
  File Name: SkillsList.vue
  Description: Skills List page
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
========================================================================================== -->

<template>
    <div id="page-skills-list">
        <div class="vx-card p-6">
            <add-form />
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
                                    @click="confirmDeleteRecord()"
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
                        <span class="mr-2"
                            >{{
                                currentPage * paginationPageSize -
                                    (paginationPageSize - 1)
                            }}
                            -
                            {{
                                skillsData.length -
                                    currentPage * paginationPageSize >
                                0
                                    ? currentPage * paginationPageSize
                                    : skillsData.length
                            }}
                            sur {{ skillsData.length }}</span
                        >
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
                :rowData="skillsData"
                rowSelection="multiple"
                colResizeDefault="shift"
                :animateRows="true"
                :floatingFilter="false"
                :pagination="false"
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
import { AgGridVue } from "ag-grid-vue";
import "@sass/vuexy/extraComponents/agGridStyleOverride.scss";
import vSelect from "vue-select";

//CRUD
import AddForm from "./AddForm.vue";
import EditForm from "./EditForm.vue";

// Store Module
import moduleSkillManagement from "@/store/skill-management/moduleSkillManagement.js";
import moduleCompanyManagement from "@/store/company-management/moduleCompanyManagement.js";

// Cell Renderer
import CellRendererLink from "./cell-renderer/CellRendererLink.vue";
import CellRendererRelations from "./cell-renderer/CellRendererRelations.vue";
import CellRendererActions from "./cell-renderer/CellRendererActions.vue";

var modelTitle = "Compétence";

export default {
    components: {
        AgGridVue,
        vSelect,
        AddForm,
        EditForm,

        // Cell Renderer
        CellRendererLink,
        CellRendererActions,
        CellRendererRelations
    },
    data() {
        return {
            searchQuery: "",

            // AgGrid
            gridApi: null,
            gridOptions: {
                localeText: { noRowsToShow: "Pas de compétences à afficher" }
            },
            defaultColDef: {
                sortable: true,
                resizable: true,
                suppressMenu: true
            },
            columnDefs: [
                {
                    filter: false,
                    width: 30,
                    checkboxSelection: true,
                    headerCheckboxSelectionFilteredOnly: false,
                    headerCheckboxSelection: true,
                    resizable: true
                },
                {
                    headerName: "Nom",
                    field: "name",
                    filter: true,
                    width: 100,
                    resizable: true
                },
                {
                    headerName: "Société",
                    field: "company",
                    filter: true,
                    width: 100,
                    resizable: true,
                    cellRendererFramework: "CellRendererRelations"
                },
                {
                    headerName: "Actions",
                    field: "transactions",
                    width: 30,
                    resizable: true,
                    cellRendererFramework: "CellRendererActions"
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
    watch: {},
    computed: {
        skillsData() {
            return this.$store.state.skillManagement.skills;
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
            return this.$store.state.skillManagement.skill.id || 0;
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
        authorizedTo(action, model = "skills") {
            return this.$store.getters.userHasPermissionTo(
                `${action} ${model}`
            );
        },
        updateSearchQuery(val) {
            this.gridApi.setQuickFilter(val);
        },
        confirmDeleteRecord() {
            let selectedRow = this.gridApi.getSelectedRows();
            let singleSkill = selectedRow[0];

            this.$vs.dialog({
                type: "confirm",
                color: "danger",
                title: "Confirmer suppression",
                text:
                    this.gridApi.getSelectedRows().length > 1
                        ? `Voulez vous vraiment supprimer cette compétence ?`
                        : `Voulez vous vraiment archiver la compétences ${singleSkill.name} ?`,
                accept: this.deleteRecord,
                acceptText: "Supprimer",
                cancelText: "Annuler"
            });
        },
        deleteRecord() {
            const selectedRowLength = this.gridApi.getSelectedRows().length;
            this.gridApi.getSelectedRows().map(selectRow => {
                this.$store
                    .dispatch("skillManagement/forceRemoveItem", selectRow.id)
                    .then(data => {
                        console.log(["data", data]);
                        if (selectedRowLength === 1) {
                            this.showDeleteSuccess(selectedRowLength);
                        }
                    })
                    .catch(err => {
                        console.error(err);
                    });
            });
        },
        showDeleteSuccess(selectedRowLength) {
            this.$vs.notify({
                color: "success",
                title: modelTitle,
                text:
                    selectedRowLength > 1
                        ? `Compétences supprimées`
                        : `Compétence supprimée`
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
        this.$store.dispatch("skillManagement/fetchItems").catch(err => {
            console.error(err);
        });
        if (this.authorizedTo("read", "companies")) {
            this.$store.dispatch("companyManagement/fetchItems").catch(err => {
                console.error(err);
            });
        }
    },
    beforeDestroy() {
        window.removeEventListener("resize", this.onResize());

        moduleSkillManagement.isRegistered = false;
        moduleCompanyManagement.isRegistered = false;
        this.$store.unregisterModule("skillManagement");
        this.$store.unregisterModule("companyManagement");
    }
};
</script>

<style lang="scss">
#page-skills-list {
    .skills-list-filters {
        .vs__actions {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-58%);
        }
    }
}
</style>
