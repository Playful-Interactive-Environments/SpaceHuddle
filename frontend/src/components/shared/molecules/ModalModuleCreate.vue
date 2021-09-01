<template>
  <ModalBase
    v-model:show-modal="showModal"
    @update:showModal="$emit('update:showModal', $event)"
  >
    <div class="session-create">
      <h2 class="heading heading--regular">{{ $t("moderator.organism.module.create.header") }}</h2>
      <p>
        {{ $t("moderator.organism.module.create.info") }}
      </p>
      <form class="session-create__form">
        <label for="taskType" class="heading heading--xs">{{ $t("moderator.organism.module.create.taskType") }}</label>
        <select
          v-model="taskType"
          id="taskType"
          class="select select--fullwidth"
        >
          <option v-for="type in TaskTypeKeys" :key="type" :value="type">
            {{ $t(`enum.taskType.${TaskType[type]}`) }}
          </option>
        </select>
        <FormError
          v-if="context.$v.taskType.$error"
          :errors="context.$v.taskType.$errors"
          :isSmall="true"
        />
        <label for="moduleType" class="heading heading--xs">{{ $t("moderator.organism.module.create.moduleType") }}</label>
        <el-select v-model="moduleType" id="moduleType" multiple>
          <el-option
            v-for="type in ModuleTypeKeys"
            :key="type"
            :value="type"
            :label="$t(`enum.moduleType.${TaskType[taskType]}.${type}`)"
          />
        </el-select>
        <FormError
          v-if="context.$v.taskType.$error"
          :errors="context.$v.taskType.$errors"
          :isSmall="true"
        />
        <label for="title" class="heading heading--xs">{{
          taskType === 'BRAINSTORMING'
            ? $t('moderator.organism.module.create.question')
            : $t('moderator.organism.module.create.title')
        }}</label>
        <input
          id="title"
          v-model="title"
          class="input input--fullwidth"
          :placeholder="$t('moderator.organism.module.create.questionExample')"
          @blur="context.$v.title.$touch()"
        />
        <FormError
          v-if="context.$v.title.$error"
          :errors="context.$v.title.$errors"
          :isSmall="true"
        />

        <label for="description" class="heading heading--xs">{{ $t("moderator.organism.module.create.description") }}</label>
        <textarea
          id="description"
          v-model="description"
          class="textarea textarea--fullwidth"
          rows="3"
          :placeholder="$t('moderator.organism.module.create.descriptionExample')"
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
          {{ $t("moderator.organism.module.create.submit") }}
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

import * as taskService from '@/services/task-service';
import TaskType from '@/types/enum/TaskType';
import {
  getErrorMessage,
  addError,
  clearErrors,
} from '@/services/exception-service';
import { getModulesForTaskType } from '@/modules/ModuleList';

@Options({
  components: {
    FormError,
    ModalBase,
  },
  validations: {
    taskType: {
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

  taskType = this.TaskTypeKeys[1];
  moduleType = this.ModuleTypeKeys;
  title = '';
  description = '';
  errors: string[] = [];

  TaskType = TaskType;

  context = setup(() => {
    return {
      $v: useVuelidate(),
    };
  });

  get TaskTypeKeys(): Array<keyof typeof TaskType> {
    return Object.keys(TaskType) as Array<keyof typeof TaskType>;
  }

  get ModuleTypeKeys(): string[] {
    const list = getModulesForTaskType(this.taskType);
    return list;
  }

  resetForm(): void {
    this.taskType = this.TaskTypeKeys[1];
    this.moduleType = this.ModuleTypeKeys;
    this.title = '';
    this.description = '';
  }

  async createModule(): Promise<void> {
    clearErrors(this.errors);
    await this.context.$v.$validate();
    if (this.context.$v.$error) return;

    taskService
      .postTask(this.topicId, {
        taskType: this.taskType,
        name: this.title,
        description: this.description,
        parameter: {},
        order: 10,
        modules: this.moduleType,
      })
      .then(
        () => {
          this.$emit('update:showModal', false);
          this.$emit('moduleCreated');
          this.resetForm();
          this.context.$v.$reset();
        },
        (error) => {
          addError(this.errors, getErrorMessage(error));
        }
      );
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
