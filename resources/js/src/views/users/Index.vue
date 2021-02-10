<template>
  <div id="page-users-list">
    <div class="vx-card w-full p-6">
      <vs-row
        vs-type="flex"
        vs-justify="space-between"
        vs-align="center"
        vs-w="12"
        class="mb-6"
      >
        <vs-col
          vs-type="flex"
          vs-justify="flex-start"
          vs-align="center"
          vs-w="2"
          vs-sm="6"
        >
          <vs-button v-if="authorizedTo('publish')" @click="addRecord">
            Ajouter un utilisateur
          </vs-button>
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
          <vs-row vs-type="flex">
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

        <!-- <vs-button class="mb-4 md:mb-0" @click="gridApi.exportDataAsCsv()">Export as CSV</vs-button> -->

        <!-- ITEMS PER PAGE -->
        <vs-dropdown vs-trigger-click class="cursor-pointer">
          <div
            class="p-4 border border-solid d-theme-border-grey-light rounded-full d-theme-dark-bg cursor-pointer flex items-center justify-between font-medium"
          >
            <span class="mr-2">
              {{ currentPage * paginationPageSize - (paginationPageSize - 1) }}
              -
              {{
                usersData.length - currentPage * paginationPageSize > 0
                  ? currentPage * paginationPageSize
                  : usersData.length
              }}
              sur {{ usersData.length }}
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
        :rowData="usersData"
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
    <!-- <edit-form :reload="usersData" :itemId="itemIdToEdit" v-if="itemIdToEdit && authorizedTo('edit') " /> -->
  </div>
</template>

<script>
import { AgGridVue } from "ag-grid-vue";
import "@sass/vuexy/extraComponents/agGridStyleOverride.scss";
import vSelect from "vue-select";
import router from "@/router";

// Store Module
import moduleUserManagement from "@/store/user-management/moduleUserManagement.js";
import moduleRoleManagement from "@/store/role-management/moduleRoleManagement.js";
import moduleCompanyManagement from "@/store/company-management/moduleCompanyManagement.js";
import moduleSkillManagement from "@/store/skill-management/moduleSkillManagement.js";

// Cell Renderer
import CellRendererLink from "./cell-renderer/CellRendererLink.vue";
import CellRendererRelations from "./cell-renderer/CellRendererRelations.vue";
import CellRendererActions from "@/components/cell-renderer/CellRendererActions.vue";

// Components
import RefreshModule from "@/components/inputs/buttons/RefreshModule.vue";

var model = "user";
var modelPlurial = "users";
var modelTitle = "Utilisateurs";

var columnDef = [
  {
    width: 40,
    filter: false,
    checkboxSelection: true,
    headerCheckboxSelectionFilteredOnly: true,
    headerCheckboxSelection: true,
    resizable: true,
  },
  {
    headerName: "Nom",
    field: "lastname",
    filter: true,
    resizable: true,
  },
  {
    headerName: "Prénom",
    field: "firstname",
    filter: true,
    resizable: true,
  },
  {
    headerName: "Email",
    field: "email",
    filter: true,
    resizable: true,
  },
  {
    headerName: "Rôle",
    field: "role",
    filter: true,
    cellRendererFramework: "CellRendererRelations",
    resizable: true,
  },
  {
    headerName: "Société",
    field: "company",
    filter: true,
    cellRendererFramework: "CellRendererRelations",
    resizable: true,
  },
  {
    headerName: "Actions",
    field: "transactions",
    type: "numericColumn",
    cellRendererFramework: "CellRendererActions",
    cellRendererParams: {
      model: "user",
      modelPlurial: "users",
      name: (data) => `l'utilisateur ${data.firstname} ${data.lastname}`,
      disabled: (data) => data.is_admin,
      footNotes: {
        archive:
          "Si vous archivez l'utilisateur les tâches associées ne lui seront plus attribué.",
      },
    },
  },
];

export default {
  components: {
    AgGridVue,
    vSelect,
    // Cell Renderer
    CellRendererLink,
    CellRendererRelations,
    CellRendererActions,

    // Components
    RefreshModule,
  },
  data() {
    return {
      searchQuery: "",

      // AgGrid
      gridApi: null,
      gridOptions: {
        localeText: { noRowsToShow: "Aucun utilisateur à afficher" },
      },
      defaultColDef: {
        sortable: true,
        resizable: true,
        suppressMenu: true,
      },
      columnDefs: this.getColumnDef(),

      // Cell Renderer Components
      components: {
        CellRendererLink,
        CellRendererRelations,
        CellRendererActions,
      },
    };
  },
  watch: {
    roleFilter(obj) {
      this.setColumnFilter("role", obj.value);
    },
    statusFilter(obj) {
      this.setColumnFilter("status", obj.value);
    },
    isVerifiedFilter(obj) {
      const val =
        obj.value === "all" ? "all" : obj.value === "yes" ? "true" : "false";
      this.setColumnFilter("is_verified", val);
    },
    departmentFilter(obj) {
      this.setColumnFilter("department", obj.value);
    },
  },
  computed: {
    isAdmin() {
      return this.$store.state.AppActiveUser.is_admin;
    },
    itemIdToEdit() {
      return this.$store.state.userManagement.user.id || 0;
    },
    usersData() {
      return this.$store.state.userManagement.users;
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
    getColumnDef() {
      if (this.isAdmin) {
        return columnDef;
      } else {
        let withoutCompany = columnDef.slice(0, 5).concat(columnDef.slice(-1));
        return withoutCompany;
      }
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
      let singleUser = selectedRow[0];

      this.$vs.dialog({
        type: "confirm",
        color: "danger",
        title: "Confirmer suppression",
        text:
          this.gridApi.getSelectedRows().length > 1
            ? `Voulez vous vraiment supprimer ces utilisateurs ?`
            : `Voulez vous vraiment supprimer l'utilisateur
              ${singleUser.firstname} ${singleUser.lastname} ?`,
        accept: this.deleteRecord,
        acceptText: "Supprimer",
        cancelText: "Annuler",
      });
    },
    deleteRecord() {
      this.gridApi.getSelectedRows().map((selectRow) => {
        this.$store
          .dispatch("userManagement/forceRemoveItem", selectRow.id)
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
        this.gridApi.redrawRows();

        // resize columns in the grid to fit the available space
        this.gridApi.sizeColumnsToFit();
      }
    },
    addRecord() {
      this.$router.push(`/${modelPlurial}/${model}-add/`).catch(() => {});
    },
  },
  mounted() {
    const user = this.$store.state.AppActiveUser;
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
    if (!moduleUserManagement.isRegistered) {
      this.$store.registerModule("userManagement", moduleUserManagement);
      moduleUserManagement.isRegistered = true;
    }
    if (!moduleRoleManagement.isRegistered) {
      this.$store.registerModule("roleManagement", moduleRoleManagement);
      moduleRoleManagement.isRegistered = true;
    }
    if (!moduleCompanyManagement.isRegistered) {
      this.$store.registerModule("companyManagement", moduleCompanyManagement);
      moduleCompanyManagement.isRegistered = true;
    }
    if (!moduleSkillManagement.isRegistered) {
      this.$store.registerModule("skillManagement", moduleSkillManagement);
      moduleSkillManagement.isRegistered = true;
    }

    this.$store.dispatch("skillManagement/fetchItems");
    if (this.authorizedTo("read", "skills")) {
      this.$store.dispatch("skillManagement/fetchItems");
    }
    if (this.authorizedTo("read", "companies")) {
      this.$store.dispatch("companyManagement/fetchItems");
    }
    if (this.authorizedTo("read", "roles")) {
      this.$store.dispatch("roleManagement/fetchItems");
    }
    this.$store.dispatch("userManagement/fetchItems", { with_trashed: true });
  },
  beforeDestroy() {
    window.removeEventListener("resize", this.onResize());

    moduleUserManagement.isRegistered = false;
    moduleRoleManagement.isRegistered = false;
    moduleCompanyManagement.isRegistered = false;
    moduleSkillManagement.isRegistered = false;
    this.$store.unregisterModule("skillManagement");
    this.$store.unregisterModule("userManagement");
    this.$store.unregisterModule("roleManagement");
    this.$store.unregisterModule("companyManagement");
  },
};
</script>

<style lang="scss">
#page-user-list {
  .user-list-filters {
    .vs__actions {
      position: absolute;
      right: 0;
      top: 50%;
      transform: translateY(-58%);
    }
  }
}
</style>
