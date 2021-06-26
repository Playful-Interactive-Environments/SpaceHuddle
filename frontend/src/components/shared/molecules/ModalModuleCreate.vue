<template>
  <ModalBase
    v-model:show-modal="showModal"
    @update:showModal="$emit('update:showModal', $event)"
  >
    <div class="session-create">
      <h2 class="heading heading--regular">Add a Module</h2>
      <p>
        Modules are the place where all the interaction happens. You can choose
        from 5 different types of modules.
      </p>
      <form class="session-create__form">
        <label for="moduleType" class="heading heading--xs">Module type</label>
        <select
          v-model="moduleType"
          id="moduleType"
          class="select select--fullwidth"
        >
          <option v-for="type in ModuleTypeKeys" :key="type" :value="type">
            {{ ModuleType[type] }}
          </option>
        </select>
        <FormError
          v-if="context.$v.moduleType.$error"
          :errors="context.$v.moduleType.$errors"
          :isSmall="true"
        />
        <label for="title" class="heading heading--xs">{{
          moduleType === 'BRAINSTORMING' ? 'Question' : 'Title'
        }}</label>
        <input
          id="title"
          v-model="title"
          class="input input--fullwidth"
          placeholder="e.g. What should be the name of our new app?"
          @blur="context.$v.title.$touch()"
        />
        <FormError
          v-if="context.$v.title.$error"
          :errors="context.$v.title.$errors"
          :isSmall="true"
        />

        <label for="description" class="heading heading--xs">Description</label>
        <textarea
          id="description"
          v-model="description"
          class="textarea textarea--fullwidth"
          rows="3"
          placeholder="e.g. The purpose of the meeting is to come up with new ideas our new app."
          @blur="context.$v.description.$touch"
        />
        <FormError
          v-if="context.$v.description.$error"
          :errors="context.$v.description.$errors"
          :isSmall="true"
        />
        <button
          type="submit"
          class="btn btn--gradient btn--fullwidth"
          @click.prevent="createModule"
        >
          Create module
        </button>
      </form>
    </div>
  </ModalBase>
</template>

<script lang="ts">
import { Options, setup, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import useVuelidate from '@vuelidate/core';
import { maxLength, required } from '@vuelidate/validators';

import FormError from '@/components/shared/atoms/FormError.vue';
import ModalBase from '@/components/shared/molecules/ModalBase.vue';

import * as topicService from '@/services/moderator/topic-service';
import ModuleType from '@/types/ModuleType';

@Options({
  components: {
    FormError,
    ModalBase,
  },
  validations: {
    moduleType: {
      required,
    },
    title: {
      required,
      max: maxLength(255),
    },
    description: {
      required,
      max: maxLength(1000),
    },
  },
})
export default class ModalModuleCreate extends Vue {
  @Prop({ default: false }) showModal!: boolean;
  @Prop({ required: true }) topicId!: string;

  moduleType = this.ModuleTypeKeys[1];
  title = '';
  description = '';

  ModuleType = ModuleType;

  context = setup(() => {
    return {
      $v: useVuelidate(),
    };
  });

  get ModuleTypeKeys(): Array<keyof typeof ModuleType> {
    return Object.keys(ModuleType) as Array<keyof typeof ModuleType>;
  }

  async createModule(): Promise<void> {
    await this.context.$v.$validate();
    if (this.context.$v.$error) return;

    await topicService.postTask(this.topicId, {
      taskType: this.moduleType,
      name: this.title,
      description: this.description,
      parameter: {},
      order: 0,
    });
    this.$emit('update:showModal', false);
    this.$emit('moduleCreated');
  }
}
</script>

<style lang="scss" scoped>
.session-create {
  display: flex;
  flex-direction: column;
  padding: 0 1rem;
  line-height: 1.2;

  &__form {
    display: flex;
    flex-direction: column;

    input,
    textarea {
      margin: 0 0 0.2rem;
    }
  }

  &__topic {
    margin-top: 1rem;
  }
}
</style>
