<!-- =========================================================================================
  File Name: UserEditTabInformation.vue
  Description: User Edit Information Tab content
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
========================================================================================== -->

<template>
  <div id="user-edit-tab-info">
    <div class="vx-row">
      <div class="vx-col w-full md:w-1/2">
        <!-- Col Header -->
        <div class="flex items-end">
          <feather-icon icon="UserIcon" class="mr-2" svgClasses="w-5 h-5" />
          <span class="leading-none font-medium">Informations Personnelles</span>
        </div>

        <!-- Col Content -->
        <div>
          <!-- DOB -->
          <div class="mt-4">
            <label class="text-sm">Date de naissance</label>
            <flat-pickr
              v-model="data_local.birth_date"
              :config="{ dateFormat: 'Y/m/d', maxDate: new Date() }"
              class="w-full"
              v-validate="'required'"
              name="dob"
            />
            <span class="text-danger text-sm" v-show="errors.has('dob')">{{ errors.first('dob') }}</span>
          </div>

          <vs-input
            class="w-full mt-4"
            label="Téléphone"
            v-model="data_local.phone_number"
            v-validate="{regex: '^\\+?([0-9]+).{9}$' }"
            maxlength="10"
            name="mobile"
          />
          <span
            class="text-danger text-sm"
            v-show="errors.has('mobile')"
          >{{ errors.first('mobile') }}</span>

          <!-- <vs-input
            class="w-full mt-4"
            label="site web"
            v-model="data_local.website"
            v-validate="'url:require_protocol'"
            name="website"
            data-vv-as="field"
          />
          <span
            class="text-danger text-sm"
            v-show="errors.has('website')"
          >{{ errors.first('website') }}</span>-->

          <!-- <div class="mt-4">
            <label class="text-sm">Languages</label>
            <v-select
              v-model="data_local.languages_known"
              multiple
              :closeOnSelect="false"
              :options="langOptions"
              v-validate="'required'"
              name="lang_known"
              :dir="$vs.rtl ? 'rtl' : 'ltr'"
            />
            <span
              class="text-danger text-sm"
              v-show="errors.has('lang_known')"
            >{{ errors.first('lang_known') }}</span>
          </div>-->

          <!-- Gender -->
          <!-- <div class="mt-4">
            <label class="text-sm">Sexe</label>
            <div class="mt-2">
              <vs-radio
                v-on:change="changeGenre"
                :checked="genre === 'H'"
                color="primary"
                vs-name="radio_genre"
                class="mr-4"
              >Homme</vs-radio>
              <vs-radio
                v-on:change="changeGenre"
                :checked="genre === 'F'"
                color="primary"
                vs-name="radio_genre"
                class="mr-4"
              >Femme</vs-radio>
            </div>
          </div>-->

          <div class="mt-4">
            <label class="text-sm">Sexe</label>
            <div class="mt-2">
              <vs-radio
                v-model="genre"
                vs-value="H"
                color="primary"
                vs-name="radio_genre"
                class="mr-4"
              >Homme</vs-radio>
              <vs-radio
                v-model="genre"
                vs-value="F"
                color="primary"
                vs-name="radio_genre"
                class="mr-4"
              >Femme</vs-radio>
            </div>
          </div>

          <div class="mt-6">
            <label>Options de contact</label>
            <div class="flex flex-wrap mt-1">
              <vs-checkbox
                v-model="data_local.contact_options"
                vs-value="email"
                class="mr-4 mb-2"
                disabled="true"
              >Email</vs-checkbox>
              <vs-checkbox
                v-model="data_local.contact_options"
                vs-value="message"
                class="mr-4 mb-2"
                disabled="true"
              >Message</vs-checkbox>
              <vs-checkbox
                v-model="data_local.contact_options"
                vs-value="Phone"
                class="mb-2"
                disabled="true"
              >Téléphone</vs-checkbox>
            </div>
          </div>
        </div>
      </div>

      <!-- Address Col -->
      <div class="vx-col w-full md:w-1/2">
        <!-- Col Header -->
        <div class="flex items-end md:mt-0 mt-base">
          <feather-icon icon="MapPinIcon" class="mr-2" svgClasses="w-5 h-5" />
          <span class="leading-none font-medium">Adresse</span>
        </div>

        <!-- Col Content -->
        <div>
          <vs-input
            class="w-full mt-4"
            label="Adresse ligne 1"
            v-validate="'required'"
            name="addd_line_1"
            disabled="true"
          />
          <span
            class="text-danger text-sm"
            v-show="errors.has('addd_line_1')"
          >{{ errors.first('addd_line_1') }}</span>

          <vs-input class="w-full mt-4" label="Adresse ligne 2" disabled="true" />

          <vs-input
            class="w-full mt-4"
            label="Code postale"
            v-validate="'required|numeric'"
            name="post_code"
            disabled="true"
          />
          <span
            class="text-danger text-sm"
            v-show="errors.has('post_code')"
          >{{ errors.first('post_code') }}</span>

          <vs-input
            class="w-full mt-4"
            label="Ville"
            v-validate="'required|alpha'"
            name="city"
            disabled="true"
          />
          <span class="text-danger text-sm" v-show="errors.has('city')">{{ errors.first('city') }}</span>

          <!-- <vs-input
            class="w-full mt-4"
            label="Départements"
            v-validate="'required|alpha'"
            name="state"
          />
          <span class="text-danger text-sm" v-show="errors.has('state')">{{ errors.first('state') }}</span>

          <vs-input class="w-full mt-4" label="Pays" v-validate="'required|alpha'" name="country" />
          <span
            class="text-danger text-sm"
            v-show="errors.has('country')"
          >{{ errors.first('country') }}</span>-->
        </div>
      </div>
    </div>

    <!-- Save & Reset Button -->
    <div class="vx-row">
      <div class="vx-col w-full">
        <div class="mt-8 flex flex-wrap items-center justify-end">
          <vs-button
            class="ml-auto mt-2"
            @click="save_changes"
            :disabled="!validateForm"
          >Sauvegarder</vs-button>
          <vs-button
            class="ml-4 mt-2"
            type="border"
            color="warning"
            @click="reset_data"
          >Réinitialiser</vs-button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import vSelect from "vue-select";

export default {
  components: {
    vSelect,
    flatPickr
  },
  props: {
    data: {
      type: Object,
      required: true
    }
  },
  data() {
    console.log(this.data);

    return {
      data_local: JSON.parse(JSON.stringify(this.data)),

      langOptions: [
        { label: "English", value: "english" },
        { label: "Spanish", value: "spanish" },
        { label: "French", value: "french" },
        { label: "Russian", value: "russian" },
        { label: "German", value: "german" },
        { label: "Arabic", value: "arabic" },
        { label: "Sanskrit", value: "sanskrit" }
      ]
    };
  },
  computed: {
    validateForm() {
      // console.log(["genre", this.data_local.genre]);

      return (
        !this.errors.any() &&
        this.data_local.birth_date !== "" &&
        this.data_local.phone_number &&
        (this.data_local.genre === "H" || this.data_local.genre === "F")
      );
    },
    genre: {
      get() {
        console.log(this.data_local.genre);
        return this.data_local.genre;
      },
      set(value) {
        console.log(value);
        this.data_local.genre = value;
        // this.genre = value;
      }
    }
  },
  methods: {
    changeGenre() {
      console.log("Je passe dedans");

      if (this.data_local.genre === "H") {
        this.data_local.genre = "F";
      } else {
        this.data_local.genre = "H";
      }
    },
    save_changes() {
      /* eslint-disable */
      if (!this.validateForm) return;

      // Here will go your API call for updating data
      // You can get data in "this.data_local"
      console.log(["data_local", this.data_local]);

      this.$store
        .dispatch("userManagement/updateInformationItem", this.data_local)
        .then(data => {
          console.log(["data", data]);

          this.$vs.loading.close();
          this.$vs.notify({
            title: "Modification du compte",
            text: "Vos données ont étés modifiées avec succès",
            iconPack: "feather",
            icon: "icon-alert-circle",
            color: "success"
          });
        })
        .catch(error => {
          console.log(["error", error]);

          this.$vs.loading.close();
          this.$vs.notify({
            title: "Error",
            text: "Une erreur est survenue, veuillez réessayer plus tard.",
            iconPack: "feather",
            icon: "icon-alert-circle",
            color: "danger"
          });
        });

      /* eslint-enable */
    },
    reset_data() {
      this.data_local = Object.assign({}, this.data);
    }
  }
};
</script>
