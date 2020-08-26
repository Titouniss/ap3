<!-- =========================================================================================
  File Name: CustomersList.vue
  Description: Customers List page
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
========================================================================================== -->

<template>
    <div id="page-users-list">
        <div class="vx-card w-full p-6">
            <add-form v-if="authorizedToPublish" />

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
                                    v-if="authorizedToDelete"
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
                                    v-if="authorizedToDelete"
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
                                customersData.length -
                                    currentPage * paginationPageSize >
                                0
                                    ? currentPage * paginationPageSize
                                    : customersData.length
                            }}
                            sur {{ customersData.length }}
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
                :rowData="customersData"
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
        <edit-form
            :reload="customersData"
            :itemId="itemIdToEdit"
            v-if="itemIdToEdit && authorizedToEdit"
        />
    </div>
</template>

<script>
import { AgGridVue } from "ag-grid-vue";
import "@sass/vuexy/extraComponents/agGridStyleOverride.scss";
import vSelect from "vue-select";

//CRUD
import EditForm from "./EditForm.vue";
import AddForm from "./AddForm.vue";

// Store Module
import moduleCustomerManagement from "@/store/customer-management/moduleCustomerManagement.js";
import moduleCompanyManagement from "@/store/company-management/moduleCompanyManagement.js";

// Cell Renderer
import CellRendererActions from "@/components/cell-renderer/CellRendererActions.vue";
import CellRendererBoolean from "./cell-renderer/CellRendererBoolean.vue";
import CellRendererRelations from "./cell-renderer/CellRendererRelations.vue";

var model = "customer";
var modelPlurial = "customers";
var modelTitle = "Clients";

export default {
    components: {
        AgGridVue,
        vSelect,
        // Crud
        EditForm,
        AddForm,
        // Cell Renderer
        CellRendererActions,
        CellRendererBoolean,
        CellRendererRelations
    },
    data() {
        return {
            searchQuery: "",

            // AgGrid
            gridApi: null,
            gridOptions: {
                localeText: { noRowsToShow: "Aucun client à afficher" }
            },
            defaultColDef: {
                resizable: true,
                suppressMenu: true
            },
            columnDefs: [
                {
                    filter: false,
                    checkboxSelection: true,
                    headerCheckboxSelectionFilteredOnly: false,
                    headerCheckboxSelection: true,
                    width: 40
                },
                {
                    headerName: "Société",
                    field: "name",
                    filter: true,
                    sortable: true
                },
                {
                    headerName: "Nom",
                    field: "lastname",
                    filter: true,
                    sortable: true
                },
                {
                    headerName: "Siret",
                    field: "siret",
                    filter: true,
                    sortable: true
                },
                {
                    headerName: "Type",
                    field: "professional",
                    filter: true,
                    sortable: true,
                    cellRendererFramework: "CellRendererBoolean"
                },
                {
                    headerName: "Société",
                    field: "company",
                    filter: true,
                    cellRendererFramework: "CellRendererRelations"
                },
                {
                    headerName: "Actions",
                    field: "transactions",
                    type: "numericColumn",
                    cellRendererFramework: "CellRendererActions",
                    cellRendererParams: {
                        model: "customer",
                        modelPlurial: "customers",
                        withPrompt: true,
                        name: data => `le client ${data.lastname}`,
                        linkedTables: ["projets", "tâches"]
                    }
                }
            ],
            // Cell Renderer Components
            components: {
                CellRendererActions,
                CellRendererBoolean
            }
        };
    },
    computed: {
        itemIdToEdit() {
            return this.$store.state.customerManagement.customer.id || 0;
        },
        customersData() {
            return this.activeUserRole() == "superAdmin"
                ? this.$store.state.customerManagement.customers
                : this.$store.state.customerManagement.customers.filter(
                      c =>
                          c.company.id ===
                          this.$store.state.AppActiveUser.company_id
                  );
        },
        authorizedToPublish() {
            return this.$store.getters.userHasPermissionTo(
                `publish ${modelPlurial}`
            );
        },
        authorizedToDelete() {
            return this.$store.getters.userHasPermissionTo(
                `delete ${modelPlurial}`
            );
        },
        authorizedToEdit() {
            return this.$store.getters.userHasPermissionTo(
                `edit ${modelPlurial}`
            );
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
        authorizedTo(action, model = "customers") {
            return this.$store.getters.userHasPermissionTo(
                `${action} ${model}`
            );
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
            this.roleFilter = this.statusFilter = this.isVerifiedFilter = this.departmentFilter = {
                label: "All",
                value: "all"
            };

            this.$refs.filterCard.removeRefreshAnimation();
        },
        updateSearchQuery(val) {
            this.gridApi.setQuickFilter(val);
        },
        confirmDeleteRecord(type) {
            let selectedRow = this.gridApi.getSelectedRows();
            let singleCustomer = selectedRow[0];
            console.log(["singleCustomer", singleCustomer]);

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
                        ? `Voulez vous vraiment supprimer ces clients ?`
                        : type === "delete" &&
                          this.gridApi.getSelectedRows().length === 1
                        ? `Voulez vous vraiment supprimer le client ${singleCustomer.lastname} ?`
                        : this.gridApi.getSelectedRows().length > 1
                        ? `Voulez vous vraiment archiver ces clients ?`
                        : `Voulez vous vraiment archiver le client ${singleCustomer.lastname} ?`,
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
                        "customerManagement/forceRemoveItem",
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
                    .dispatch("customerManagement/removeItem", selectRow.id)
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
                        ? `Clients supprimés`
                        : type === "delete" && selectedRowLength === 1
                        ? `Client supprimé`
                        : selectedRowLength > 1
                        ? `Clients archivés`
                        : `Client archivé`
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
        activeUserRole() {
            const user = this.$store.state.AppActiveUser;
            if (user.roles && user.roles.length > 0) {
                return user.roles[0].name;
            }
            return false;
        }
    },
    mounted() {
        this.gridApi = this.gridOptions.api;

        // Hide company column ?
        this.gridOptions.columnApi.setColumnVisible(
            "company",
            this.activeUserRole() == "superAdmin" ? true : false
        );

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
        if (!moduleCustomerManagement.isRegistered) {
            this.$store.registerModule(
                "customerManagement",
                moduleCustomerManagement
            );
            moduleCustomerManagement.isRegistered = true;
        }
        if (!moduleCompanyManagement.isRegistered) {
            this.$store.registerModule(
                "companyManagement",
                moduleCompanyManagement
            );
            moduleCompanyManagement.isRegistered = true;
        }

        if (this.authorizedTo("read", "customers")) {
            this.$store.dispatch("customerManagement/fetchItems");
        }

        this.$store.dispatch("companyManagement/fetchItems").catch(err => {
            console.error(err);
        });
    },
    beforeDestroy() {
        window.removeEventListener("resize", this.onResize());

        moduleCustomerManagement.isRegistered = false;
        moduleCompanyManagement.isRegistered = false;

        this.$store.unregisterModule("customerManagement");
        this.$store.unregisterModule("companyManagement");
    }
};
</script>

<style lang="scss"></style>
