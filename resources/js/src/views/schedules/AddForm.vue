<template>
  <div class="p-3 mb-4 mr-4">
    <vs-button @click="activePrompt = true" class="w-full">Ajouter une tâche</vs-button>
    <vs-prompt
      title="Ajouter une tâche"
      accept-text="Ajouter"
      cancel-text="Annuler"
      button-cancel="border"
      @cancel="clearFields"
      @accept="addItem"
      @close="clearFields"
      :is-valid="validateForm"
      :active.sync="showPrompt"
    >
      <div>
        <form autocomplete="off">
          <div class="vx-row">
            <div class="vx-col w-full">
              <p>Nom</p>
              <vs-input
                v-validate="'required|max:255'"
                name="title"
                class="w-full mb-4"
                v-model="itemLocal.title"
                :color="!errors.has('title') ? 'success' : 'danger'"
              />
              <span
                class="text-danger text-sm"
                v-show="errors.has('title')"
              >{{ errors.first('title') }}</span>

              <p class="mt-5">Date de début</p>
              <flat-pickr
                v-validate="'required'"
                name="startDate"
                :config="configDatePicker()"
                class="w-full"
                v-model="itemLocal.start"
                :color="!errors.has('startDate') ? 'success' : 'danger'"
              />
              <span
                class="text-danger text-sm"
                v-show="errors.has('startDate')"
              >{{ errors.first('startDate') }}</span>

              <p class="mt-5">Date de fin</p>
              <flat-pickr
                v-validate="'required'"
                name="endDate"
                :config="configDatePicker()"
                class="w-full"
                v-model="itemLocal.end"
                :color="!errors.has('endDate') ? 'success' : 'danger'"
              />
              <span
                class="text-danger text-sm"
                v-show="errors.has('endDate')"
              >{{ errors.first('endDate') }}</span>
            </div>
          </div>
        </form>
      </div>
    </vs-prompt>
  </div>
</template>

<script>
// Store Module
import moduleScheduleManagement from "@/store/schedule-management/moduleScheduleManagement.js";

// register custom messages
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";
Validator.localize("fr", errorMessage);

var model = "tâche";
var modelPlurial = "tâches";

// FlatPickr
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import { French as FrenchLocale } from "flatpickr/dist/l10n/fr.js";

export default {
  props: {
    handleClose: {
      type: Function,
      required: true
    },
    activeAddPrompt: {
      type: Boolean,
      required: true
    },
    dateData: {
      type: Object,
      required: true
    }
  },
  components: {
    flatPickr
  },
  data() {
    return {
      activePrompt: false,
      itemLocal: {
        title: "",
        start: "",
        end: "",
        label: ""
      },
      configDatePicker: () => ({
        disableMobile: "true",
        enableTime: true,
        locale: FrenchLocale,
        dateFormat: "Y-m-d H:i",
        altFormat: "j F Y, H:i",
        altInput: true
      })
    };
  },
  computed: {
    showPrompt: {
      get() {
        if (this.activePrompt || this.activeAddPrompt) {
          if (this.activeAddPrompt) {
            this.itemLocal.start = this.dateData.dateStr;
          }
          return true;
        } else {
          return false;
        }
      },
      set(value) {
        return value;
      }
    },
    validateForm() {
      return (
        !this.errors.any() &&
        this.itemLocal.title !== "" &&
        this.itemLocal.start !== "" &&
        this.itemLocal.end !== ""
      );
    }
  },
  methods: {
    clearFields() {
      this.itemLocal = {
        title: "",
        start: "",
        end: "",
        label: ""
      };
      (this.activePrompt = false), this.handleClose();
    },
    addItem() {
      this.$validator.validateAll().then(result => {
        if (result) {
          this.$store.dispatch("scheduleManagement/addEvent", this.itemLocal);
          this.clearFields();
        }
      });
    }
  },
  created() {}
};
</script>
