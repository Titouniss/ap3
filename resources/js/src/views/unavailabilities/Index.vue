<template>
  <div>
    <add-form />
    <div class="flex flex-wrap items-center">
      <!-- ITEMS PER PAGE -->
      <div class="flex-grow">
        <vs-dropdown vs-trigger-click class="cursor-pointer">
          <div
            class="p-4 border border-solid d-theme-border-grey-light rounded-full d-theme-dark-bg cursor-pointer flex items-center justify-between font-medium"
          >
            <span
              class="mr-2"
            >{{ currentPage * paginationPageSize - (paginationPageSize - 1) }} - {{ unavailabilitiesData.length - currentPage * paginationPageSize > 0 ? currentPage * paginationPageSize : unavailabilitiesData.length }} of {{ unavailabilitiesData.length }}</span>
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
        placeholder="Search..."
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
          <vs-dropdown-item>
            <span class="flex items-center">
              <feather-icon icon="TrashIcon" svgClasses="h-4 w-4" class="mr-2" />
              <span>Delete</span>
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
      :rowData="unavailabilitiesData"
      rowSelection="multiple"
      colResizeDefault="shift"
      :animateRows="true"
      :floatingFilter="true"
      :pagination="true"
      :paginationPageSize="paginationPageSize"
      :suppressPaginationPanel="true"
      :enableRtl="$vs.rtl"
    ></ag-grid-vue>

    <vs-pagination :total="totalPages" :max="7" v-model="currentPage" />

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
import moduleUnavailabilityManagement from "@/store/unavailability-management/moduleUnavailabilityManagement.js";

// Cell Renderer
import CellRendererActions from "./cell-renderer/CellRendererActions.vue";
import moment from "moment";

export default {
  components: {
    AgGridVue,
    vSelect,
    AddForm,
    EditForm,

    // Cell Renderer
    CellRendererActions
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
          headerCheckboxSelectionFilteredOnly: true,
          headerCheckboxSelection: true,
          resizable: true
        },
        {
          headerName: "Début",
          field: "starts_at",
          filter: true,
          width: 300,
          valueFormatter: param => this.formatDateTime(param.value)
        },
        {
          headerName: "Fin",
          field: "ends_at",
          filter: true,
          width: 300,
          valueFormatter: param => this.formatDateTime(param.value)
        },
        {
          headerName: "Motif",
          field: "reason",
          filter: true,
          width: 300
        },
        {
          headerName: "Actions",
          field: "transactions",
          width: 150,
          cellRendererFramework: "CellRendererActions"
        }
      ],

      // Cell Renderer Components
      components: {
        CellRendererActions
      }
    };
  },
  computed: {
    unavailabilitiesData() {
      return this.$store.state.unavailabilityManagement.unavailabilities;
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
      return this.$store.state.unavailabilityManagement.unavailability.id || 0;
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
    formatDateTime(value) {
      return moment(value).format("YYYY-MM-DD HH:mm");
    },
    updateSearchQuery(val) {
      this.gridApi.setQuickFilter(val);
    }
  },
  mounted() {
    this.gridApi = this.gridOptions.api;

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
    this.$store.dispatch("unavailabilityManagement/fetchItems").catch(err => {
      console.error(err);
    });
  },
  beforeDestroy() {
    moduleUnavailabilityManagement.isRegistered = false;
    this.$store.unregisterModule("unavailabilityManagement");
  }
};
</script>