<!-- =========================================================================================
  File Name: ProjectsList.vue
  Description: Projects List page
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
========================================================================================== -->

<template>
  <div id="page-projects-list">
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
                <vs-dropdown-item @click="confirmDeleteRecord('delete')">
                  <span class="flex items-center">
                    <feather-icon icon="TrashIcon" svgClasses="h-4 w-4" class="mr-2" />
                    <span>Supprimer</span>
                  </span>
                </vs-dropdown-item>

                <vs-dropdown-item @click="confirmDeleteRecord('archive')">
                  <span class="flex items-center">
                    <feather-icon icon="ArchiveIcon" svgClasses="h-4 w-4" class="mr-2" />
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
              projectsData.length -
              currentPage * paginationPageSize >
              0
              ? currentPage * paginationPageSize
              : projectsData.length
              }}
              sur {{ projectsData.length }}
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
        :rowData="projectsData"
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
import moment from "moment";

//CRUD
import AddForm from "./AddForm.vue";
import EditForm from "./EditForm.vue";

// Store Module
import moduleProjectManagement from "@/store/project-management/moduleProjectManagement.js";
import moduleCompanyManagement from "@/store/company-management/moduleCompanyManagement.js";
import moduleRangeManagement from "@/store/range-management/moduleRangeManagement.js";
import moduleCustomerManagement from "@/store/customer-management/moduleCustomerManagement.js";

// Cell Renderer
import CellRendererRelations from "./cell-renderer/CellRendererRelations.vue";
import CellRendererActions from "@/components/cell-renderer/CellRendererActions.vue";
import CellRendererLink from "./cell-renderer/CellRendererLink.vue";

var modelTitle = "Projet";

export default {
  components: {
    AgGridVue,
    vSelect,
    AddForm,
    EditForm,

    // Cell Renderer
    CellRendererActions,
    CellRendererLink,
    CellRendererRelations
  },
  data() {
    return {
      searchQuery: "",

      // AgGrid
      gridApi: null,
      gridOptions: {
        localeText: { noRowsToShow: "Aucun projet à afficher" }
      },
      defaultColDef: {
        sortable: true,
        resizable: true,
        suppressMenu: true
      },
      columnDefs: [
        {
          width: 40,
          checkboxSelection: true,
          headerCheckboxSelectionFilteredOnly: true,
          headerCheckboxSelection: true
        },
        {
          headerName: "Nom",
          field: "name",
          filter: true,
          cellRendererFramework: "CellRendererLink"
        },
        {
          headerName: "Date de création",
          field: "created_at",
          filter: true,
          cellRenderer: data => {
            moment.locale("fr");
            return moment(data.value).format("DD MMMM YYYY");
          }
        },
        {
          headerName: "Avancement",
          field: "status",
          filter: true
        },
        {
          headerName: "Société",
          field: "company",
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
            model: "project",
            modelPlurial: "projects",
            name: data => `le projet ${data.name}`,
            withPrompt: true,
            linkedTables: ["tâches"]
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
    projectsData() {
      return this.sortProjects(this.$store.state.projectManagement.projects);
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
      return this.$store.state.projectManagement.project.id || 0;
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
    updateSearchQuery(val) {
      this.gridApi.setQuickFilter(val);
    },
    confirmDeleteRecord(type) {
      let selectedRow = this.gridApi.getSelectedRows();
      let singleProject = selectedRow[0];

      this.$vs.dialog({
        type: "confirm",
        color: "danger",
        title:
          type === "delete" ? "Confirmer suppression" : "Confirmer archivage",
        text:
          type === "delete" && this.gridApi.getSelectedRows().length > 1
            ? `Voulez vous vraiment supprimer ces projets ?`
            : type === "delete" && this.gridApi.getSelectedRows().length === 1
            ? `Voulez vous vraiment supprimer le projet ${singleProject.name} ?`
            : this.gridApi.getSelectedRows().length > 1
            ? `Voulez vous vraiment archiver ces projets ?`
            : `Voulez vous vraiment archiver le projet ${singleProject.name} ?`,
        accept: type === "delete" ? this.deleteRecord : this.archiveRecord,
        acceptText: type === "delete" ? "Supprimer" : "Archiver",
        cancelText: "Annuler"
      });
    },
    deleteRecord() {
      const selectedRowLength = this.gridApi.getSelectedRows().length;

      this.gridApi.getSelectedRows().map(selectRow => {
        this.$store
          .dispatch("projectManagement/forceRemoveItem", selectRow.id)
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
          .dispatch("projectManagement/removeItem", selectRow.id)
          .then(data => {
            if (selectedRowLength === 1) {
              this.showDeleteSuccess("archive", selectedRowLength);
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
            ? `Projets supprimés`
            : type === "delete" && selectedRowLength === 1
            ? `Projet supprimé`
            : selectedRowLength > 1
            ? `Projets archivés`
            : `Projet archivé`
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
    sortProjects(projects) {
      let todoProjects = projects
        .filter(project => project.status == "todo")
        .reverse();
      let doingProjects = projects
        .filter(project => project.status == "doing")
        .reverse();
      let doneProjects = projects
        .filter(project => project.status == "done")
        .reverse();

      let response = [];
      response = response.concat(todoProjects);
      response = response.concat(doingProjects);
      response = response.concat(doneProjects);

      return response;
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
    if (!moduleProjectManagement.isRegistered) {
      this.$store.registerModule("projectManagement", moduleProjectManagement);
      moduleProjectManagement.isRegistered = true;
    }
    if (!moduleCompanyManagement.isRegistered) {
      this.$store.registerModule("companyManagement", moduleCompanyManagement);
      moduleCompanyManagement.isRegistered = true;
    }
    if (!moduleRangeManagement.isRegistered) {
      this.$store.registerModule("rangeManagement", moduleRangeManagement);
      moduleRangeManagement.isRegistered = true;
    }
    if (!moduleCustomerManagement.isRegistered) {
      this.$store.registerModule(
        "customerManagement",
        moduleCustomerManagement
      );
      moduleCustomerManagement.isRegistered = true;
    }
    this.$store.dispatch("projectManagement/fetchItems").catch(err => {
      console.error(err);
    });
    this.$store.dispatch("customerManagement/fetchItems").catch(err => {
      console.error(err);
    });
    if (this.$store.getters.userHasPermissionTo(`read companies`)) {
      this.$store.dispatch("companyManagement/fetchItems").catch(err => {
        console.error(err);
      });
    }
    if (this.$store.getters.userHasPermissionTo(`read ranges`)) {
      this.$store.dispatch("rangeManagement/fetchItems").catch(err => {
        console.error(err);
      });
    }
  },
  beforeDestroy() {
    window.removeEventListener("resize", this.onResize());

    moduleProjectManagement.isRegistered = false;
    moduleCompanyManagement.isRegistered = false;
    moduleRangeManagement.isRegistered = false;
    moduleCustomerManagement.isRegistered = false;
    this.$store.unregisterModule("projectManagement");
    this.$store.unregisterModule("companyManagement");
    this.$store.unregisterModule("rangeManagement");
    this.$store.unregisterModule("customerManagement");
  }
};
</script>

<style lang="scss">
#page-projects-list {
  .projects-list-filters {
    .vs__actions {
      position: absolute;
      right: 0;
      top: 50%;
      transform: translateY(-58%);
    }
  }
}
</style>
