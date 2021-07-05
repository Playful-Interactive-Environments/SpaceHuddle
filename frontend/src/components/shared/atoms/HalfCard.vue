<template>
  <div class="half-card fullwidth">
    <div class="half-card__content container">
      <slot />
    </div>
  </div>
</template>

<script lang="ts">
import { Prop } from 'vue-property-decorator';
import { Options, Vue } from 'vue-class-component';
import ModuleType from '@/types/ModuleType';
import ModuleInfo from '@/components/shared/molecules/ModuleInfo.vue';

@Options({
  components: {
    ModuleInfo,
  },
})
export default class HalfCard extends Vue {
  @Prop({ default: ModuleType.CATEGORISATION }) type!: ModuleType;
}
</script>

<style lang="scss" scoped>
.half-card {
  position: relative;
  flex-grow: 1;
  justify-content: space-between;
  align-items: center;
  background-color: white;
  color: #1d2948;
  border-radius: 30px 30px 0 0;
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
  padding: 0.5rem 0 1.5rem 0;
  margin: 0;

  &__content {
    text-align: center;
  }
}

.fullwidth {
  width: 100vw;
  min-height: 100vw;
  position: relative;
  margin-left: -50vw;
  margin-top: 10px;
  left: 50%;
}
</style>
