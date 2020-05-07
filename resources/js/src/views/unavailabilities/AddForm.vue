<template>
  <div class="p-3 mb-4 mr-4">
    <vs-button
      type="border"
      class="items-center p-3 w-full"
      @click="activePrompt = true"
    >Ajouter une indisponibilité</vs-button>
    <vs-prompt
      title="Ajouter une indisponibilité"
      accept-text="Ajouter"
      cancel-text="Annuler"
      button-cancel="border"
      @cancel="clearFields"
      @accept="addUnavailability"
      @close="clearFields"
      :is-valid="validateForm"
      :active.sync="activePrompt"
    >
      <div>
        <form>
          <div class="vx-row">
            <div class="vx-col w-full">
              <flat-pickr
                name="starts_at"
                class="w-full mb-4 mt-5"
                :config="configStartsAtDateTimePicker"
                v-model="itemLocal.starts_at"
                placeholder="Date de début"
                @on-change="onStartsAtChange"
              />
              <flat-pickr
                name="ends_at"
                class="w-full mb-4 mt-5"
                :config="configEndsAtDateTimePicker"
                v-model="itemLocal.ends_at"
                placeholder="Date de fin"
                @on-change="onEndsAtChange"
              />
              <vs-input
                name="reason"
                class="w-full mb-4 mt-5"
                placeholder="Motif"
                v-model="itemLocal.reason"
                v-validate="'required'"
                :color="!errors.has('reason') ? 'success' : 'danger'"
              />
              <span
                class="text-danger text-sm"
                v-show="errors.has('reason')"
              >{{ errors.first('reason') }}</span>
            </div>
          </div>
        </form>
      </div>
    </vs-prompt>
  </div>
</template>

<script>
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import { French as FrenchLocale } from "flatpickr/dist/l10n/fr.js";

// register custom messages
Validator.localize("fr", errorMessage);

export default {
  components: {
    flatPickr
  },
  data() {
    return {
      activePrompt: false,

      itemLocal: {
        starts_at: null,
        ends_at: null,
        reason: ""
      },

      configStartsAtDateTimePicker: {
        disableMobile: "true",
        enableTime: true,
        locale: FrenchLocale,
        minDate: new Date(),
        maxDate: null
      },
      configEndsAtDateTimePicker: {
        disableMobile: "true",
        enableTime: true,
        locale: FrenchLocale,
        minDate: null
      }
    };
  },
  computed: {
    validateForm() {
      return (
        !this.errors.any() &&
        this.itemLocal.starts_at &&
        this.itemLocal.ends_at &&
        this.itemLocal.reason
      );
    }
  },
  methods: {
    clearFields() {
      Object.assign(this.itemLocal, {
        starts_at: null,
        ends_at: null,
        reason: ""
      });
      Object.assign(this.configStartsAtDateTimePicker, {
        disableMobile: "true",
        enableTime: true,
        locale: FrenchLocale,
        minDate: new Date(),
        maxDate: null
      });
      Object.assign(this.configEndsAtDateTimePicker, {
        disableMobile: "true",
        enableTime: true,
        locale: FrenchLocale,
        minDate: null
      });
    },
    onStartsAtChange(selectedDates, dateStr, instance) {
      this.$set(this.configEndsAtDateTimePicker, "minDate", dateStr);
    },
    onEndsAtChange(selectedDates, dateStr, instance) {
      this.$set(this.configStartsAtDateTimePicker, "maxDate", dateStr);
    },
    addUnavailability() {
      this.$validator.validateAll().then(result => {
        if (result) {
          this.$store
            .dispatch(
              "unavailabilityManagement/addItem",
              Object.assign({}, this.itemLocal)
            )
            .then(() => {
              this.$vs.loading.close();
              this.$vs.notify({
                title: "Ajout d'une indisponibilité",
                text: `"Indisponibilité ajoutée avec succès`,
                iconPack: "feather",
                icon: "icon-alert-circle",
                color: "success"
              });
            })
            .catch(error => {
              this.$vs.loading.close();
              this.$vs.notify({
                title: "Error",
                text: error.message,
                iconPack: "feather",
                icon: "icon-alert-circle",
                color: "danger"
              });
            });
          this.clearFields();
        }
      });
    }
  }
};
</script>
