<template>
  <section></section>
</template>

<script lang="ts">
import { Options, setup, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as moduleService from '@/services/module-service';
import useVuelidate from '@vuelidate/core';
import FormError from '@/components/shared/atoms/FormError.vue';
import { Module } from '@/types/api/Module';

@Options({
  components: {
    FormError,
  },
  validations: {},
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorConfig extends Vue {
  @Prop() readonly moduleId!: string;
  @Prop() readonly taskId!: string;
  @Prop() readonly topicId!: string;
  @Prop({ default: {} }) modelValue!: any;
  module: Module | null = null;
  errors: string[] = [];

  context = setup(() => {
    return {
      $v: useVuelidate(),
    };
  });

  @Watch('moduleId', { immediate: true })
  async onModuleIdChanged(): Promise<void> {
    await this.getModule();
    if (this.module) {
      this.$emit('update:modelValue', this.module.parameter);
    }
  }

  async getModule(): Promise<void> {
    if (this.moduleId) {
      await moduleService.getModuleById(this.moduleId).then((module) => {
        this.module = module;
      });
    }
  }
}
</script>
