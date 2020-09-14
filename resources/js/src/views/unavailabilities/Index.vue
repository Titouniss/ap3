<template>
  <div>
    <div class="mb-base">
      <h6 class="mb-4">Heures supplémentaires</h6>
      <div class="flex items-center mb-4">
        <span class="ml-4">Total :</span>
        <span class="ml-4">{{overtimes}} {{overtimes > 1 ? "heures" : "heure"}}</span>
      </div>
      <div class="flex items-center mb-4">
        <span class="ml-4">{{usedOvertimes > 1 ? "Utilisées :" : "Utilisée :"}}</span>
        <span class="ml-4">{{usedOvertimes}} {{usedOvertimes > 1 ? "heures" : "heure"}}</span>
      </div>
      <div class="flex items-center mb-4">
        <span class="ml-4">Reste à utiliser :</span>
        <span
          class="ml-4"
        >{{overtimes - usedOvertimes}} {{(overtimes - usedOvertimes) > 1 ? "heures" : "heure"}}</span>
      </div>
    </div>

    <div class="mb-base">
      <h6 class="mb-4">Indisponibilités</h6>
      <add-form :getOvertimes="getOvertimes()" />
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
                <vs-dropdown-item @click="this.confirmDeleteRecord" v-if="authorizedToDelete">
                  <span class="flex items-center">
                    <feather-icon icon="TrashIcon" svgClasses="h-4 w-4" class="mr-2" />
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
              {{
              currentPage * paginationPageSize -
              (paginationPageSize - 1)
              }}
              -
              {{
              unavailabilitiesData.length -
              currentPage * paginationPageSize >
              0
              ? currentPage * paginationPageSize
              : unavailabilitiesData.length
              }}
              sur {{ unavailabilitiesData.length }}
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
        class="ag-theme-material w-full my-4 ag-grid-table"
        overlayLoadingTemplate="Chargement..."
        :columnDefs="columnDefs"
        :defaultColDef="defaultColDef"
        :rowData="unavailabilitiesData"
        rowSelection="multiple"
        colResizeDefault="shift"
        :animateRows="true"
        :floatingFilter="false"
        :pagination="true"
        :paginationPageSize="paginationPageSize"
        :suppressPaginationPanel="true"
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
import EditForm from "./EditForm.vue";

// Store Module
import moduleUnavailabilityManagement from "@/store/unavailability-management/moduleUnavailabilityManagement.js";
import moduleDealingHoursManagement from "@/store/dealing-hours-management/moduleDealingHoursManagement.js";

// Cell Renderer
import CellRendererActions from "@/components/cell-renderer/CellRendererActions.vue";
import moment from "moment";

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
    CellRendererActions
  },
  data() {
    return {
      overtimes: 0,
      usedOvertimes: 0,

      searchQuery: "",

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
              `l'indisponibilité du ${moment(data.starts_at).format(
                "DD/MM/YYYY [à] HH:mm"
              )} au ${moment(data.ends_at).format("DD/MM/YYYY [à] HH:mm")}`,
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
  computed: {
    unavailabilitiesData() {
      return this.$store.state.unavailabilityManagement.unavailabilities;
    },
    authorizedToDelete() {
      return this.$store.getters.userHasPermissionTo(`delete ${modelPlurial}`);
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
    },
    parseDateTime(dateTime) {
      let date = dateTime.split(" ")[0];
      let hour = dateTime.split(" ")[1];
      hour = hour.split(":")[0] + ":" + hour.split(":")[1];
      return date + " à " + hour;
    },
    getOvertimes() {
      this.$vs.loading();
      this.$store
        .dispatch("dealingHoursManagement/getOvertimes")
        .then(data => {
          if (data && data.status === 200) {
            this.overtimes = data.data.success.overtimes;
            this.usedOvertimes = data.data.success.usedOvertimes;
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
        .finally(() => this.$vs.loading.close());
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
          .dispatch("unavailabilityManagement/removeItem", selectRow.id)
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
    this.getOvertimes();

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
    this.$store.dispatch("unavailabilityManagement/fetchItems").catch(err => {
      console.error(err);
    });
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
