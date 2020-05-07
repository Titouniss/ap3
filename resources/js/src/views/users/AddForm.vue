<template>
  <div class="p-3 mb-4 mr-4">
    <vs-button @click="activePrompt = true" class="w-full">Ajouter un utilisateur</vs-button>
    <vs-prompt
      title="Ajouter un utilisateur"
      accept-text="Ajouter"
      cancel-text="Annuler"
      button-cancel="border"
      @cancel="clearFields"
      @accept="addItem"
      @close="clearFields"
      :is-valid="validateForm"
      :active.sync="activePrompt"
    >
      <div>
        <form>
          <div class="vx-row">
            <div class="vx-col w-full">
              <vs-input
                v-validate="'required'"
                name="lastname"
                class="w-full mb-4 mt-5"
                placeholder="Nom"
                v-model="itemLocal.lastname"
                :color="!errors.has('lastname') ? 'success' : 'danger'"
              />
              <span
                class="text-danger text-sm"
                v-show="errors.has('lastname')"
              >{{ errors.first('lastname') }}</span>
              <vs-input
                v-validate="'required'"
                name="firstname"
                class="w-full mb-4 mt-5"
                placeholder="Prénom"
                v-model="itemLocal.firstname"
                :color="!errors.has('firstname') ? 'success' : 'danger'"
              />
              <span
                class="text-danger text-sm"
                v-show="errors.has('firstname')"
              >{{ errors.first('firstname') }}</span>
              <vs-input
                v-validate="'required|email'"
                name="email"
                class="w-full mb-4 mt-5"
                placeholder="E-mail"
                v-model="itemLocal.email"
                :color="!errors.has('email') ? 'success' : 'danger'"
              />
              <span
                class="text-danger text-sm"
                v-show="errors.has('email')"
              >{{ errors.first('email') }}</span>

              <div class="vx-row mt-4" v-if="!disabled">
                <div class="vx-col w-full">
                  <div class="flex items-end px-3">
                    <feather-icon svgClasses="w-6 h-6" icon="LockIcon" class="mr-2" />
                    <span class="font-medium text-lg leading-none">Admin</span>
                  </div>
                  <vs-divider />
                  <div>
                    <vs-select
                      v-validate="'required'"
                      name="company_id"
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
                    <span
                      class="text-danger text-sm"
                      v-show="errors.has('company_id')"
                    >{{ errors.first('company_id') }}</span>
                  </div>
                </div>
              </div>

              <div>
                <vs-select
                  v-validate="'required'"
                  name="role"
                  label="Rôle"
                  :multiple="true"
                  v-model="itemLocal.roles"
                  class="w-full mt-5"
                  autocomplete
                >
                  <vs-select-item
                    :key="index"
                    :value="item.id"
                    :text="item.name"
                    v-for="(item,index) in rolesData"
                  />
                </vs-select>
                <span
                  class="text-danger text-sm"
                  v-show="errors.has('company_id')"
                >{{ errors.first('company_id') }}</span>
              </div>
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

// register custom messages
Validator.localize("fr", errorMessage);

var model = "user";
var modelPlurial = "users";

export default {
  data() {
    return {
      activePrompt: false,
      itemLocal: {
        firstname: "",
        lastname: "",
        email: "",
        company_id: null,
        roles: []
      }
    };
  },
  computed: {
    companiesData() {
      return this.$store.state.companyManagement.companies;
    },
    rolesData() {
      return this.$store.getters["roleManagement/getItemsForCompany"](
        this.itemLocal.company_id
      );
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
    },
    validateForm() {
      return (
        !this.errors.any() &&
        this.itemLocal.name != "" &&
        this.itemLocal.firstname != "" &&
        this.itemLocal.email != "" &&
        this.itemLocal.company_id != null &&
        this.itemLocal.roles.length > 0
      );
    }
  },
  methods: {
    clearFields() {
      Object.assign(this.itemLocal, {
        itemLocal: {
          email: "",
          company_id: null,
          roles: []
        }
      });
    },
    addItem() {
      this.$validator.validateAll().then(result => {
        if (result) {
          this.$store
            .dispatch(
              "userManagement/addItem",
              Object.assign({}, this.itemLocal)
            )
            .then(() => {
              this.$vs.loading.close();
              this.$vs.notify({
                title: "Ajout d'un utilisateur",
                text: `Utilisateur ajouté avec succès`,
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
    }
  }
};
</script>
