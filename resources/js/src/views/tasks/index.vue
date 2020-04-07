<!-- =========================================================================================
  File Name: TasksList.vue
  Description: Tasks List page
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
========================================================================================== -->

<template>

  <div id="page-tasks-list">

    <div class="vx-card p-6">
      <add-form :project_data="this.project_data"/>
      <div class="flex flex-wrap items-center">

        <!-- ITEMS PER PAGE -->
        <div class="flex-grow">
          <vs-dropdown vs-trigger-click class="cursor-pointer">
            <div class="p-4 border border-solid d-theme-border-grey-light rounded-full d-theme-dark-bg cursor-pointer flex items-center justify-between font-medium">
              <span class="mr-2">{{ currentPage * paginationPageSize - (paginationPageSize - 1) }} - {{ tasksData.length - currentPage * paginationPageSize > 0 ? currentPage * paginationPageSize : tasksData.length }} of {{ tasksData.length }}</span>
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
      </div>


      <!-- AgGrid Table -->
      <ag-grid-vue
        ref="agGridTable"
        :components="components"
        :gridOptions="gridOptions"
        class="ag-theme-material w-100 my-4 ag-grid-table"
        :columnDefs="columnDefs"
        :defaultColDef="defaultColDef"
        :rowData="tasksData"
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

    <!-- <edit-form :itemId="itemIdToEdit" v-if="itemIdToEdit"/> -->
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
import moduleTaskManagement from '@/store/task-management/moduleTaskManagement.js'

// Cell Renderer
import CellRendererLink from './cell-renderer/CellRendererLink.vue'
import CellRendererRelations from './cell-renderer/CellRendererRelations.vue'
import CellRendererActions from './cell-renderer/CellRendererActions.vue'
import CellRendererStatus from './cell-renderer/CellRendererStatus.vue'


export default {
  props: {
    project_data: {
      required: true
    }
  },
  components: {
    AgGridVue,
    vSelect,
    AddForm,
    EditForm,

    // Cell Renderer
    CellRendererLink,
    CellRendererActions,
    CellRendererRelations,
    CellRendererStatus
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
          width: 50,
        },
        {
          headerName: 'Name',
          field: 'name',
          filter: true,
        },
        {
          headerName: 'Plannifié le',
          field: 'date',
          filter: true,
          cellRenderer: (data) => {
            moment.locale('fr')
            return moment(data.value).format('DD MMMM YYYY')
          }
        },
        {
          headerName: 'Estimation',
          field: 'estimated_time',
          filter: true,
          width: 200,
          cellRenderer: (data) => {
            return data.value + 'h'
          }
        },
        {
          headerName: 'Ilôt',
          field: 'workarea',
          filter: true,
          cellRendererFramework: 'CellRendererRelations'
        },
        {
          headerName: 'Avancement',
          field: 'status',
          filter: true,
          cellRendererFramework: 'CellRendererStatus'
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
    tasksData() {
      return this.$store.state.taskManagement.tasks
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
      return this.$store.state.taskManagement.task.id || 0
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
    }
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
    if (!moduleTaskManagement.isRegistered) {
      this.$store.registerModule('taskManagement', moduleTaskManagement)
      moduleTaskManagement.isRegistered = true
    }
    this.$store.dispatch('taskManagement/fetchItemsByBundle', this.project_data.tasks_bundles[0].id).catch(err => { console.error(err) })
  },
  beforeDestroy () {
    moduleTaskManagement.isRegistered = false
    this.$store.unregisterModule('taskManagement')
  }
}

</script>

<style lang="scss">
#page-tasks-list {
  .tasks-list-filters {
    .vs__actions {
      position: absolute;
      right: 0;
      top: 50%;
      transform: translateY(-58%);
    }
  }
}
</style>
