<!-- =========================================================================================
  File Name: UserEditTabSocial.vue
  Description: User Edit Social Tab content
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
========================================================================================== -->

<template>
  <div id="user-edit-tab-planning">

    <ul class="vx-timeline">
        <li>
            <div class="timeline-icon" :class="`bg-primary`">
                <feather-icon icon="CalendarIcon" svgClasses="text-white stroke-current w-5 h-5" />
            </div>
            <div class="timeline-info">
                <p class="font-semibold">Lundi</p>
                <vs-slider color="danger" v-model="days[0]" step=1 :min=0 :max=1440 />
            </div>
        </li>
        <li>
            <div class="timeline-icon" :class="`bg-primary`">
                <feather-icon icon="CalendarIcon" svgClasses="text-white stroke-current w-5 h-5" />
            </div>
            <div class="timeline-info">
                <p class="font-semibold">Mardi</p>
                <vs-slider color="danger"  v-model="days[1]" step=1 :min=0 :max=1440 />
            </div>
        </li>
                <li>
            <div class="timeline-icon" :class="`bg-primary`">
                <feather-icon icon="CalendarIcon" svgClasses="text-white stroke-current w-5 h-5" />
            </div>
            <div class="timeline-info">
                <p class="font-semibold">Mercredi</p>
                <vs-slider color="danger" v-model="days[2]" step=1 :min=0 :max=1440 />
            </div>
        </li>
                <li>
            <div class="timeline-icon" :class="`bg-primary`">
                <feather-icon icon="CalendarIcon" svgClasses="text-white stroke-current w-5 h-5" />
            </div>
            <div class="timeline-info">
                <p class="font-semibold">Jeudi</p>
                <vs-slider color="danger" v-model="days[3]" step=1 :min=0 :max=1440 />
            </div>
        </li>
                <li>
            <div class="timeline-icon" :class="`bg-primary`">
                <feather-icon icon="CalendarIcon" svgClasses="text-white stroke-current w-5 h-5" />
            </div>
            <div class="timeline-info">
                <p class="font-semibold">Vendredi</p>
                <vs-slider color="danger" v-model="days[4]" step=1 :min=0 :max=1440 />
            </div>
        </li>
        <li>
            <div class="timeline-icon" :class="`bg-primary`">
                <feather-icon icon="CalendarIcon" svgClasses="text-white stroke-current w-5 h-5" />
            </div>
            <div class="timeline-info">
                <p class="font-semibold">Samedi</p>
                <vs-slider color="danger" v-model="days[5]" step=1 :min=0 :max=1440 />
            </div>
        </li>
                        <li>
            <div class="timeline-icon" :class="`bg-primary`">
                <feather-icon icon="CalendarIcon" svgClasses="text-white stroke-current w-5 h-5" />
            </div>
            <div class="timeline-info">
                <p class="font-semibold">Dimanche</p>
                <vs-slider color="danger" v-model="days[6]" step=1 :min=0 :max=1440 />
            </div>
        </li>
    </ul>
  

    <vs-table ref="table" multiple v-model="selected" pagination search :data="data_local.indisponibilities || []">

      <div slot="header" class="flex flex-wrap-reverse items-center flex-grow justify-between">

        <div class="flex flex-wrap-reverse items-center data-list-btn-container">

          <!-- ACTION - DROPDOWN -->
          <vs-dropdown vs-trigger-click class="dd-actions cursor-pointer mr-4 mb-4">

            <div class="p-4 shadow-drop rounded-lg d-theme-dark-bg cursor-pointer flex items-center justify-center text-lg font-medium w-32 w-full">
              <span class="mr-2">Actions</span>
              <feather-icon icon="ChevronDownIcon" svgClasses="h-4 w-4" />
            </div>

            <vs-dropdown-menu>
              <vs-dropdown-item>
                <span class="flex items-center">
                  <feather-icon icon="TrashIcon" svgClasses="h-4 w-4" class="mr-2" />
                  <span>Supprimer</span>
                </span>
              </vs-dropdown-item>
            </vs-dropdown-menu>
          </vs-dropdown>

          <!-- ADD NEW -->
          <vs-button type="border" class="p-3 mb-4 mr-4" @click="addNewData">
              <feather-icon icon="PlusIcon" svgClasses="h-4 w-4" />
              <span class="ml-2 text-base text-primary">Ajouter une indisponibilité</span>
          </vs-button>
        </div>

      </div>

      <template slot="thead">
        <vs-th sort-key="start">Début</vs-th>
        <vs-th sort-key="end">Fin</vs-th>
        <vs-th sort-key="reason">Motif</vs-th>
        <vs-th>Action</vs-th>
      </template>

        <template slot-scope="{data}">
          <tbody>
            <vs-tr :data="tr" :key="indextr" v-for="(tr, indextr) in data">

              <vs-td>
                <p>{{ tr.start_at }}</p>
              </vs-td>

              <vs-td>
                <p >{{ tr.start_at }} à {{ tr.end_at }}</p>
              </vs-td>

              <vs-td>
                <p class="font-medium truncate">${{ tr.reason }}</p>
              </vs-td>

              <vs-td class="whitespace-no-wrap">
                <feather-icon icon="EditIcon" svgClasses="w-5 h-5 hover:text-primary stroke-current" @click.stop="editData(tr)" />
                <feather-icon icon="TrashIcon" svgClasses="w-5 h-5 hover:text-danger stroke-current" class="ml-2" @click.stop="deleteData(tr.id)" />
              </vs-td>

            </vs-tr>
          </tbody>
        </template>
    </vs-table>

  </div>
</template>

<script>
import Moment from 'moment'

export default {
  props: {
    data: {
      type: Object,
      required: true
    }
  },
  data () {
    return {
      selected: [], // used by checkbox to delete
      data_local: JSON.parse(JSON.stringify(this.data)),
      days: {
          0: [0,0], // monday
          1: [0,0],
          2: [0,0],
          3: [0,0],
          4: [0,0],
          5: [0,0],
          6: [0,0], // sunday
      }
    }
  },
  computed: {
    validateForm () {
      return !this.errors.any()
    }
  },
  methods: {
    addNewData () {
      return
    },
    save_changes () {
      /* eslint-disable */
      if (!this.validateForm) return

      // Here will go your API call for updating data (Also remvoe eslint-disable)
      // You can get data in "this.data_local"

      /* eslint-enable */
    },
    reset_data () {
      this.data_local = Object.assign({}, this.data)
    }
  }
}
</script>

<style lang="scss">
@import "@sass/vuexy/components/vxTimeline.scss";
</style>
