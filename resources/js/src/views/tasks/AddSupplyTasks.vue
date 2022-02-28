<template>
  <div class="mb-1">
    <span
      v-if="supply.length > 0"
      v-on:click="activePromptSupply = true"
      class="linkTxtWarning"
    >
      - Modifier un approvisionnement
    </span>
    <span
      v-if="supply.length == 0"
      v-on:click="activePromptSupply = true"
      class="linkTxt"
    >
      + Ajouter un approvisionnement
    </span>

    <vs-prompt
      title="Ajouter un approvisionnement"
      accept-text="Ajouter"
      cancel-text="Annuler"
      @cancel="clearFields"
      @close="clearFields"
      button-cancel="border"
      @accept="addSupply(supplies_local)"
      :active.sync="activePromptSupply"
      class="previous-task-compose"
    >
    <form>
      <div>
        <simple-select
          class="mt-3"
          v-validate="'required'"
          header="Approvisionnement"
          label="name"
          multiple
          v-model="supplies_local.id"
          :reduce="(item) => item.id"
          :options="supplyData"
        />
      </div>
      <div>
        <small class="date-label pl-2 mt-5" style="display: block">
          Date
        </small>
        <flat-pickr
          v-validate="'required'"
          v-model="supplies_local.date"
          class="w-full"
          label="Date"
          header="Date"
          name="date"
          :config="configdateTimePicker"
        />
      </div>  
      </form>
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
    supply: { required: true },
  },
  data() {
    return {
      activePromptSupply: false,
      supplies_local: {
        id: "",
        date: new Date()
      },
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
    // validateForm() {
    //   return (
    //             !this.errors.any() &&
    //             this.supplies_local.date !== null &&
    //             this.supplies_local.id !== "" 
               
    //         );
      
    // },
    clearFields() {
      this.supplies_local = {
        id: "",
        date: new Date(),
      };
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
