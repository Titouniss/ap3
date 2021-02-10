<template>
  <div id="page-hours-list">
    <div class="vx-card p-6">
      <div class="d-theme-dark-light-bg flex flex-row justify-start pb-3">
        <feather-icon icon="FilterIcon" svgClasses="h-6 w-6" />
        <h4 class="ml-3">Filtres</h4>
      </div>
      <div class="flex flex-wrap justify-center items-end">
        <div style="min-width: 15em">
          <v-select
            label="name"
            v-model="filters.project"
            :options="projects"
            @input="refreshData()"
            @search:blur="refreshData()"
            @search:focus="clearRefreshDataTimeout"
            class="w-full"
          >
            <template #header>
              <div style="opacity: 0.8">Projet</div>
            </template>
          </v-select>
        </div>
        <vs-dropdown vs-trigger-click class="cursor-pointer mx-4">
          <div
            class="p-3 rounded-lg border border-solid d-theme-border-grey-light cursor-pointer flex items-center justify-between text-lg font-medium w-32"
          >
            <span class="mx-2 leading-none">
              {{ period_types[filters.period_type].name }}
            </span>
            <feather-icon icon="ChevronDownIcon" svgClasses="h-4 w-4" />
          </div>

          <vs-dropdown-menu>
            <vs-dropdown-item
              v-for="period_type in period_type_names"
              :key="period_type"
              @click="setPeriodType(period_type)"
            >
              <span class="flex items-center">
                {{ period_types[period_type].name }}
              </span>
            </vs-dropdown-item>
          </vs-dropdown-menu>
        </vs-dropdown>
        <div style="min-width: 15em">
          <v-select
            v-if="authorizedTo('show', 'users')"
            label="lastname"
            :options="users"
            v-model="filters.user"
            @input="refreshData()"
            @search:blur="refreshData()"
            @search:focus="clearRefreshDataTimeout"
            class="w-full"
          >
            <template #header>
              <div style="opacity: 0.8">Utilisateur</div>
            </template>
            <template #option="user">
              <span>
                {{ `${user.lastname} ${user.firstname}` }}
              </span>
            </template>
          </v-select>
        </div>
      </div>
      <div class="flex flex-wrap items-center">
        <vs-row
          v-if="!isFullFilter()"
          vs-justify="center"
          vs-align="center"
          vs-type="flex"
          vs-w="12"
          class="mt-6"
        >
          <vs-col
            vs-w="12"
            vs-type="flex"
            vs-justify="center"
            vs-align="center"
          >
            <vs-button
              v-if="isPeriodFilter()"
              radius
              color="primary"
              type="border"
              icon-pack="feather"
              icon="icon-chevron-left"
              @click="removeFromFilterDate"
            ></vs-button>
            <div class="m-3 flex" style="width: 300px">
              <vs-row vs-type="flex" vs-justify="center">
                <vs-col vs-type="flex" vs-justify="center">
                  <h5 v-if="isPeriodFilter()">
                    {{ filterDate }}
                  </h5>
                </vs-col>
                <vs-col vs-type="flex" vs-justify="center">
                  <h5 class="mt-1" v-if="this.filters.period_type === 'week'">
                    {{ currentWeek }}
                  </h5>
                </vs-col>
                <vs-col vs-type="flex" vs-justify="center">
                  <flat-pickr
                    v-if="!isPeriodFilter()"
                    :config="configDatePicker()"
                    placeholder="Date"
                    v-model="filters.date"
                    @on-change="onFilterDateChange"
                    @on-open="clearRefreshDataTimeout"
                  />
                </vs-col>
              </vs-row>
            </div>
            <vs-button
              v-if="isPeriodFilter()"
              radius
              color="primary"
              type="border"
              icon-pack="feather"
              icon="icon-chevron-right"
              @click="addToFilterDate"
            ></vs-button>
          </vs-col>
        </vs-row>
      </div>
    </div>
    <div class="vx-card p-6 mt-1">
      <div class="d-theme-dark-light-bg flex flex-row justify-start pb-3">
        <feather-icon icon="BarChart2Icon" svgClasses="h-6 w-6" />
        <h4 class="ml-3">Résumé</h4>
      </div>
      <vs-row
        v-if="showSummary"
        vs-justify="center"
        vs-align="center"
        vs-type="flex"
        vs-w="12"
      >
        <vs-col vs-w="6" vs-type="flex" vs-justify="center" vs-align="center">
          Heures travaillées sur la période :
          {{ " " + getStats("total") }}
        </vs-col>
        <!-- v-if="stats.overtime" -->
        <vs-col vs-w="6" vs-type="flex" vs-justify="center" vs-align="center">
          {{ lostTimeOrOvertime() + " " + getStats("overtime") }}
        </vs-col>
      </vs-row>
      <vs-row
        v-else
        vs-justify="center"
        vs-align="center"
        vs-type="flex"
        vs-w="12"
      >
        Veuillez renseigner des heures sur cette période afin d'avoir le résumé
      </vs-row>
      <vs-row
        vs-justify="center"
        vs-align="center"
        vs-type="flex"
        vs-w="12"
        class="mt-6"
      >
        <vs-button
          v-if="
            (!isManager && !isAdmin) ||
            (filters.user &&
              filters.user.id === this.$store.state.AppActiveUser.id)
          "
          @click="goToUnavailabilities()"
        >
          {{ "Gérer mes indisponibilités" }}
        </vs-button>
      </vs-row>
    </div>
    <div class="vx-card p-6 mt-1">
      <div
        class="d-theme-dark-light-bg flex flex-row justify-between items-center pb-3"
      >
        <div class="flex flex-row justify-start items-center">
          <feather-icon icon="ClockIcon" svgClasses="h-6 w-6" />
          <h4 class="ml-3">Heures effectuées</h4>
          <!-- <div class="px-6 py-2" v-if="authorizedTo('publish')">
            <vs-button @click="addRecord">Ajouter des heures</vs-button>
          </div>-->
          <div class="px-6 py-2" v-if="authorizedTo('publish')">
            <vs-button @click="readRecord">
              {{ isAdmin ? "Gérer les heures" : "Gérer mes heures" }}
            </vs-button>
          </div>
        </div>
        <vs-button type="border" @click="onExport">
          <div class="flex flex-row">
            <feather-icon
              icon="DownloadIcon"
              svgClasses="h-5 w-5"
              class="mr-2"
            />
            Exporter
          </div>
        </vs-button>
      </div>
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
                <feather-icon icon="ChevronDownIcon" svgClasses="h-4 w-4" />
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
              {{ currentPage * paginationPageSize - (paginationPageSize - 1) }}
              -
              {{
                hoursData.length - currentPage * paginationPageSize > 0
                  ? currentPage * paginationPageSize
                  : hoursData.length
              }}
              sur {{ hoursData.length }}
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
import moduleHoursManagement from "@/store/hours-management/moduleHoursManagement.js";
import moduleProjectManagement from "@/store/project-management/moduleProjectManagement.js";
import moduleUserManagement from "@/store/user-management/moduleUserManagement.js";

// FlatPickr import
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import { French as FrenchLocale } from "flatpickr/dist/l10n/fr.js";

// Cell Renderer
import CellRendererActions from "@/components/cell-renderer/CellRendererActions.vue";
import CellRendererRelations from "./cell-renderer/CellRendererRelations.vue";

import moment from "moment";

var model = "hours";
var modelPlurial = "hours";
var modelTitle = "Heures";

moment.locale("fr");

export default {
  components: {
    AgGridVue,
    vSelect,
    flatPickr,
    // Cell Renderer
    CellRendererActions,
    CellRendererRelations,
  },
  data() {
    return {
      searchQuery: "",
      // AgGrid
      gridApi: null,
      gridOptions: {
        localeText: { noRowsToShow: "Aucune heure à afficher" },
      },
      defaultColDef: {
        sortable: true,
        resizable: true,
        suppressMenu: true,
      },
      columnDefs: [
        {
          filter: false,
          width: 40,
          checkboxSelection: true,
          headerCheckboxSelectionFilteredOnly: false,
          headerCheckboxSelection: true,
        },
        {
          headerName: "Date",
          field: "date",
          cellRenderer: (data) => {
            moment.locale("fr");
            return moment(data.data.start_at).format("D MMMM YYYY");
          },
        },
        {
          headerName: "Durée",
          field: "duration",
        },
        {
          headerName: "Opérateur",
          field: "user",
          cellRenderer: (data) => {
            return data.data.user.firstname + " " + data.data.user.lastname;
          },
        },
        {
          headerName: "Description",
          field: "description",
        },
        {
          headerName: "Projet",
          field: "project",
          cellRendererFramework: "CellRendererRelations",
        },
        {
          sortable: false,
          headerName: "Actions",
          field: "transactions",
          type: "numericColumn",
          cellRendererFramework: "CellRendererActions",
          cellRendererParams: {
            model: "hours",
            modelPlurial: "hours",
            usesSoftDelete: false,
            canEdit: () => false,
            name: (data) =>
              data.duration == "01:00:00"
                ? `l'heure du ${data.start_at.split(" ")[0]} pour le projet ${
                    data.project
                  }`
                : `les ${data.duration.split(":")[0]} heures du ${
                    data.start_at.split(" ")[0]
                  } pour le projet ${data.project.name}`,
          },
        },
      ],

      // Cell Renderer Components
      components: {
        CellRendererActions,
        CellRendererRelations,
      },

      // Excel
      headerTitle: [
        "Id",
        "Utilisateur",
        "Projet",
        "Date",
        "Durée",
        "Description",
      ],
      headerVal: ["id", "user", "project", "date", "duration", "description"],

      // Filters
      filters: {
        project: null,
        user: null,
        date: moment(),
        period_type: "month",
      },
      period_type_names: ["date", "day", "week", "month", "year", "full"],
      period_types: {
        date: {
          name: "Date",
          symbol: "d",
          format: "D MMMM YYYY",
        },
        day: {
          name: "Jour",
          symbol: "d",
          format: "D MMMM YYYY",
        },
        week: {
          name: "Semaine",
          symbol: "w",
          format: "[Semaine] w, YYYY",
        },
        month: {
          name: "Mois",
          symbol: "M",
          format: "MMMM YYYY",
        },
        year: {
          name: "Année",
          symbol: "y",
          format: "YYYY",
        },
        full: {
          name: "Total",
          symbol: null,
          format: null,
        },
      },
      currentWeek: "",
      configDatePicker: () => ({
        disableMobile: "true",
        enableTime: false,
        locale: FrenchLocale,
        altFormat: "j F Y",
        altInput: true,
      }),
      refreshDataTimeout: null,

      // Stats
      stats: { total: 0 },
    };
  },
  computed: {
    isAdmin() {
      return this.$store.state.AppActiveUser.is_admin;
    },
    isManager() {
      return this.$store.state.AppActiveUser.is_manager;
    },
    filterDate() {
      moment.locale("fr");
      if (this.filters.period_type === "week") {
        let startWeek = moment(this.filters.date)
          .startOf("isoWeek")
          .format("dddd D MMM");

        let endWeek = moment(this.filters.date)
          .endOf("isoWeek")
          .format("dddd D MMM");

        this.currentWeek = startWeek + " - " + endWeek;
      }

      return moment(this.filters.date).format(
        this.period_types[this.filters.period_type].format
      );
    },
    itemIdToEdit() {
      return this.$store.getters["hoursManagement/getSelectedItem"].id || 0;
    },
    projects() {
      return this.$store.getters["projectManagement/getItems"].sort(function (
        a,
        b
      ) {
        var textA = a.name.toUpperCase();
        var textB = b.name.toUpperCase();
        return textA < textB ? -1 : textA > textB ? 1 : 0;
      });
    },
    users() {
      return this.$store.getters["userManagement/getItems"];
    },
    hoursData() {
      return this.$store.getters["hoursManagement/getItems"];
    },
    showSummary() {
      if (
        this.stats.length === undefined &&
        typeof this.stats.total === "object"
      ) {
        return false;
      } else {
        return true;
      }
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
      },
    },
  },
  methods: {
    authorizedTo(action, model = modelPlurial) {
      return this.$store.getters.userHasPermissionTo(`${action} ${model}`);
    },
    clearRefreshDataTimeout() {
      if (this.refreshDataTimeout) {
        clearTimeout(this.refreshDataTimeout);
      }
    },
    getStats(name) {
      if (name === "overtime") {
        if (this.stats["overtime"]) {
          return (this.stats[name] ? parseFloat(this.stats[name]) : 0).toFixed(
            2
          );
        } else {
          return (this.stats["lost_time"]
            ? parseFloat(this.stats["lost_time"])
            : 0
          ).toFixed(2);
        }
      } else {
        return (this.stats[name] ? parseFloat(this.stats[name]) : 0).toFixed(2);
      }
    },
    lostTimeOrOvertime() {
      if (this.stats["overtime"]) {
        return "Heures supplémentaires sur la période :";
      } else {
        return "Heures manquante sur la période :";
      }
    },
    addToFilterDate() {
      this.filters.date = moment(this.filters.date).add(
        1,
        this.period_types[this.filters.period_type].symbol
      );
      this.refreshData();
    },
    removeFromFilterDate() {
      this.filters.date = moment(this.filters.date).subtract(
        1,
        this.period_types[this.filters.period_type].symbol
      );
      this.refreshData();
    },
    isFullFilter() {
      return this.filters.period_type === "full";
    },
    isPeriodFilter() {
      return this.filters.period_type !== "date";
    },
    setPeriodType(type) {
      this.filters.period_type = type;
      this.filters.date = type === "date" || type === "full" ? null : moment();
      this.refreshData();
    },
    onFilterDateChange(selectedDates, dateStr, instance) {
      //this.filters.date = selectedDates[0];
      this.refreshData(selectedDates[0]);
    },
    refreshData(targetDate = this.filters.date) {
      const filter = {};
      if (this.filters.project) {
        filter.project_id = this.filters.project.id;
      }
      if (this.filters.user) {
        filter.user_id = this.filters.user.id;
      }
      if (targetDate) {
        filter.date = moment(targetDate).format("DD-MM-YYYY");
        if (this.isPeriodFilter()) {
          filter.period_type = this.filters.period_type;
        }
      }
      this.clearRefreshDataTimeout();
      this.refreshDataTimeout = setTimeout(() => {
        this.$vs.loading();
        // refresh Hours
        this.$store
          .dispatch("hoursManagement/fetchItems", filter)
          .then((data) => {
            this.stats = data.stats;
          })
          .catch((error) => {
            this.$vs.notify({
              title: "Erreur",
              text: error.message,
              iconPack: "feather",
              icon: "icon-alert-circle",
              color: "danger",
            });
          })
          .finally(() => this.$vs.loading.close());
      }, 1500);
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
      this.hoursFilter = this.statusFilter = this.isVerifiedFilter = this.departmentFilter = {
        label: "All",
        value: "all",
      };

      this.$refs.filterCard.removeRefreshAnimation();
    },
    updateSearchQuery(val) {
      this.gridApi.setQuickFilter(val);
    },
    confirmDeleteRecord() {
      let selectedRow = this.gridApi.getSelectedRows();
      let singleHour = selectedRow[0];

      this.$vs.dialog({
        type: "confirm",
        color: "danger",
        title: "Confirmer suppression",
        text:
          this.gridApi.getSelectedRows().length > 1
            ? `Voulez vous vraiment supprimer ces heures ?`
            : singleHour.duration == "01:00:00"
            ? `Voulez vous vraiment supprimer l'heure du ${
                singleHour.start_at.split(" ")[0]
              } pour le projet ${singleHour.project} ?`
            : `Voulez vous vraiment supprimer les ${
                singleHour.duration.split(":")[0]
              } heures du ${singleHour.start_at.split(" ")[0]} pour le projet ${
                singleHour.project.name
              } ?`,
        accept: this.deleteRecord,
        acceptText: "Supprimer",
        cancelText: "Annuler",
      });
    },
    deleteRecord() {
      this.gridApi.getSelectedRows().map((selectRow) => {
        this.$store
          .dispatch("hoursManagement/removeItems", [selectRow.id])
          .then((data) => {
            this.showDeleteSuccess();
          })
          .catch((err) => {
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
            ? `Heures supprimés`
            : `Heure supprimé`,
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
    readRecord() {
      this.$router.push(`/${modelPlurial}/${model}-view/`).catch(() => {});
    },
    goToUnavailabilities() {
      this.$router.push({
        path: `/users/user-profil-edit/` + this.$store.state.AppActiveUser.id,
        query: { tab: 1 },
      });
    },
    onExport() {
      import("@/vendor/Export2Excel").then((excel) => {
        const data = this.formatJson(this.headerVal, this.hoursData);
        excel.export_json_to_excel({
          header: this.headerTitle,
          data,
          filename: moment().format("YYYY-MM-DD HH:mm") + "_Heures_effectuées",
          autoWidth: true,
          bookType: "xlsx",
        });
      });
    },
    formatJson(filterVal, jsonData) {
      return jsonData.map((v) =>
        filterVal.map((j) => {
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
    },
  },
  mounted() {
    this.gridApi = this.gridOptions.api;

    // Hide user column ?
    this.gridOptions.columnApi.setColumnVisible(
      "user",
      this.isAdmin || this.isManager
    );

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
    if (!moduleHoursManagement.isRegistered) {
      this.$store.registerModule("hoursManagement", moduleHoursManagement);
      moduleHoursManagement.isRegistered = true;
    }
    if (!moduleProjectManagement.isRegistered) {
      this.$store.registerModule("projectManagement", moduleProjectManagement);
      moduleProjectManagement.isRegistered = true;
    }
    if (!moduleUserManagement.isRegistered) {
      this.$store.registerModule("userManagement", moduleUserManagement);
      moduleUserManagement.isRegistered = true;
    }

    this.$store
      .dispatch("hoursManagement/fetchItems", {
        date: moment().format("DD-MM-YYYY"),
        period_type: "month",
      })
      .then((data) => (this.stats = data.stats));
    this.$store.dispatch("projectManagement/fetchItems");
    if (this.authorizedTo("read", "users")) {
      this.$store.dispatch("userManagement/fetchItems");
    } else {
      this.filters.user = this.$store.state.AppActiveUser;
    }
  },
  beforeDestroy() {
    window.removeEventListener("resize", this.onResize());
    moduleProjectManagement.isRegistered = false;
    moduleUserManagement.isRegistered = false;
    moduleHoursManagement.isRegistered = false;
    this.$store.unregisterModule("hoursManagement");
    this.$store.unregisterModule("projectManagement");
    this.$store.unregisterModule("userManagement");
  },
};
</script>
