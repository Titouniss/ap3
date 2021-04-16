<template>
    <v-select
        :ref="`${model}Select`"
        :label="label"
        :options="items"
        :value="value"
        :filterable="false"
        :multiple="multiple"
        :reduce="reduce"
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
                <span :key="item.id">{{ item[label] }}</span>
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
            default: () => ({})
        },
        input: {
            type: Function,
            default: value => {}
        },
        blur: {
            type: Function,
            default: () => {}
        },
        focus: {
            type: Function,
            default: () => {}
        },
        reduce: {
            type: Function,
            default: item => item
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
            items: this.$store.getters[`${this.model}Management/getItems`],

            fetchTimeout: null
        };
    },
    methods: {
        clearFetchTimeout() {
            if (this.fetchTimeout) {
                clearTimeout(this.fetchTimeout);
            }
        },
        onInput(value) {
            this.$emit("input", value);
            this.input(value);
        },
        onBlur() {
            this.observer.disconnect();
            setTimeout(this.clearFetchTimeout, 0);
            this.blur();
        },
        async onFocus() {
            if (this.hasNextPage) {
                await this.$nextTick();
                this.observer.observe(this.$refs.load);
            }
            this.focus();
        },
        onSearch(query) {
            this.searchQuery = query;
            this.page = 1;
            this.clearFetchTimeout();
            this.fetchTimeout = setTimeout(this.fetchItems, 500);
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
                    fields: this.label,
                    loads: "",
                    appends: "",
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
                        const ids = this.items.map(item => item.id);
                        const unique = [];
                        data.payload.forEach(item => {
                            if (ids.indexOf(item.id) === -1) {
                                ids.push(item.id);
                                unique.push(item);
                            }
                        });
                        this.items.push(...unique);
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
