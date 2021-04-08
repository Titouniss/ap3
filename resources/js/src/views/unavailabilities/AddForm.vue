<template>
  <div class="p-3 mb-4 mr-4">
    <vs-button
      type="border"
      class="items-center p-3 w-full"
      @click="activePrompt = true"
    >
      Ajouter une indisponibilité
    </vs-button>
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
        <form autocomplete="off">
          <div class="vx-row">
            <div class="vx-col w-full">
              <v-select
                v-validate="'required'"
                name="reason"
                label="name"
                :multiple="false"
                v-model="itemLocal.reason"
                :reduce="(name) => name.name"
                class="w-full mt-2 mb-2"
                autocomplete
                :options="reasons"
              >
                <template #header>
                  <div style="opacity: .8 font-size: .85rem">Motif</div>
                </template>
                <template #option="reason">
                  <span>{{ `${reason.name}` }}</span>
                </template>
              </v-select>
              <span
                v-if="itemLocal.reason === 'Autre...'"
                class="text-danger text-sm"
                v-show="errors.has('reason')"
                >{{ errors.first("reason") }}</span
              >
              <vs-input
                v-if="itemLocal.reason === 'Autre...'"
                name="custom_reason"
                class="w-full mb-4 mt-6"
                placeholder="Motif personnalisé"
                v-model="custom_reason"
                v-validate="'required'"
                :color="!errors.has('custom_reason') ? 'success' : 'danger'"
              />
              <flat-pickr
                v-if="
                  (itemLocal.reason == 'Autre...' && custom_reason !== '') ||
                  (itemLocal.reason !== '' && itemLocal.reason !== 'Autre...')
                "
                name="starts_at"
                class="w-full mb-4 mt-5"
                :config="configStartsAtDateTimePicker"
                v-model="itemLocal.starts_at"
                placeholder="Date de début"
                @on-change="onStartsAtChange"
              />
              <flat-pickr
                v-if="
                  (itemLocal.reason == 'Autre...' && custom_reason !== '') ||
                  (itemLocal.reason !== '' &&
                    itemLocal.reason !== 'Autre...' &&
                    itemLocal.starts_at)
                "
                name="ends_at"
                class="w-full mb-2 mt-5"
                :config="configEndsAtDateTimePicker"
                v-model="itemLocal.ends_at"
                placeholder="Date de fin"
                @on-change="onEndsAtChange"
              />
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
import vSelect from "vue-select";
import "flatpickr/dist/flatpickr.css";
import { French as FrenchLocale } from "flatpickr/dist/l10n/fr.js";
import moment from "moment";

// register custom messages
Validator.localize("fr", errorMessage);

export default {
  components: {
    flatPickr,
    vSelect,
  },
  props: ['id_user'],
  data() {
    return {
      activePrompt: false,

      itemLocal: {
        user_id: null,
        starts_at: null,
        ends_at: null,
        reason: "",
      },
      custom_reason: "",
      reasons: [
        { name: "Utilisation heures supplémentaires" },
        { name: "Jours fériés" },
        { name: "Rendez-vous privé" },
        { name: "Congés payés" },
        { name: "Période de cours" },
        { name: "Arrêt de travail" },
        { name: "Autre..." },
      ],

      configStartsAtDateTimePicker: {
        disableMobile: "true",
        enableTime: true,
        locale: FrenchLocale,
        minDate: null,
        maxDate: null,
      },
      configEndsAtDateTimePicker: {
        disableMobile: "true",
        enableTime: true,
        locale: FrenchLocale,
        minDate: null,
      },
    };
  },
  computed: {
    validateForm() {
      if (this.itemLocal.reason === "Autre...") {
        return (
          !this.errors.any() &&
          this.itemLocal.starts_at &&
          this.itemLocal.ends_at &&
          this.itemLocal.reason !== "" &&
          this.custom_reason !== ""
        );
      } else {
        return (
          !this.errors.any() &&
          this.itemLocal.starts_at &&
          this.itemLocal.ends_at &&
          this.itemLocal.reason !== ""
        );
      }
    },
  },
  methods: {
    clearFields() {
      Object.assign(this.itemLocal, {
        user_id:null,
        starts_at: null,
        ends_at: null,
        reason: "",
      });
      this.custom_reason = "";
      Object.assign(this.configStartsAtDateTimePicker, {
        disableMobile: "true",
        enableTime: true,
        locale: FrenchLocale,
        minDate: null,
        maxDate: null,
      });
      Object.assign(this.configEndsAtDateTimePicker, {
        disableMobile: "true",
        enableTime: true,
        locale: FrenchLocale,
        minDate: null,
      });
    },
    onStartsAtChange(selectedDates, dateStr, instance) {
      this.$set(this.configEndsAtDateTimePicker, "minDate", dateStr);
    },
    onEndsAtChange(selectedDates, dateStr, instance) {
      this.$set(this.configStartsAtDateTimePicker, "maxDate", dateStr);
    },
    addUnavailability() {
      this.$validator.validateAll().then((result) => {
        if (result) {
          const item = JSON.parse(JSON.stringify(this.itemLocal));
          if (this.itemLocal.reason === "Autre...") {
            item.reason = this.custom_reason;
          }
          item.starts_at = moment(item.starts_at).format("YYYY-MM-DD HH:mm");
          item.ends_at = moment(item.ends_at).format("YYYY-MM-DD HH:mm");
          item.user_id = this.id_user;
          this.$store
            .dispatch("unavailabilityManagement/addItem", item)
            .then((data) => {
              this.$vs.notify({
                title: "Ajout d'une indisponibilité",
                text: `Indisponibilité ajoutée avec succès`,
                iconPack: "feather",
                icon: "icon-alert-circle",
                color: "success",
              });
            })
            .catch((error) => {
              this.$vs.notify({
                title: "Erreur",
                text: error.message,
                iconPack: "feather",
                icon: "icon-alert-circle",
                color: "danger",
                time: 10000,
              });
            })
            .finally(() => {
              this.$vs.loading.close();
              this.clearFields();
            });
        }
      });
    },
  },
  mounted(){
    if(this.$store.state.AppActiveUser.is_admin){
      this.reasons.unshift({ name: "Heures supplémentaires payées" });
    }
  }
};
</script>
