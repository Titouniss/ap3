<!-- =========================================================================================
  File Name: CompaniesList.vue
  Description: Companies List page
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
========================================================================================== -->

<template>
    <div id="page-companies-list">
        <div class="vx-card p-6">
            <vs-button
                class="mt-1 mb-6"
                v-if="authorizedTo('publish')"
                @click="addRecord"
            >
                Ajouter une société
            </vs-button>
            <div class="flex flex-wrap items-center">
                <div class="flex-grow">
                    <vs-row>
                        <!-- <vs-button class="mb-4 md:mb-0" @click="gridApi.exportDataAsCsv()">Export as CSV</vs-button> -->

                        <multiple-actions
                            model="company"
                            model-plurial="companies"
                            :items="selectedItems"
                            @on-action="onAction"
                        />

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
                            {{
                                currentPage * paginationPageSize -
                                    (paginationPageSize - 1)
                            }}
                            -
                            {{
                                companiesData.length -
                                    currentPage * paginationPageSize >
                                0
                                    ? currentPage * paginationPageSize
                                    : companiesData.length
                            }}
                            sur {{ companiesData.length }}
                        </span>
                        <feather-icon
                            icon="ChevronDownIcon"
                            svgClasses="h-4 w-4"
                        />
                    </div>
                    <!-- <vs-button class="btn-drop" type="line" color="primary" icon-pack="feather" icon="icon-chevron-down"></vs-button> -->
                    <vs-dropdown-menu>
                        <vs-dropdown-item
                            @click="gridApi.paginationSetPageSize(10)"
                        >
                            <span>10</span>
                        </vs-dropdown-item>
                        <vs-dropdown-item
                            @click="gridApi.paginationSetPageSize(20)"
                        >
                            <span>20</span>
                        </vs-dropdown-item>
                        <vs-dropdown-item
                            @click="gridApi.paginationSetPageSize(25)"
                        >
                            <span>25</span>
                        </vs-dropdown-item>
                        <vs-dropdown-item
                            @click="gridApi.paginationSetPageSize(30)"
                        >
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
                :rowData="companiesData"
                rowSelection="multiple"
                colResizeDefault="shift"
                :animateRows="true"
                :floatingFilter="false"
                :pagination="true"
                :paginationPageSize="paginationPageSize"
                :suppressPaginationPanel="true"
                :enableRtl="$vs.rtl"
                @selection-changed="onSelectedItemsChanged"
            ></ag-grid-vue>

            <vs-pagination :total="totalPages" :max="7" v-model="currentPage" />
        </div>
    </div>
</template>

<script>
import { AgGridVue } from 'ag-grid-vue'
import '@sass/vuexy/extraComponents/agGridStyleOverride.scss'
import vSelect from 'vue-select'

import moment from 'moment'

// Store Module
import moduleCompanyManagement from '@/store/company-management/moduleCompanyManagement.js'

// Cell Renderer
import CellRendererLink from './cell-renderer/CellRendererLink.vue'
import CellRendererBoolean from '@/components/cell-renderer/CellRendererBoolean.vue'
import CellRendererActions from '@/components/cell-renderer/CellRendererActions.vue'

// Components
import MultipleActions from '@/components/inputs/buttons/MultipleActions.vue'

// Mixins
import { multipleActionsMixin } from '@/mixins/lists'

const model = 'company'
const modelPlurial = 'companies'
const modelTitle = 'Société'

export default {
  mixins: [multipleActionsMixin],
  components: {
    AgGridVue,
    vSelect,

    // Cell Renderer
    CellRendererLink,
    CellRendererBoolean,
    CellRendererActions,

    // Components
    MultipleActions
  },
  data () {
    return {
      searchQuery: '',

      // AgGrid
      gridApi: null,
      gridOptions: {
        localeText: { noRowsToShow: 'Aucune société à afficher' },
        rowClassRules: {
          'subscription-ending' (params) {
            return (
              !params.data.deleted_at &&
                            params.data.has_active_subscription &&
                            moment(
                              params.data.active_subscription.ends_at
                            ).isBefore(moment().add(1, 'month'))
            )
          }
        }
      },
      defaultColDef: {
        resizable: true,
        suppressMenu: true
      },
      columnDefs: [
        {
          filter: false,
          width: 40,
          checkboxSelection: true,
          headerCheckboxSelectionFilteredOnly: false,
          headerCheckboxSelection: true
        },
        {
          headerName: 'Période d\'essai',
          field: 'is_trial',
          filter: true,
          sortable: true,
          cellRendererFramework: 'CellRendererBoolean',
          valueGetter: params => {
            let bool = null
            if (params.data.has_active_subscription) {
              bool = params.data.active_subscription.is_trial
            }
            return bool
          }
        },
        {
          headerName: 'Abonnement',
          field: 'active_subscription',
          filter: true,
          sortable: true,
          valueGetter: params => {
            let text = 'Aucun'
            if (params.data.has_active_subscription) {
              text = params.data.active_subscription.packages
                .map(p => p.display_name)
                .join(', ')
            }
            return text
          }
        },
        {
          headerName: 'Début',
          field: 'starts_at',
          filter: true,
          sortable: true,
          valueGetter: params => {
            let text = ''

            if (params.data.has_active_subscription) {
              text = moment(
                params.data.active_subscription.starts_at
              ).format('DD/MM/YYYY')
            }

            return text
          }
        },
        {
          headerName: 'Fin',
          field: 'ends_at',
          filter: true,
          sortable: true,
          valueGetter: params => {
            let text = ''

            if (params.data.has_active_subscription) {
              text = moment(
                params.data.active_subscription.ends_at
              ).format('DD/MM/YYYY')
            }

            return text
          }
        },
        {
          headerName: 'Nom',
          field: 'name',
          filter: true,
          sortable: true
        },
        {
          headerName: 'Nombre d\'utilisateurs',
          field: 'user_count',
          filter: true,
          sortable: true
        },
        {
          headerName: 'Actions',
          field: 'transactions',
          type: 'numericColumn',
          cellRendererFramework: 'CellRendererActions',
          cellRendererParams: {
            model: 'company',
            modelPlurial: 'companies',
            withPrompt: false,
            name: data => `la société ${data.name}`,
            footNotes: {
              restore:
                                'Si vous restaurez la société ses utilisateurs, projets et gammes seront également restauré.',
              archive:
                                'Si vous archivez la société ses utilisateurs, projets et gammes seront également archivé.',
              delete:
                                'Si vous supprimez la société ses utilisateurs, projets et gammes seront également supprimé.'
            }
          }
        }
      ],

      // Cell Renderer Components
      components: {
        CellRendererLink,
        CellRendererActions
      }
    }
  },
  watch: {},
  computed: {
    companiesData () {
      return this.$store.state.companyManagement.companies
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
      return this.$store.state.companyManagement.company.id || 0
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
    authorizedTo (action, model = modelPlurial) {
      return this.$store.getters.userHasPermissionTo(
        `${action} ${model}`
      )
    },
    addRecord () {
      this.$router.push(`/${modelPlurial}/${model}-add/`).catch(() => {})
    },
    onResize (event) {
      if (this.gridApi) {
        // refresh the grid
        this.gridApi.refreshView()

        // resize columns in the grid to fit the available space
        this.gridApi.sizeColumnsToFit()
      }
    }
  },
  mounted () {
    this.gridApi = this.gridOptions.api

    window.addEventListener('resize', this.onResize)
    if (this.gridApi) {
      // refresh the grid
      this.gridApi.refreshView()

      // resize columns in the grid to fit the available space
      this.gridApi.sizeColumnsToFit()

      this.gridApi.showLoadingOverlay()
    }

    /* =================================================================
      NOTE:
      Header is not aligned properly in RTL version of agGrid table.
      However, we given fix to this issue. If you want more robust solution please contact them at gitHub
    ================================================================= */
    if (this.$vs.rtl) {
      const header = this.$refs.agGridTable.$el.querySelector(
        '.ag-header-container'
      )
      header.style.left = `-${String(
        Number(header.style.transform.slice(11, -3)) + 9
      )}px`
    }
  },
  created () {
    if (!moduleCompanyManagement.isRegistered) {
      this.$store.registerModule(
        'companyManagement',
        moduleCompanyManagement
      )
      moduleCompanyManagement.isRegistered = true
    }
    this.$store
      .dispatch('companyManagement/fetchItems', { with_trashed: true })
      .catch(err => {
        console.error(err)
      })
  },
  beforeDestroy () {
    window.removeEventListener('resize', this.onResize())

    moduleCompanyManagement.isRegistered = false
    this.$store.unregisterModule('companyManagement')
  }
}
</script>

<style lang="scss">
#page-companies-list {
    .companies-list-filters {
        .vs__actions {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-58%);
        }
    }
}

.subscription-ending {
    background-color: rgba($color: var(--vs-danger), $alpha: 1);
    color: white;
}
.subscription-ending:hover {
    background-color: rgba($color: var(--vs-danger), $alpha: 0.9);
}

.subscription-ending.ag-row-selected {
    background-color: rgba($color: var(--vs-danger), $alpha: 1);
    color: white;
}
.subscription-ending.ag-row-selected:hover {
    background-color: rgba($color: var(--vs-danger), $alpha: 0.9);
}
</style>
