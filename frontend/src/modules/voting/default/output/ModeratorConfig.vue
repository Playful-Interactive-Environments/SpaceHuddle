<template>
  <section>
    <label for="slotCount" class="heading heading--xs">{{
      $t('module.voting.default.moderatorConfig.slotCount')
    }}</label>
    <input
      id="slotCount"
      v-model="slotCount"
      type="number"
      class="input input--fullwidth"
      :placeholder="
        $t('module.voting.default.moderatorConfig.slotCountExample')
      "
      @blur="context.$v.slotCount.$touch()"
    />
    <FormError
      v-if="context.$v.slotCount.$error"
      :errors="context.$v.slotCount.$errors"
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
import { CustomParameter } from '@/types/ui/CustomParameter';
import { Module } from '@/types/api/Module';

@Options({
  components: {
    FormError,
  },
  validations: {
    slotCount: {
      required,
      numeric,
    },
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorConfig extends Vue implements CustomParameter {
  @Prop() readonly moduleId!: string;
  @Prop() readonly taskId!: string;
  @Prop() readonly topicId!: string;
  @Prop({ default: {} }) modelValue!: any;
  module: Module | null = null;
  slotCount = 3;
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
        this.slotCount = module.parameter.slotCount;
      });
    }
  }

  async save(moduleId: string | null): Promise<void> {
    if (!moduleId) moduleId = this.moduleId;
    if (moduleId) {
      await moduleService.putModule(moduleId, {
        order: 3,
        parameter: {
          slotCount: this.slotCount,
        },
      });
    }
  }
}
</script>
