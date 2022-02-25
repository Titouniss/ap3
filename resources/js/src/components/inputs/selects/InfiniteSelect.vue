<template>
    <vs-select
    v-on:keydown.enter="ignore_enter"
        class="w-full"
        :placeholder="placeholder"
        :ref="`${model}Select`"
        :value="value"
        :label="header"
        :multiple="multiple"
        :loading="loading"
        :autocomplete="autocomplete"
        :disabled="disabled"
        :success="required && !!value && (!multiple || value.length > 0)"
        :danger="
            required && hadValue && (!value || !multiple || value.length === 0)
        "
        :danger-text="`Le champ ${header.toLowerCase()} est obligatoire`"
        @input="onInput"
        @change="onChange"
        @blur="onBlur"
        @focus="onFocus"
        @input-change="onSearch"
    >
        <vs-select-item
            v-for="item in items"
            :key="item.id"
            :value="reduce(item)"
            :text="itemText(item)"
        />

        <li ref="load" class="loader" v-show="hasNextPage"></li>
    </vs-select>
</template>

<script>
import _ from "lodash";

export default {
    props: {
        header: {
            type: String,
            default: () => null
        },
        model: {
            type: String,
            required: true
        },
        label: {
            type: String,
            required: true
        },
        placeholder:{
            type: String,
            default: ()=> null
        },
        itemText: {
            type: Function,
            default(item) {
                return item[this.label];
            }
        },
        itemFields: {
            type: Array,
            default() {
                return [this.label];
            }
        },
        value: {
            type: Array | Number | String,
            default: () => null
        },
        filters: {
            type: Object,
            default: () => ({})
        },
        multiple: {
            type: Boolean,
            default: () => false
        },
        required: {
            type: Boolean,
            default: () => false
        },
        reduce: {
            type: Function,
            default(item) {
                return item.id;
            }
        },
        autocomplete: {
            type: Boolean,
            default: () => true
        },
        disabled: {
            type: Boolean,
            default: () => false
        }
    },
    data() {
        return {
            observer: new IntersectionObserver(this.infiniteScroll),
            searchQuery: "",
            page: 1,
            perPage: 30,
            hasNextPage: false,

            items: [],

            lastSearch: {},
            fetchTimeout: null,

            loading: false,
            hadValue: !!this.value
        };
    },
    watch: {
        filters: {
            handler(val) {
                this.fetchItems();
            },
            deep: true
        }
    },
    methods: {
        ignore_enter(){

        },
        clearFetchTimeout() {
            if (this.fetchTimeout) {
                clearTimeout(this.fetchTimeout);
            }
        },
        onInput(value) {
            if (value && (!this.multiple || value.length > 0)) {
                this.hadValue = true;
            }
            this.$emit("input", value);
        },
        onChange(value) {
            if (value && value.target) this.onInput(null);
        },
        onBlur() {
            this.observer.disconnect();
            setTimeout(this.clearFetchTimeout, 0);
            this.$emit("blur");
        },
        async onFocus() {
            await this.fetchItems();
            if (this.hasNextPage) {
                await this.$nextTick();
                this.observer.observe(this.$refs.load);
            }
            this.$emit("focus");
        },
        onSearch(event) {
            this.searchQuery = event.target.value;
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
        async fetchItems(force) {
            const search = {
                page: this.page,
                per_page: this.perPage,
                q: this.searchQuery || undefined,
                ...this.filters
            };
            if (!_.isEqual(this.lastSearch, search)) {
                this.lastSearch = JSON.parse(JSON.stringify(search));
                this.loading = true;
                await this.$store
                    .dispatch(`${this.model}Management/fetchItems`, {
                        fields: this.itemFields,
                        loads: "",
                        appends: "",
                        order_by: this.label,
                        ...search
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
                                if (
                                    ids.indexOf(item.id) === -1 &&
                                    this.itemText(item) &&
                                    this.itemText(item).length > 0
                                ) {
                                    ids.push(item.id);
                                    unique.push(item);
                                }
                            });
                            this.items.push(...unique);
                        }
                    })
                    .catch(err => {
                        console.error(err);
                    })
                    .finally(() => {
                        this.loading = false;
                        this.$refs[`${this.model}Select`].filterItems(
                            this.searchQuery
                        );
                        this.$refs[`${this.model}Select`].$children.map(
                            item => {
                                item.valueInputx = this.searchQuery;
                            }
                        );
                    });
            }
        },
        async fetchSelectedItems() {
            if (this.value) {
                for (const val of this.multiple ? this.value : [this.value]) {
                    try {
                        const id = Number.isInteger(val) ? val : val.id;
                        if (!this.items.find(item => item.id === id)) {
                            const { payload } = await this.$store.dispatch(
                                `${this.model}Management/fetchItem`,
                                id
                            );

                            this.items.unshift(payload);
                        }
                    } catch (err) {
                        console.error(err);
                    }
                }
                this.hadValue = true;
            }
        }
    },
    async created() {
        await this.fetchItems();
        await this.fetchSelectedItems();
    }
};
</script>

<style>
.loader {
    height: 40px;
}
</style>
