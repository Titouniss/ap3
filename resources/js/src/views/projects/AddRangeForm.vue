<template>
  <div>
    <vs-button
      @click="activePrompt = true"
      size="small"
      color="success"
      type="gradient"
      icon-pack="feather"
      icon="icon-plus"
    ></vs-button>
    <vs-prompt
      title="Ajouter une gamme au projet"
      accept-text="Ajouter"
      cancel-text="Annuler"
      button-cancel="border"
      @cancel="clearFields"
      @accept="addRange"
      @close="clearFields"
      :is-valid="validateForm"
      :active.sync="activePrompt"
    >
      <div>
        <form autocomplete="off">
          <vx-tooltip
            style="z-index: 52007"
            title="Information"
            color="dark"
            text="Le préfixe va vous servir pour différencier vos différentes gammes"
          >
            <vs-input
              icon-pack="feather"
              icon="icon-info"
              v-validate="'required|numeric'"
              name="prefix"
              class="w-full mb-4 mt-1"
              placeholder="Préfixe"
              v-model="itemLocal.prefix"
              maxlength="5"
              @input="onPrefixChange"
            />
            <v-select
              label="name"
              v-model="itemLocal.range"
              :options="rangesData"
              class="w-full"
            >
              <template #header>
                <div class="vs-select--label">Gamme</div>
              </template>
            </v-select>
          </vx-tooltip>
        </form>
        <div v-if="rangesData.length === 0" class="mt-12 mb-2">
          <span label="Compétences" class="msgTxt mt-10"
            >Aucune gammes trouvées.</span
          >
          <router-link class="linkTxt" :to="{ path: '/ranges' }"
            >Ajouter une gamme</router-link
          >
        </div>
      </div>
    </vs-prompt>
  </div>
</template>

<script>
import moment from "moment";
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";
import vSelect from "vue-select";

// register custom messages
Validator.localize("fr", errorMessage);

export default {
  components: {
    vSelect,
  },
  props: {
    company_id: {
      required: true,
    },
    project_id: {
      required: true,
    },
    refreshData: {},
  },
  data() {
    return {
      activePrompt: false,

      itemLocal: {
        prefix: "",
        range: "",
      },
    };
  },
  computed: {
    isAdmin() {
      return this.$store.state.AppActiveUser.is_admin;
    },
    validateForm() {
      return (
        this.itemLocal.prefix != "" &&
        this.itemLocal.range != null &&
        this.itemLocal.range != ""
      );
    },
    rangesData() {
      return this.filterItemsAdmin(
        this.$store.getters["rangeManagement/getItems"]
      );
    },
  },
  methods: {
    clearFields() {
      Object.assign(this.itemLocal, {
        prefix: "",
        range: "",
        rangeId: "",
      });
    },
    addRange() {
      this.itemLocal.rangeId = this.itemLocal.range.id;
      this.itemLocal.project_id = this.project_id;

      this.$store
        .dispatch(
          "taskManagement/addItemRange",
          Object.assign({}, this.itemLocal)
        )
        .then(() => {
          if (this.refreshData) {
            this.refreshData();
          }
          this.$vs.loading.close();
          this.$vs.notify({
            title: "Ajout d'une gamme au projet",
            text: `"${this.itemLocal.range.name}" ajouté avec succès`,
            iconPack: "feather",
            icon: "icon-alert-circle",
            color: "success",
          });
        })
        .catch((error) => {
          this.$vs.loading.close();
          this.$vs.notify({
            title: "Error",
            text: error.message,
            iconPack: "feather",
            icon: "icon-alert-circle",
            color: "danger",
          });
        });
    },
    filterItemsAdmin(items) {
      let filteredItems = items;
      const user = this.$store.state.AppActiveUser;
      if (this.isAdmin) {
        filteredItems = items.filter(
          (item) => item.company.id === this.company_id
        );
      }
      return filteredItems;
    },
    onPrefixChange() {
      this.itemLocal.prefix = this.itemLocal.prefix.toUpperCase();
    },
  },
};
</script>
<style>
.vs-tooltip {
  z-index: 99999 !important;
}
</style>
