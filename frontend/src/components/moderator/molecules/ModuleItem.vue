<template>
    <article ref="item" class="module-item">
        <img
            :src="require(`@/assets/illustrations/planets/${type}.png`)"
            alt="planet"
            class="module-item__planet"
        />
        <ModuleInfo
            :type="type"
            :title="'Module Title'"
            :description="'Module description here ...'"
        />
        <Timer />
        <div class="module-item__toggles">
            <Toggle label="Active" v-if="!(type === ModuleType.SELECTION)" />
            <Toggle label="Public Screen" />
        </div>
        <div class="module-item__drag">
            <img
                src="@/assets/icons/drag-dots.svg"
                alt="draggable"
                class="module-item__dots-icon"
            />
        </div>
    </article>
</template>

<script lang="ts">
import { Prop } from 'vue-property-decorator';
import { Options, Vue } from 'vue-class-component';
import ModuleType from '@/types/ModuleType';
import ModuleColors from '@/types/ModuleColors';
import ModuleInfo from '@/components/shared/molecules/ModuleInfo.vue';
import Timer from '@/components/shared/atoms/Timer.vue';
import Toggle from '@/components/moderator/atoms/Toggle.vue';

@Options({
    components: {
        ModuleInfo,
        Timer,
        Toggle,
    },
})
export default class ModuleItem extends Vue {
    @Prop({ default: ModuleType.BRAINSTORMING }) type!: ModuleType;

    public ModuleType = ModuleType;

    mounted(): void {
        (this.$refs.item as HTMLElement).style.setProperty(
            '--module-color',
            ModuleColors[this.type]
        );
        // TODO: add Planet images
        (this.$refs.item as HTMLElement).style.setProperty(
            '--module-planet',
            `/assets/illustrations/${this.type}.png`
        );
    }
}
</script>

<style lang="scss" scoped>
.module-item {
    position: relative;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: white;
    border-radius: var(--border-radius);
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
    padding: 1.5rem 5rem 1.5rem 4.5rem;
    margin-left: 3rem;

    &__planet {
        position: absolute;
        left: -4rem;
        top: 50%;
        transform: translateY(-50%);
        width: 8rem;
    }

    &__toggles {
        display: flex;
        flex-direction: column;
        margin-left: 3rem;
    }

    &__drag {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        background-color: var(--color-mint);
        background-color: var(--module-color);
        border-radius: 0 var(--border-radius) var(--border-radius) 0;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 35px;
        align-self: stretch;
    }

    &__dots-icon {
        width: 12px;
        height: auto;
    }
}
</style>
