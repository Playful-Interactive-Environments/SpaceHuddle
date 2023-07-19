<template>
  <CustomConfig
    ref="customConfig"
    :rulePropPath="rulePropPath"
    :moduleId="moduleId"
    :taskId="taskId"
    :topicId="topicId"
    v-model="modelValue"
  />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import CustomConfig from '@/modules/information/findit/organisms/CustomConfig.vue';

@Options({
  components: { CustomConfig },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorConfig extends Vue {
  @Prop() readonly rulePropPath!: string;
  @Prop() readonly moduleId!: string;
  @Prop() readonly taskId!: string;
  @Prop() readonly topicId!: string;
  @Prop({ default: {} }) modelValue!: any;
}
</script>

<style lang="scss" scoped>
.pin {
  --pin-color: var(--color-primary);
  font-size: var(--font-size-xxxlarge);
  color: var(--pin-color);
}
</style>
