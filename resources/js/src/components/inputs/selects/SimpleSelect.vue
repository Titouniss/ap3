<template>
    <vs-select
        class="w-full"
        :value="value"
        :label="header"
        :multiple="multiple"
        :autocomplete="true"
        :success="required && !!value"
        :danger="required && hadValue && !value"
        :danger-text="`Le champ ${header.toLowerCase()} est obligatoire`"
        @input="onInput"
        @blur="onBlur"
        @focus="onFocus"
        v-on:keydown.enter="ignore_enter"
    >
        <vs-select-item
            v-for="item in options"
            :key="reduce(item)"
            :value="reduce(item)"
            :text="itemText(item)"
        />
    </vs-select>
</template>

<script>
export default {
    props: {
        header: {
            type: String,
            default: () => null
        },
        label: {
            type: String,
            default: () => null
        },
        itemText: {
            type: Function,
            default(item) {
                return this.label ? item[this.label] : item;
            }
        },
        value: {
            default: () => null
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
                return item;
            }
        },
        options: {
            type: Array,
            required: true
        }
    },
    data() {
        return {
            hadValue: !!this.value
        };
    },
    methods: {
        ignore_enter(){
            
        },
        onInput(value) {
            if (value) {
                this.hadValue = true;
            }
            this.$emit("input", value);
        },
        onBlur() {
            this.$emit("blur");
        },
        onFocus() {
            this.$emit("focus");
        }
    }
};
</script>
