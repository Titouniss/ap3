<template>
  <div class="p-3 mb-4 mr-4">
    <vs-button @click="activePrompt = true" class="w-full">Ajouter un projet</vs-button>
    <vs-prompt
      title="Ajouter un projet"
      accept-text="Ajouter"
      cancel-text="Annuler"
      button-cancel="border"
      @cancel="clearFields"
      @accept="addProject"
      @close="clearFields"
      :is-valid="validateForm"
      :active.sync="activePrompt"
    >
      <div>
        <form>
          <div class="vx-row">
            <div class="vx-col w-full">
              <vs-input
                v-validate="'required|max:255'"
                name="name"
                class="w-full mb-4 mt-5"
                placeholder="Nom"
                v-model="itemLocal.name"
                :color="!errors.has('name') ? 'success' : 'danger'"
              />
              <span
                class="text-danger text-sm"
                v-show="errors.has('name')"
              >{{ errors.first('name') }}</span>
              <div class="my-4">
                <small class="date-label">Date de livraison prévue</small>
                <datepicker
                  class="pickadate"
                  :disabledDates="{ to: new Date(Date.now() - 8640000) }"
                  :language="langFr"
                  name="date"
                  v-model="itemLocal.date"
                  :color="validateForm ? 'success' : 'danger'"
                ></datepicker>
              </div>
              <div class="my-4">
                <small class="date-label">Client</small>
                <vs-input
                  name="client"
                  class="w-full mb-4"
                  placeholder="Client (RAF)"
                  v-model="itemLocal.client"
                />
              </div>
              <div class="my-4" v-if="itemLocal.company_id != null">
                <small class="date-label">Gammes</small>
                <vs-select
                  v-if="rangesData.length > 0"
                  v-model="itemLocal.ranges"
                  class="w-full"
                  multiple
                  autocomplete
                  v-validate="'required'"
                  name="ranges"
                >
                  <vs-select-item
                    :key="index"
                    :value="item.id"
                    :text="item.name"
                    v-for="(item,index) in rangesData"
                  />
                </vs-select>
                <small
                  v-if="rangesData.length == 0"
                  style="font-style: italic; color: #C8C8C8"
                >Aucunes gammes trouvées</small>
              </div>
              <div class="vx-row mt-4" v-if="!disabled">
                <div class="vx-col w-full">
                  <div class="flex items-end px-3">
                    <feather-icon svgClasses="w-6 h-6" icon="LockIcon" class="mr-2" />
                    <span class="font-medium text-lg leading-none">Admin</span>
                  </div>
                  <vs-divider />
                  <vs-select
                    name="company"
                    v-validate="'required'"
                    label="Compagnie"
                    v-model="itemLocal.company_id"
                    class="w-full mt-5"
                  >
                    <vs-select-item
                      :key="index"
                      :value="item.id"
                      :text="item.name"
                      v-for="(item,index) in companiesData"
                    />
                  </vs-select>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
    </vs-prompt>
  </div>
</template>

<script>
import Datepicker from "vuejs-datepicker";
import { fr } from "vuejs-datepicker/src/locale";
import moment from "moment";
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";

// register custom messages
Validator.localize("fr", errorMessage);

export default {
  components: {
    Datepicker
  },
  data() {
    return {
      activePrompt: false,
      langFr: fr,

      itemLocal: {
        name: "",
        date: new Date(),
        clients: "",
        ranges: "",
        company_id: null
      }
    };
  },
  computed: {
    validateForm() {
      return (
        !this.errors.any() &&
        this.itemLocal.name != "" &&
        this.itemLocal.company_id != null
      );
    },
    companiesData() {
      return this.$store.state.companyManagement.companies;
    },
    rangesData() {
      return this.filterItemsAdmin(this.$store.state.rangeManagement.ranges);
    },
    disabled() {
      const user = this.$store.state.AppActiveUser;
      if (user.roles && user.roles.length > 0) {
        if (
          user.roles.find(
            r => r.name === "superAdmin" || r.name === "littleAdmin"
          )
        ) {
          return false;
        } else {
          this.itemLocal.company_id = user.company_id;
          return true;
        }
      } else return true;
    }
  },
  methods: {
    clearFields() {
      this.itemLocal = {
        name: "",
        date: new Date(),
        clients: "",
        ranges: "",
        company_id: null
      };
    },
    addProject() {
      this.$validator.validateAll().then(result => {
        this.itemLocal.date = moment(this.itemLocal.date).format("YYYY-MM-DD");
        if (result) {
          this.$store
            .dispatch(
              "projectManagement/addItem",
              Object.assign({}, this.itemLocal)
            )
            .then(() => {
              this.clearFields();
              this.$vs.loading.close();
              this.$vs.notify({
                title: "Ajout d'un projet",
                text: `"${this.itemLocal.name}" ajouté avec succès`,
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
        }
      });
    },
    filterItemsAdmin(items) {
      let filteredItems = [];
      const user = this.$store.state.AppActiveUser;
      if (user.roles && user.roles.length > 0) {
        if (
          user.roles.find(
            r => r.name === "superAdmin" || r.name === "littleAdmin"
          )
        ) {
          filteredItems = items.filter(
            item => item.company.id === this.itemLocal.company_id
          );
        } else {
          filteredItems = items;
        }
      }
      return filteredItems;
    }
  }
};
</script>
