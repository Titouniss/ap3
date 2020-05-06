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
          <vs-dropdown vs-trigger-click class="cursor-pointer">
            <div class="p-4 border border-solid d-theme-border-grey-light rounded-full d-theme-dark-bg cursor-pointer flex items-center justify-between font-medium">
              <span class="mr-2">{{ currentPage * paginationPageSize - (paginationPageSize - 1) }} - {{ projectsData.length - currentPage * paginationPageSize > 0 ? currentPage * paginationPageSize : projectsData.length }} of {{ projectsData.length }}</span>
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
          <vs-input class="sm:mr-4 mr-0 sm:w-auto w-full sm:order-normal order-3 sm:mt-0 mt-4" v-model="searchQuery" @input="updateSearchQuery" placeholder="Search..." />
          <!-- <vs-button class="mb-4 md:mb-0" @click="gridApi.exportDataAsCsv()">Export as CSV</vs-button> -->

          <!-- ACTION - DROPDOWN -->
          <vs-dropdown vs-trigger-click class="cursor-pointer">

            <div class="p-3 shadow-drop rounded-lg d-theme-dark-light-bg cursor-pointer flex items-end justify-center text-lg font-medium w-32">
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

              <vs-dropdown-item>
                <span class="flex items-center">
                  <feather-icon icon="ArchiveIcon" svgClasses="h-4 w-4" class="mr-2" />
                  <span>Archive</span>
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
        :rowData="projectsData"
        rowSelection="multiple"
        colResizeDefault="shift"
        :animateRows="true"
        :floatingFilter="true"
        :pagination="true"
        :paginationPageSize="paginationPageSize"
        :suppressPaginationPanel="true"
        :enableRtl="$vs.rtl">
      </ag-grid-vue>

      <vs-pagination
        :total="totalPages"
        :max="7"
        v-model="currentPage" />

    </div>

    <edit-form :itemId="itemIdToEdit" v-if="itemIdToEdit"/>
  </div>

</template>

<script>
import { AgGridVue } from 'ag-grid-vue'
import '@sass/vuexy/extraComponents/agGridStyleOverride.scss'
import vSelect from 'vue-select'
import moment from 'moment'

//CRUD
import AddForm from './AddForm.vue'
import EditForm from './EditForm.vue'

// Store Module
import moduleProjectManagement from '@/store/project-management/moduleProjectManagement.js'
import moduleCompanyManagement from '@/store/company-management/moduleCompanyManagement.js'
import moduleRangeManagement from '@/store/range-management/moduleRangeManagement.js'

// Cell Renderer
import CellRendererLink from './cell-renderer/CellRendererLink.vue'
import CellRendererRelations from './cell-renderer/CellRendererRelations.vue'
import CellRendererActions from './cell-renderer/CellRendererActions.vue'


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
  data () {
    return {
      searchQuery: '',

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
          checkboxSelection: true,
          headerCheckboxSelectionFilteredOnly: true,
          headerCheckboxSelection: true,
        },
        {
          headerName: 'Name',
          field: 'name',
          filter: true,
          cellRendererFramework: 'CellRendererLink'
        },
        {
          headerName: 'Date de création',
          field: 'created_at',
          filter: true,
          cellRenderer: (data) => {
            moment.locale('fr')
            return moment(data.value).format('DD MMMM YYYY')
          }
        },
        {
          headerName: 'Avancement',
          field: 'status',
          filter: true,
        },
        {
          headerName: 'Compagnie',
          field: 'company',
          filter: true,
          cellRendererFramework: 'CellRendererRelations'
        },
        {
          headerName: 'Actions',
          field: 'transactions',
          cellRendererFramework: 'CellRendererActions'
        }
      ],

      // Cell Renderer Components
      components: {
        CellRendererLink,
        CellRendererActions,
        CellRendererRelations
      }
    }
  },
  computed: {
    projectsData() {
      return this.$store.state.projectManagement.projects
    },
    paginationPageSize () {
      if (this.gridApi) return this.gridApi.paginationGetPageSize()
      else return 10
    },
    totalPages () {
      if (this.gridApi) return this.gridApi.paginationGetTotalPages()
      else return 0
    },
    itemIdToEdit () {
      return this.$store.state.projectManagement.project.id || 0
    },
    currentPage: {
      get () {
        if (this.gridApi) return this.gridApi.paginationGetCurrentPage() + 1
        else return 1
      },
      set (val) {
        this.gridApi.paginationGoToPage(val - 1)
      }
    }
  },
  methods: {
    updateSearchQuery (val) {
      this.gridApi.setQuickFilter(val)
    },
  },
  mounted () {
    this.gridApi = this.gridOptions.api

    /* =================================================================
      NOTE:
      Header is not aligned properly in RTL version of agGrid table.
      However, we given fix to this issue. If you want more robust solution please contact them at gitHub
    ================================================================= */
    if (this.$vs.rtl) {
      const header = this.$refs.agGridTable.$el.querySelector('.ag-header-container')
      header.style.left = `-${  String(Number(header.style.transform.slice(11, -3)) + 9)  }px`
    }
  },
  created () {
    if (!moduleProjectManagement.isRegistered) {
      this.$store.registerModule('projectManagement', moduleProjectManagement)
      moduleProjectManagement.isRegistered = true
    }
    if (!moduleCompanyManagement.isRegistered) {
      this.$store.registerModule('companyManagement', moduleCompanyManagement)
      moduleCompanyManagement.isRegistered = true
    }
    if (!moduleRangeManagement.isRegistered) {
      this.$store.registerModule('rangeManagement', moduleRangeManagement)
      moduleRangeManagement.isRegistered = true
    }
    this.$store.dispatch('companyManagement/fetchItems').catch(err => { console.error(err) })
    this.$store.dispatch('rangeManagement/fetchItems').catch(err => { console.error(err) })
    this.$store.dispatch('projectManagement/fetchItems').catch(err => { console.error(err) })
  },
  beforeDestroy () {
    moduleProjectManagement.isRegistered = false
    moduleCompanyManagement.isRegistered = false
    moduleRangeManagement.isRegistered = false
    this.$store.unregisterModule('projectManagement')
    this.$store.unregisterModule('companyManagement')
    this.$store.unregisterModule('rangeManagement')
  },
}

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
