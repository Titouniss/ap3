<template>
  <div class="w-full">
    <span style="font-size: 10px;">* : Champs obligatoires | Au moins un des deux téléphone est obligatoire</span>
    <div class="pt-6 px-3 flex items-end">
      <feather-icon svgClasses="w-6 h-6" icon="BriefcaseIcon" class="mr-2" />
      <span class="font-medium text-lg leading-none"> Société </span>
    </div>
    <vs-divider />
    <vs-row vs-justify="center" vs-type="flex" vs-w="12" class="mb-6">
      <vs-col vs-w="6" vs-xs="12" class="pt-3 px-3">
        <vs-input
          v-validate="'max:255|required'"
          name="name"
          class="w-full"
          label="Nom *"
          v-model="itemLocal.name"
          :success="itemLocal.name != '' && !errors.first('name')"
          :danger="errors.has('name')"
          :danger-text="errors.first('name')"
        />
      </vs-col>
      <vs-col vs-w="6" vs-xs="12" class="pt-3 px-3">
        <vs-input
          v-validate="'numeric|min:14|max:14'"
          name="siret"
          class="w-full"
          label="Siret"
          v-model="itemLocal.siret"
          :success="itemLocal.siret != '' && !errors.has('siret')"
          :danger="errors.has('siret')"
          :danger-text="errors.first('siret')"
        />
      </vs-col>
      <vs-col v-if="showAll || isAdmin" vs-w="6" vs-xs="12" class="pt-3 px-3">
        <vs-input
          v-validate="'max:255'"
          name="type"
          class="w-full"
          label="Type"
          v-model="itemLocal.type"
          :success="itemLocal.type != '' && !errors.has('type')"
          :danger="errors.has('type')"
          :danger-text="errors.first('type')"
        />
      </vs-col>
      <vs-col v-if="showAll || isAdmin" vs-w="6" vs-xs="12" class="pt-3 px-3">
        <vs-input
          v-validate="'max:255'"
          name="code"
          class="w-full"
          label="N° client"
          v-model="itemLocal.code"
          :success="itemLocal.code != '' && !errors.has('code')"
          :danger="errors.has('code')"
          :danger-text="errors.first('code')"
        />
      </vs-col>
    </vs-row>
    <div class="pt-6 px-3 flex items-end">
      <feather-icon svgClasses="w-6 h-6" icon="UserIcon" class="mr-2" />
      <span class="font-medium text-lg leading-none"> Contact </span>
    </div>
    <vs-divider />
    <vs-row vs-justify="center" vs-type="flex" vs-w="12" class="mb-6">
      <vs-col vs-w="4" vs-xs="12" class="pt-3 px-3">
        <vs-input
          v-validate="'max:255|required'"
          name="contact_firstname"
          class="w-full"
          label="Prénom *"
          v-model="itemLocal.contact_firstname"
          :success="
            itemLocal.contact_firstname != '' &&
            !errors.has('contact_firstname')
          "
          :danger="errors.has('contact_firstname')"
          :danger-text="errors.first('contact_firstname')"
        />
      </vs-col>
      <vs-col vs-w="4" vs-xs="12" class="pt-3 px-3">
        <vs-input
          v-validate="'max:255|required'"
          name="contact_lastname"
          class="w-full"
          label="Nom *"
          v-model="itemLocal.contact_lastname"
          :success="
            itemLocal.contact_lastname != '' && !errors.has('contact_lastname')
          "
          :danger="errors.has('contact_lastname')"
          :danger-text="errors.first('contact_lastname')"
        />
      </vs-col>
      <vs-col vs-w="4" vs-xs="12" class="pt-3 px-3">
        <vs-input
          v-validate="'max:255|required'"
          name="contact_function"
          class="w-full"
          label="Fonction *"
          v-model="itemLocal.contact_function"
          :success="
            itemLocal.contact_function != '' && !errors.has('contact_function')
          "
          :danger="errors.has('contact_function')"
          :danger-text="errors.first('contact_function')"
        />
      </vs-col>
      <vs-col vs-w="4" vs-xs="12" class="pt-3 px-3">
        <vs-input
          v-validate="'max:255|email|required'"
          name="contact_email"
          class="w-full"
          label="Email *"
          v-model="itemLocal.contact_email"
          :success="
            itemLocal.contact_email != '' && !errors.has('contact_email')
          "
          :danger="errors.has('contact_email')"
          :danger-text="errors.first('contact_email')"
        />
      </vs-col>
      <vs-col vs-w="4" vs-xs="12" class="pt-3 px-3">
        <vs-input
          v-validate="`max:50${!itemLocal.contact_tel2 ? '|required' : ''}`"
          name="contact_tel1"
          class="w-full"
          label="Téléphone fixe"
          v-model="itemLocal.contact_tel1"
          :success="itemLocal.contact_tel1 != '' && !errors.has('contact_tel1')"
          :danger="errors.has('contact_tel1')"
          :danger-text="errors.first('contact_tel1')"
        />
      </vs-col>
      <vs-col vs-w="4" vs-xs="12" class="pt-3 px-3">
        <vs-input
          v-validate="`max:50${!itemLocal.contact_tel1 ? '|required' : ''}`"
          name="contact_tel2"
          class="w-full"
          label="Téléphone mobile"
          v-model="itemLocal.contact_tel2"
          :success="itemLocal.contact_tel2 != '' && !errors.has('contact_tel2')"
          :danger="errors.has('contact_tel2')"
          :danger-text="errors.first('contact_tel2')"
        />
      </vs-col>
    </vs-row>
    <div class="pt-6 px-3 flex items-end">
      <feather-icon svgClasses="w-6 h-6" icon="MapIcon" class="mr-2" />
      <span class="font-medium text-lg leading-none"> Adresse </span>
    </div>
    <vs-divider />
    <vs-row vs-justify="center" vs-type="flex" vs-w="12" class="mb-6">
      <vs-col vs-w="6" vs-xs="12" class="pt-3 px-3">
        <vs-input
          v-validate="'max:10|required'"
          name="street_number"
          class="w-full"
          label="Numéro *"
          v-model="itemLocal.street_number"
          :success="
            itemLocal.street_number != '' && !errors.has('street_number')
          "
          :danger="errors.has('street_number')"
          :danger-text="errors.first('street_number')"
        />
      </vs-col>
      <vs-col vs-w="6" vs-xs="12" class="pt-3 px-3">
        <vs-input
          v-validate="'max:255|required'"
          name="street_name"
          class="w-full"
          label="Rue *"
          v-model="itemLocal.street_name"
          :success="itemLocal.street_name != '' && !errors.has('street_name')"
          :danger="errors.has('street_name')"
          :danger-text="errors.first('street_name')"
        />
      </vs-col>
      <vs-col vs-w="4" vs-xs="12" class="pt-3 px-3">
        <vs-input
          v-validate="'max:15|required'"
          name="postal_code"
          class="w-full"
          label="Code postal *"
          v-model="itemLocal.postal_code"
          :success="itemLocal.postal_code != '' && !errors.has('postal_code')"
          :danger="errors.has('postal_code')"
          :danger-text="errors.first('postal_code')"
        />
      </vs-col>
      <vs-col vs-w="4" vs-xs="12" class="pt-3 px-3">
        <vs-input
          v-validate="'max:255|required'"
          name="city"
          class="w-full"
          label="Ville *"
          v-model="itemLocal.city"
          :success="itemLocal.city != '' && !errors.has('city')"
          :danger="errors.has('city')"
          :danger-text="errors.first('city')"
        />
      </vs-col>
      <vs-col vs-w="4" vs-xs="12" class="pt-3 px-3">
        <vs-input
          v-validate="'max:255|required'"
          name="country"
          class="w-full"
          label="Pays *"
          v-model="itemLocal.country"
          :success="itemLocal.country != '' && !errors.has('country')"
          :danger="errors.has('country')"
          :danger-text="errors.first('country')"
        />
      </vs-col>
    </vs-row>
  </div>
</template>

<script>
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";
Validator.localize("fr", errorMessage);
import SimpleSelect from "@/components/inputs/selects/SimpleSelect.vue";

export default {
  components:{
    SimpleSelect
  },
  props: {
    itemLocal: {
      type: Object,
      required: true,
    },
    showAll: {
      type: Boolean,
      default: true,
    },
  },
  computed: {
    isAdmin() {
      return this.$store.state.AppActiveUser.is_admin;
    },
  },
};
</script>
