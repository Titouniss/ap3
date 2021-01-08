<template>
  <div id="page-role-list">
    <div class="vx-card p-6">
      <div class="px-4 pt-3 mb-6" v-if="authorizedTo('publish')">
        <vs-button @click="addRecord" class="w-full">Ajouter un rôle</vs-button>
      </div>
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
                rolesData.length - currentPage * paginationPageSize > 0
                  ? currentPage * paginationPageSize
                  : rolesData.length
              }}
              sur {{ rolesData.length }}
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
        :rowData="rolesData"
        rowSelection="multiple"
        colResizeDefault="shift"
        :animateRows="true"
        :floatingFilter="false"
        :pagination="true"
        :paginationPageSize="paginationPageSize"
        :suppressPaginationPanel="true"
        :enableRtl="$vs.rtl"
        :isRowSelectable="(row) => !isRowDisabled(row.data)"
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
import moduleRoleManagement from "@/store/role-management/moduleRoleManagement.js";
import moduleUserManagement from "@/store/user-management/moduleUserManagement.js";

// Cell Renderer
import CellRendererActions from "@/components/cell-renderer/CellRendererActions.vue";

var model = "role";
var modelPlurial = "roles";
var modelTitle = "Rôle";

export default {
  components: {
    AgGridVue,
    vSelect,
    // Cell Renderer
    CellRendererActions,
  },
  data() {
    return {
      searchQuery: "",
      // AgGrid
      gridApi: null,
      gridOptions: {
        localeText: { noRowsToShow: "Aucun rôle à afficher" },
      },
      defaultColDef: {
        sortable: true,
        resizable: true,
        suppressMenu: true,
      },
      columnDefs: [
        {
          width: 40,
          filter: false,
          sortable: false,
          suppressSizeToFit: true,
          checkboxSelection: true,
          headerCheckboxSelectionFilteredOnly: false,
          headerCheckboxSelection: true,
        },
        {
          headerName: "Titre",
          field: "name",
        },
        {
          headerName: "Description",
          field: "description",
        },
        {
          sortable: false,
          headerName: "Actions",
          field: "transactions",
          type: "numericColumn",
          cellRendererFramework: "CellRendererActions",
          cellRendererParams: {
            model: "role",
            modelPlurial: "roles",
            name: (data) => `le rôle ${data.name}`,
            usesSoftDelete: false,
            disabled: this.isRowDisabled,
            blockDelete: (data) => this.blockRowDelete(data),
            blockDeleteMessage: (data) =>
              `Impossible de supprimer le rôle ${data.name}, il est utilisé par un ou plusieurs utilisateurs`,
          },
        },
      ],

      // Cell Renderer Components
      components: {
        CellRendererActions,
      },
    };
  },
  computed: {
    isAdmin() {
      return this.$store.state.AppActiveUser.is_admin;
    },
    itemIdToEdit() {
      return this.$store.state.roleManagement.role.id || 0;
    },
    rolesData() {
      return this.$store.state.roleManagement.roles;
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
    blockRowDelete(data) {
      return this.$store.state.userManagement.users.find((user) =>
        user.roles.find((r) => r.id === data.id)
      );
    },
    isRowDisabled(data) {
      const user = this.$store.state.AppActiveUser;
      if (user.roles && user.roles.length > 0) {
        if (this.isAdmin) {
          return (
            ["superAdmin", "Administrateur", "Utilisateur"].includes(
              data.name
            ) || data.is_public
          );
        } else {
          return data.company_id === null && data.is_public;
        }
      } else return true;
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
        value: "all",
      };

      this.$refs.filterCard.removeRefreshAnimation();
    },
    updateSearchQuery(val) {
      this.gridApi.setQuickFilter(val);
    },
    confirmDeleteRecord() {
      let selectedRow = this.gridApi.getSelectedRows();
      let singleRole = selectedRow[0];

      this.$vs.dialog({
        type: "confirm",
        color: "danger",
        title: "Confirmer suppression",
        text:
          this.gridApi.getSelectedRows().length > 1
            ? `Voulez vous vraiment supprimer ces rôles ?`
            : `Voulez vous vraiment supprimer le rôle ${singleRole.name} ?`,
        accept: this.deleteRecord,
        acceptText: "Supprimer",
        cancelText: "Annuler",
      });
    },
    deleteRecord() {
      this.gridApi.getSelectedRows().map((selectRow) => {
        this.$store
          .dispatch("roleManagement/removeItem", selectRow.id)
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
            ? `Utilisateurs supprimés?`
            : `Utilisateur supprimé`,
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
    if (!moduleRoleManagement.isRegistered) {
      this.$store.registerModule("roleManagement", moduleRoleManagement);
      moduleRoleManagement.isRegistered = true;
    }
    if (!moduleUserManagement.isRegistered) {
      this.$store.registerModule("userManagement", moduleUserManagement);
      moduleUserManagement.isRegistered = true;
    }
    this.$store.dispatch("roleManagement/fetchItems").catch((err) => {
      console.error(err);
    });
    this.$store.dispatch("userManagement/fetchItems").catch((err) => {
      console.error(err);
    });
  },
  beforeDestroy() {
    window.removeEventListener("resize", this.onResize());
    moduleRoleManagement.isRegistered = false;
    moduleUserManagement.isRegistered = false;
    this.$store.unregisterModule("roleManagement");
    this.$store.unregisterModule("userManagement");
  },
};
</script>
