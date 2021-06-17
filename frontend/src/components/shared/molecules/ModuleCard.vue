<template>
  <article
    ref="item"
    class="module-card"
    :class="{ 'module-card--client': isClient }"
  >
    <img
      :src="require(`@/assets/illustrations/planets/${type}.png`)"
      alt="planet"
      class="module-card__planet"
    />
    <ModuleInfo
      :type="type"
      :title="'Module Title'"
      :description="'Module description here ...'"
    />
    <Timer class="module-card__timer" />
    <div class="module-card__toggles" v-if="!isClient">
      <Toggle label="Active" v-if="!(type === ModuleType.SELECTION)" />
      <Toggle label="Public Screen" />
    </div>
    <div class="module-card__drag" v-if="!isClient">
      <img
        src="@/assets/icons/drag-dots.svg"
        alt="draggable"
        class="module-card__dots-icon"
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
export default class ModuleCard extends Vue {
  @Prop({ default: ModuleType.BRAINSTORMING }) type!: ModuleType;
  @Prop({ default: false }) isClient!: boolean;

  public ModuleType = ModuleType;

  mounted(): void {
    this.setModuleStyles();
  }

  updated(): void {
    this.setModuleStyles();
  }

  private setModuleStyles(): void {
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
.module-card {
  position: relative;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background-color: white;
  border-radius: var(--border-radius);
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
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
    cursor: pointer;
  }

  &__dots-icon {
    width: 12px;
    height: auto;
  }

  &--client {
    flex-direction: column;
    margin-left: 0;
    padding: 1.5rem 1.5rem 0;
    text-align: center;
    color: var(--color-darkblue);
    width: 65vw;
    margin-bottom: 1.5rem;

    .module-card__planet {
      position: static;
      transform: none;
      margin-bottom: 0.5rem;
    }

    .module-card__timer {
      margin-top: 0.5rem;
      transform: translateY(50%);
    }
  }
}
</style>
