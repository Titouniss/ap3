<template>
  <vs-prompt
    title="Edition"
    accept-text="Modifier"
    cancel-text="Annuler"
    button-cancel="border"
    @cancel="init"
    @accept="submitTodo"
    @close="init"
    :is-valid="validateForm"
    :active.sync="activePrompt"
  >
    <div>
      <form>
        <div class="vx-row">
          <div class="vx-col w-full">
            <vs-input
              v-validate="'required|alpha_dash|min:3'"
              data-vv-validate-on="blur"
              label-placeholder="Titre"
              name="title"
              placeholder="Titre"
              class="w-full mt-8"
            />
          </div>
        </div>
      </form>
    </div>
  </vs-prompt>
</template>

<script>
// Store Module
//import moduleRoleManagement from "@/store/role-management/moduleRoleManagement.js";

export default {
  props: {
    itemProp: Object
  },
  data() {
    return {
      itemLocal: {
        id: currentEvent.id,
        title: currentEvent.title,
        start: currentEvent.start,
        end: currentEvent.end,
        label: currentEvent.title
      }
    };
  },
  computed: {
    activePrompt: {
      get() {
        return this.itemProp.length > 0 ? true : false;
      },
      set(value) {}
    },
    roles() {
      return;
    },
    validateForm() {
      return;
    }
  },
  methods: {
    init() {},
    submitTodo() {}
  },
  created() {
    console.log(["props", this.props]);
  }
};
</script>
