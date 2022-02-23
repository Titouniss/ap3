<template>
  <div class="mb-1">
    <span
      v-if="customSupplies.id.length > 0"
      v-on:click="activePromptSupply = true"
      class="linkTxtWarning"
    >
      - Modifier un approvisionnement
    </span>
    <span
      v-if="customSupplies.id.length == 0"
      v-on:click="activePromptSupply = true"
      class="linkTxt"
    >
      + Ajouter un approvisionnement
    </span>

    <vs-prompt
      title="Modifier un approvisionnement"
      accept-text="Valider"
      cancel-text="Annuler"
      @cancel="clearFields"
      @close="clearFields"
      button-cancel="border"
      @accept="addSupply(customSupplies)"
      :active.sync="activePromptSupply"
      class="previous-task-compose"
      is-valid="validateForm"
    >
      <div>
        <simple-select
          class="mt-3"
          v-validate="{
            required: true,
          }"
          header="Approvisionnement"
          label="name"
          name="name"
          multiple
          v-model="customSupplies.id"
          :reduce="(item) => item.id"
          :options="supplyData"
        />
      </div>
      <div>
        <small class="date-label pl-2 mt-5" style="display: block">
          Date
        </small>
        <flat-pickr
          v-validate="{
            required: true,
          }"
          v-model="customSupplies.date"
          class="w-full"
          label="Date"
          header="Date"
          name="date"
          :config="configdateTimePicker"
        />
      </div>
    </vs-prompt>
  </div>
</template>

<script>
import SimpleSelect from "@/components/inputs/selects/SimpleSelect.vue";
import flatPickr from "vue-flatpickr-component";
import { French as FrenchLocale } from "flatpickr/dist/l10n/fr.js";
import "flatpickr/dist/flatpickr.css";
export default {
  components: {
    SimpleSelect,
    flatPickr,
    FrenchLocale,
  },
  props: {
    addSupply: { type: Function },
    current_task_id: { type: Number },
    tasks_list: { required: true },
    customSupplies: { required: true },
  },
  data() {
    return {
      activePromptSupply: false,
      configdateTimePicker: {
        disableMobile: "true",
        dateFormat: "Y-m-d",
        altFormat: "d/m/Y",
        altInput: true,
        locale: FrenchLocale,
        minDate: null,
        maxDate: null,
      },
    };
  },
  computed: {
    supplyData() {
      return this.$store.getters["supplyManagement/getItems"];
    },
  },
  methods: {

    clearFields() {
        this.customSupplies;
    },
  },
};
</script>

<style>
.previous-task-compose {
  z-index: 52007;
}
.linkTxt {
  font-size: 0.8em;
  color: #2196f3;
  background-color: #e9e9ff;
  border-radius: 4px;
  margin: 3px;
  padding: 3px 4px;
  font-weight: 500;
}
.linkTxtWarning {
  font-size: 0.8em;
  color: #ff6600;
  background-color: #e9e9ff;
  border-radius: 4px;
  margin: 3px;
  padding: 3px 4px;
  font-weight: 500;
}
.linkTxt:hover {
  cursor: pointer;
  background-color: #efefef;
}
.linkTxtWarning:hover {
  cursor: pointer;
  background-color: #efefef;
}
</style>
