<template>
  <div id="page-hours-list">
    <div class="vx-card p-6">
      <div class="d-theme-dark-light-bg flex flex-row justify-start pb-3">
        <feather-icon icon="FilterIcon" svgClasses="h-6 w-6" />
        <h4 class="ml-3">Filtres</h4>
      </div>
      <div class="flex flex-wrap items-center"></div>
    </div>
    <div class="vx-card p-6 mt-1">
      <div class="d-theme-dark-light-bg flex flex-row justify-start pb-3">
        <feather-icon icon="ClockIcon" svgClasses="h-6 w-6" />
        <h4 class="ml-3">Résumé</h4>
      </div>
      <div class="flex flex-wrap items-center">
        <div></div>
      </div>
    </div>
    <div class="vx-card p-6 mt-1">
      <div class="d-theme-dark-light-bg flex flex-row justify-between items-center pb-3">
        <div class="flex flex-row justify-start items-center">
          <feather-icon icon="CalendarIcon" svgClasses="h-6 w-6" />
          <h4 class="ml-3">Heures effectuées</h4>
          <div class="px-6 py-2" v-if="authorizedToPublish">
            <vs-button @click="addRecord">Ajouter des heures</vs-button>
          </div>
        </div>
        <vs-button type="border" @click="onExport">
          <div class="flex flex-row">
            <feather-icon icon="DownloadIcon" svgClasses="h-5 w-5" class="mr-2" />Exporter
          </div>
        </vs-button>
      </div>
      <div class="flex flex-wrap items-center">
        <!-- ITEMS PER PAGE -->
        <div class="flex-grow">
          <vs-dropdown vs-trigger-click class="cursor-pointer">
            <div
              class="p-4 border border-solid d-theme-border-grey-light rounded-full d-theme-dark-bg cursor-pointer flex items-center justify-between font-medium"
            >
              <span
                class="mr-2"
              >{{ currentPage * paginationPageSize - (paginationPageSize - 1) }} - {{ hoursData.length - currentPage * paginationPageSize > 0 ? currentPage * paginationPageSize : hoursData.length }} of {{ hoursData.length }}</span>
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

        <!-- TABLE ACTION COL-2: SEARCH & EXPORT AS CSV -->
        <vs-input
          class="sm:mr-4 mr-0 sm:w-auto w-full sm:order-normal order-3 sm:mt-0 mt-4"
          v-model="searchQuery"
          @input="updateSearchQuery"
          placeholder="Rechercher..."
        />
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
            <vs-dropdown-item v-if="authorizedToDelete">
              <span class="flex items-center">
                <feather-icon icon="TrashIcon" svgClasses="h-4 w-4" class="mr-2" />
                <span>Supprimer</span>
              </span>
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
        :columnDefs="columnDefs"
        :defaultColDef="defaultColDef"
        :rowData="hoursData"
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
  </div>
</template>

<script>
import { AgGridVue } from "ag-grid-vue";
import "@sass/vuexy/extraComponents/agGridStyleOverride.scss";
import vSelect from "vue-select";

// Store Module
import moduleManagement from "@/store/hours-management/moduleHoursManagement.js";

// Cell Renderer
import CellRendererActions from "./cell-renderer/CellRendererActions.vue";
import CellRendererRelations from "./cell-renderer/CellRendererRelations.vue";

import moment from "moment";

var model = "hours";
var modelPlurial = "hours";
var modelTitle = "Heures";

export default {
  components: {
    AgGridVue,
    vSelect,
    // Cell Renderer
    CellRendererActions,
    CellRendererRelations
  },
  data() {
    return {
      searchQuery: "",
      // AgGrid
      gridApi: null,
      gridOptions: {},
      defaultColDef: {
        sortable: true,
        resizable: true,
        suppressMenu: true
      },
      columnDefs: [
        {
          filter: false,
          checkboxSelection: true,
          headerCheckboxSelectionFilteredOnly: false,
          headerCheckboxSelection: true,
          resizable: true
        },
        {
          headerName: "Date",
          field: "date"
        },
        {
          headerName: "Durée",
          field: "duration"
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
          headerName: "Actions",
          field: "transactions",
          cellRendererFramework: "CellRendererActions"
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
      headerVal: ["id", "user", "project", "date", "duration", "description"]
    };
  },
  computed: {
    authorizedToPublish() {
      return (
        this.$store.getters.userHasPermissionTo(`publish ${modelPlurial}`) > -1
      );
    },
    authorizedToDelete() {
      return (
        this.$store.getters.userHasPermissionTo(`delete ${modelPlurial}`) > -1
      );
    },
    authorizedToEdit() {
      return (
        this.$store.getters.userHasPermissionTo(`edit ${modelPlurial}`) > -1
      );
    },
    itemIdToEdit() {
      return this.$store.state.hoursManagement.hours.id || 0;
    },
    hoursData() {
      return this.$store.state.hoursManagement.allHours;
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
        if (this.gridApi) return this.gridApi.paginationGetCurrentPage() + 1;
        else return 1;
      },
      set(val) {
        this.gridApi.paginationGoToPage(val - 1);
      }
    }
  },
  methods: {
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
    onExport() {
      import("@/vendor/Export2Excel").then(excel => {
        const data = this.formatJson(this.headerVal, this.hoursData);
        excel.export_json_to_excel({
          header: this.headerTitle,
          data,
          filename: moment().format("YYYY-MM-DD HH:mm") + "_Heures_effectuées",
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

    window.addEventListener("resize", this.onResize);
    if (this.gridApi) {
      // refresh the grid
      this.gridApi.refreshView();

      // resize columns in the grid to fit the available space
      this.gridApi.sizeColumnsToFit();
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
    if (!moduleManagement.isRegistered) {
      this.$store.registerModule("hoursManagement", moduleManagement);
      moduleManagement.isRegistered = true;
    }
    this.$store.dispatch("hoursManagement/fetchItems").catch(err => {
      console.error(err);
    });
  },
  beforeDestroy() {
    window.removeEventListener("resize", this.onResize());
    moduleManagement.isRegistered = false;
    this.$store.unregisterModule("hoursManagement");
  }
};
</script>
