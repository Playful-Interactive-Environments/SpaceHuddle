<template>
    <div class="expand">
        <div
            role="button"
            class="expand__title"
            @click="isExpanded = !isExpanded"
        >
            <h2><slot name="title"></slot></h2>
            <img
                src="@/assets/icons/arrow.svg"
                alt="dropdown-arrow"
                class="expand__arrow"
                :class="{ expanded: isExpanded }"
            />
        </div>
        <transition
            name="expand"
            v-on:before-enter="beforeEnter"
            v-on:enter="enter"
            v-on:before-leave="beforeLeave"
            v-on:leave="leave"
        >
            <ul class="expand__content" v-show="isExpanded">
                <slot name="content"></slot>
            </ul>
        </transition>
    </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';

@Options({
    components: {},
})
export default class Timer extends Vue {
    public isExpanded = true;

    beforeEnter(el: HTMLElement): void {
        el.style.height = '0';
        el.style.opacity = '0';
    }

    enter(el: HTMLElement): void {
        el.style.height = el.scrollHeight + 'px';
        el.style.opacity = '1';
    }

    beforeLeave(el: HTMLElement): void {
        el.style.height = el.scrollHeight + 'px';
        el.style.opacity = '1';
    }

    leave(el: HTMLElement): void {
        el.style.height = '0';
        el.style.opacity = '0';
    }
}
</script>

<style lang="scss" scoped>
.expand {
    &__title {
        display: flex;
        justify-content: space-between;
    }

    &__arrow {
        width: 1.2rem;
        margin-right: 0.2rem;
        transition: transform 0.4s;
        transform-origin: center;

        &.expanded {
            transform: rotate(180deg);
        }
    }

    &__content {
        transform-origin: top center;
        transition: height 0.25s ease-out, opacity 0.1s;
    }
}
</style>
