<template>
    <v-select
        :name="name"
        :ref="`${model}Select`"
        :label="label"
        :options="items"
        :value="value"
        :filterable="false"
        :multiple="multiple"
        :reduce="item => (reduce ? reduce(item) : item)"
        @input="onInput"
        @search:blur="onBlur"
        @search:focus="onFocus"
        @search="onSearch"
        class="w-full"
        autocomplete
    >
        <template #header>
            <slot name="header"></slot>
        </template>
        <template #option="item">
            <slot name="option" :data="item">
                <span>{{ item[label] }}</span>
            </slot>
        </template>
        <template #list-footer>
            <li ref="load" class="loader" v-show="hasNextPage"></li>
        </template>
    </v-select>
</template>

<script>
import vSelect from "vue-select";

export default {
    components: {
        vSelect
    },
    props: {
        name: {
            type: String,
            required: true
        },
        model: {
            type: String,
            required: true
        },
        label: {
            type: String,
            required: true
        },
        value: {
            default: null
        },
        filters: {
            type: Object,
            default: {}
        },
        input: {
            type: Function,
            default: null
        },
        blur: {
            type: Function,
            default: null
        },
        focus: {
            type: Function,
            default: null
        },
        reduce: {
            type: Function,
            default: null
        },
        multiple: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            observer: new IntersectionObserver(this.infiniteScroll),
            searchQuery: "",
            page: 1,
            perPage: 30,
            hasNextPage: false,
            items: this.$store.getters[`${this.model}Management/getItems`]
        };
    },
    methods: {
        onInput(value) {
            this.$emit("input", value);
            if (this.input) {
                this.input();
            }
        },
        onBlur() {
            this.observer.disconnect();
            if (this.blur) {
                this.blur();
            }
        },
        async onFocus() {
            if (this.hasNextPage) {
                await this.$nextTick();
                this.observer.observe(this.$refs.load);
            }
            if (this.focus) {
                this.focus();
            }
        },
        onSearch(query) {
            this.searchQuery = query;
            this.page = 1;
            this.fetchItems();
        },
        async infiniteScroll([{ isIntersecting, target }]) {
            if (isIntersecting) {
                const ul = target.offsetParent;
                const scrollTop = target.offsetParent.scrollTop;
                this.page++;

                await this.fetchItems();

                await this.$nextTick();
                ul.scrollTop = scrollTop;
            }
        },
        async fetchItems() {
            await this.$store
                .dispatch(`${this.model}Management/fetchItems`, {
                    label_only: 1,
                    order_by: this.label,
                    page: this.page,
                    per_page: this.perPage,
                    q: this.searchQuery || undefined,
                    ...this.filters
                })
                .then(data => {
                    if (data.pagination) {
                        const { last_page } = data.pagination;
                        this.hasNextPage = this.page !== last_page;
                    }
                    if (this.page === 1) {
                        this.items = data.payload;
                    } else {
                        this.items.push(...data.payload);
                    }
                })
                .catch(err => {
                    console.error(err);
                });
        }
    },
    created() {
        this.fetchItems();
    }
};
</script>

<style>
.loader {
    height: 40px;
}
</style>
