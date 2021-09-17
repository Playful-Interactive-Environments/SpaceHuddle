<template>
  <section>
    <label for="slotCount" class="heading heading--xs">{{
      $t('module.voting.default.moderatorConfig.slotCount')
    }}</label>
    <input
      id="slotCount"
      v-model="modelValue.slotCount"
      type="number"
      class="input input--fullwidth"
      :placeholder="
        $t('module.voting.default.moderatorConfig.slotCountExample')
      "
      @blur="context.$v.modelValue.slotCount.$touch()"
    />
    <FormError
      v-if="context.$v.modelValue.slotCount.$error"
      :errors="context.$v.modelValue.slotCount.$errors"
      :isSmall="true"
    />
  </section>
</template>

<script lang="ts">
import { Options, setup, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as moduleService from '@/services/module-service';
import { required, numeric } from '@vuelidate/validators';
import useVuelidate from '@vuelidate/core';
import FormError from '@/components/shared/atoms/FormError.vue';
import { Module } from '@/types/api/Module';

@Options({
  components: {
    FormError,
  },
  validations: {
    modelValue: {
      slotCount: {
        required,
        numeric,
      },
    },
  },
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

  @Watch('modelValue', { immediate: true })
  async onModelValueChanged(): Promise<void> {
    if (this.modelValue && !this.modelValue.slotCount) {
      this.modelValue.slotCount = 3;
    }
  }

  @Watch('moduleId', { immediate: true })
  async onModuleIdChanged(): Promise<void> {
    await this.getModule();
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
