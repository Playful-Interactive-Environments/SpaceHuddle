<template>
  <el-dialog v-model="showDialog" :before-close="handleClose">
    <template #title>
      <span class="el-dialog__title">{{
        $t('moderator.organism.session.create.header')
      }}</span>
      <br />
      <br />
      <p>
        {{ $t('moderator.organism.session.create.info') }}
      </p>
    </template>

    <form class="session-create__form">
      <label for="title" class="heading heading--xs">{{
        $t('moderator.organism.session.create.title')
      }}</label>
      <input
        id="title"
        v-model="title"
        class="input input--fullwidth"
        :placeholder="$t('moderator.organism.session.create.titleExample')"
        @blur="context.$v.title.$touch()"
      />
      <FormError
        v-if="context.$v.title.$error"
        :errors="context.$v.title.$errors"
        :isSmall="true"
      />

      <label for="description" class="heading heading--xs">{{
        $t('moderator.organism.session.create.description')
      }}</label>
      <textarea
        id="description"
        v-model="description"
        class="textarea textarea--fullwidth"
        rows="3"
        :placeholder="
          $t('moderator.organism.session.create.descriptionExample')
        "
        @blur="context.$v.description.$touch"
      />
      <FormError
        v-if="context.$v.description.$error"
        :errors="context.$v.description.$errors"
        :isSmall="true"
      />

      <label for="expirationDate" class="heading heading--xs">{{
          $t('moderator.organism.session.create.expirationDate')
        }}</label>
      <el-date-picker
        id="expirationDate"
        v-model="expirationDate"
        type="date"
        :placeholder="$t('moderator.organism.session.create.expirationDatePlaceholder')"
      >
      </el-date-picker>
      <FormError
        v-if="context.$v.expirationDate.$error"
        :errors="context.$v.expirationDate.$errors"
        :isSmall="true"
      />

      <h3 class="session-create__topic heading heading--small">
        {{ $t('moderator.organism.session.create.topics') }}
      </h3>
      <p>
        {{ $t('moderator.organism.session.create.topicsInfo') }}
      </p>
      <label for="topic" class="heading heading--xs">{{
        $t('moderator.organism.session.create.firstTopic')
      }}</label>
      <input
        id="topic"
        v-model="topic"
        class="input input--fullwidth"
        :placeholder="$t('moderator.organism.session.create.topicExample')"
        @blur="context.$v.topic.$touch()"
      />
      <FormError
        v-if="context.$v.topic.$error"
        :errors="context.$v.topic.$errors"
        :isSmall="true"
      />
    </form>

    <template #footer>
      <button
        type="submit"
        class="btn btn--gradient btn--fullwidth"
        @click.prevent="saveSession"
      >
        {{ $t('moderator.organism.session.create.submit') }}
      </button>
    </template>
  </el-dialog>
</template>

<script lang="ts">
import { Options, setup, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import useVuelidate from '@vuelidate/core';
import { maxLength, required } from '@vuelidate/validators';

import FormError from '@/components/shared/atoms/FormError.vue';

import * as sessionService from '@/services/session-service';
import * as topicService from '@/services/topic-service';
import {
  getErrorMessage,
  addError,
  clearErrors,
} from '@/services/exception-service';

@Options({
  components: {
    FormError,
  },
  validations: {
    title: {
      required,
      max: maxLength(255),
    },
    description: {
      required,
      max: maxLength(1000),
    },
    expirationDate: {
      required,
    },
    topic: {
      required,
      max: maxLength(255),
    },
  },
})
export default class ModalSessionCreate extends Vue {
  @Prop({ default: false }) showModal!: boolean;

  title = '';
  description = '';
  topic = '';
  maxNrOfTopics = 5;
  today = new Date();
  expirationDate = new Date(this.today.setMonth(this.today.getMonth() + 1));
  errors: string[] = [];

  context = setup(() => {
    return {
      $v: useVuelidate(),
    };
  });

  showDialog = false;
  @Watch('showModal', { immediate: false, flush: 'post' })
  async onShowModalChanged(showModal: boolean): Promise<void> {
    this.showDialog = showModal;
  }

  handleClose(done: { (): void }): void {
    this.resetForm();
    this.context.$v.$reset();
    done();
    this.$emit('update:showModal', false);
  }

  resetForm(): void {
    this.title = '';
    this.description = '';
    this.topic = '';
    this.maxNrOfTopics = 5;
  }

  async saveSession(): Promise<void> {
    clearErrors(this.errors);
    await this.context.$v.$validate();
    if (this.context.$v.$error) return;

    sessionService
      .post({
        title: this.title,
        description: this.description,
        maxParticipants: 100,
        expirationDate: this.expirationDate.toISOString().slice(0, 10),
      })
      .then(
        (session) => {
          topicService
            .postTopic(session.id, {
              title: this.topic,
              description:
                'dummy data - this field should be removed in the backend!',
            })
            .then(
              () => {
                //this.$emit('update:showModal', false);
                this.$router.push({
                  name: 'moderator-session-details',
                  params: {
                    sessionId: session.id,
                  },
                });
              },
              (error) => {
                addError(this.errors, getErrorMessage(error));
              }
            );
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
