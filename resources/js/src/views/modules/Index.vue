<template>
  <div id="page-modules-list">
    <div class="vx-card p-6">
      <add-form />
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
                <feather-icon icon="ChevronDownIcon" svgClasses="h-4 w-4" />
              </div>

              <vs-dropdown-menu>
                <vs-dropdown-item @click="confirmDeleteItem('delete')">
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
        <vs-dropdown vs-trigger-click class="cursor-pointer">
          <div
            class="p-4 border border-solid d-theme-border-grey-light rounded-full d-theme-dark-bg cursor-pointer flex items-center justify-between font-medium"
          >
            <span class="mr-2">
              {{ currentPage * paginationPageSize - (paginationPageSize - 1) }}
              -
              {{
                modulesData.length - currentPage * paginationPageSize > 0
                  ? currentPage * paginationPageSize
                  : modulesData.length
              }}
              sur {{ modulesData.length }}
            </span>
            <feather-icon icon="ChevronDownIcon" svgClasses="h-4 w-4" />
          </div>
          <!-- <vs-button class="btn-drop" type="line" color="primary" icon-pack="feather" icon="icon-chevron-down"></vs-button> -->
          <vs-dropdown-menu>
            <vs-dropdown-item @click="gridApi.paginationSetPageSize(10)">
              <span>10</span>
            </vs-dropdown-item>
            <vs-dropdown-item @click="gridApi.paginationSetPageSize(20)">
              <span>20</span>
            </vs-dropdown-item>
            <vs-dropdown-item @click="gridApi.paginationSetPageSize(25)">
              <span>25</span>
            </vs-dropdown-item>
            <vs-dropdown-item @click="gridApi.paginationSetPageSize(30)">
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
        :rowData="modulesData"
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
import { AgGridVue } from "ag-grid-vue";
import "@sass/vuexy/extraComponents/agGridStyleOverride.scss";
import vSelect from "vue-select";

//CRUD
import AddForm from "./AddForm.vue";
import EditForm from "./EditForm.vue";

// Store Module
import moduleModuleManagement from "@/store/module-management/moduleModuleManagement.js";
import moduleCompanyManagement from "@/store/company-management/moduleCompanyManagement.js";

// Cell Renderer
import CellRendererActions from "@/components/cell-renderer/CellRendererActions.vue";
import CellRendererLink from "./cell-renderer/CellRendererLink.vue";

var modelTitle = "Module";

export default {
  components: {
    AgGridVue,
    vSelect,
    AddForm,
    EditForm,

    // Cell Renderer
    CellRendererActions,
    CellRendererLink,
  },
  data() {
    return {
      searchQuery: "",

      // AgGrid
      gridApi: null,
      gridOptions: {
        localeText: { noRowsToShow: "Aucun module à afficher" },
      },
      defaultColDef: {
        sortable: true,
        resizable: true,
        suppressMenu: true,
      },
      columnDefs: [
        {
          width: 40,
          checkboxSelection: true,
          headerCheckboxSelectionFilteredOnly: true,
          headerCheckboxSelection: true,
        },
        {
          headerName: "Nom",
          field: "name",
          filter: true,
          cellRendererFramework: "CellRendererLink",
        },
        {
          headerName: "Type",
          field: "modulable_type",
          filter: true,
          valueFormatter: ({ value }) =>
            value.includes("Sql") ? "SQL" : "API",
        },
        {
          headerName: "Société",
          field: "company",
          filter: true,
          valueFormatter: ({ value }) => value.name,
        },
        {
          sortable: false,
          headerName: "Actions",
          field: "transactions",
          type: "numericColumn",
          cellRendererFramework: "CellRendererActions",
          cellRendererParams: {
            model: "module",
            modelPlurial: "modules",
            name: (data) => `le module ${data.name}`,
            usesSoftDelete: false,
            withPrompt: true,
          },
        },
      ],

      // Cell Renderer Components
      components: {
        CellRendererLink,
        CellRendererActions,
      },
    };
  },
  computed: {
    modulesData() {
      return this.$store.getters["moduleManagement/getItems"];
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
      return this.$store.getters["moduleManagement/getSelectedItem"].id || 0;
    },
    currentPage: {
      get() {
        if (this.gridApi) return this.gridApi.paginationGetCurrentPage() + 1;
        else return 1;
      },
      set(val) {
        this.gridApi.paginationGoToPage(val - 1);
      },
    },
  },
  methods: {
    updateSearchQuery(val) {
      this.gridApi.setQuickFilter(val);
    },
    confirmDeleteItem(type) {
      let selectedRow = this.gridApi.getSelectedRows();
      let singleModule = selectedRow[0];

      this.$vs.dialog({
        type: "confirm",
        color: "danger",
        title: "Confirmer suppression",
        text:
          this.gridApi.getSelectedRows().length > 1
            ? `Voulez vous vraiment supprimer ces modules ?`
            : `Voulez vous vraiment supprimer le module ${singleModule.name} ?`,
        accept: this.deleteItem,
        acceptText: "Supprimer",
        cancelText: "Annuler",
      });
    },
    deleteItem() {
      const selectedRowLength = this.gridApi.getSelectedRows().length;

      this.gridApi.getSelectedRows().map((selectRow) => {
        this.$store
          .dispatch("moduleManagement/removeItem", selectRow.id)
          .then((data) => {
            this.$vs.notify({
              color: "success",
              title: "Succès",
              text: "Suppression terminée avec succès",
            });
          })
          .catch((err) => {
            this.$vs.notify({
              color: "danger",
              title: "Erreur",
              text: err.message,
            });
          });
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
    if (!moduleModuleManagement.isRegistered) {
      this.$store.registerModule("moduleManagement", moduleModuleManagement);
      moduleModuleManagement.isRegistered = true;
    }
    if (!moduleCompanyManagement.isRegistered) {
      this.$store.registerModule("companyManagement", moduleCompanyManagement);
      moduleCompanyManagement.isRegistered = true;
    }

    this.$store.dispatch("moduleManagement/fetchItems").catch((err) => {
      console.error(err);
    });
    if (this.$store.getters.userHasPermissionTo(`read companies`)) {
      this.$store.dispatch("companyManagement/fetchItems").catch((err) => {
        console.error(err);
      });
    }
  },
  beforeDestroy() {
    window.removeEventListener("resize", this.onResize());

    moduleModuleManagement.isRegistered = false;
    moduleCompanyManagement.isRegistered = false;
    this.$store.unregisterModule("moduleManagement");
    this.$store.unregisterModule("companyManagement");
  },
};
</script>
