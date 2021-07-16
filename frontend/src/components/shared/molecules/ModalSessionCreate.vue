<template>
  <ModalBase
    v-model:show-modal="showModal"
    @update:showModal="$emit('update:showModal', $event)"
  >
    <div class="session-create">
      <h2 class="heading heading--regular">Create Session</h2>
      <p>
        The session is the place that bundles all information belonging to your
        brainstorming adventure.
      </p>
      <form class="session-create__form">
        <label for="title" class="heading heading--xs">Title</label>
        <input
          id="title"
          v-model="title"
          class="input input--fullwidth"
          placeholder="e.g. Marketing Meeting"
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
        <h3 class="session-create__topic heading heading--small">Topics</h3>
        <p>
          Topics help to make a clear distinction between subjects that should
          have separate brainstorming modules. Tip: Keep it simple - one or two
          topics are usually enough!
        </p>
        <label for="topic" class="heading heading--xs">Topic 1</label>
        <input
          id="topic"
          v-model="topic"
          class="input input--fullwidth"
          placeholder="e.g. New app Name"
          @blur="context.$v.topic.$touch()"
        />
        <FormError
          v-if="context.$v.topic.$error"
          :errors="context.$v.topic.$errors"
          :isSmall="true"
        />
        <button
          type="submit"
          class="btn btn--gradient btn--fullwidth"
          @click.prevent="saveSession"
        >
          Create session
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
    ModalBase,
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
  errors: string[] = [];

  context = setup(() => {
    return {
      $v: useVuelidate(),
    };
  });

  async saveSession(): Promise<void> {
    clearErrors(this.errors);
    await this.context.$v.$validate();
    if (this.context.$v.$error) return;

    sessionService
      .post({
        title: this.title,
        description: this.description,
        maxParticipants: 100,
        expirationDate: '2021-09-12',
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
