<template>
  <ModalBase
    v-model:show-modal="showModal"
    @update:showModal="$emit('update:showModal', $event)"
  >
    <div class="modal">
      <h2>Create Session</h2>
      <p>
        The session is the place that bundles all information belonging to your
        brainstorming adventure.
      </p>
      <form>
        <label for="title" class="label">Title</label>
        <input
          id="title"
          v-model="title"
          class="input input--fullwidth"
          placeholder="e.g. Marketing Meeting"
          @blur="context.$v.title.$touch()"
        />
        <form-error
          v-if="context.$v.title.$error"
          :errors="context.$v.title.$errors"
        />

        <label for="description" class="label">Description</label>
        <textarea
          id="description"
          v-model="description"
          class="textarea textarea--fullwidth"
          rows="3"
          placeholder="e.g. The purpose of the meeting is to come up with new ideas our new app."
          @blur="context.$v.description.$touch"
        />
        <form-error
          v-if="context.$v.description.$error"
          :errors="context.$v.description.$errors"
        />
        <h3>Topics</h3>
        <p>
          Topics help to make a clear distinction between subjects that should
          have separate brainstorming modules. Tip: Keep it simple - one or two
          topics are usually enough!
        </p>
        <label for="topic">Topic 1</label>
        <input
          id="topic"
          v-model="topic"
          class="input input--fullwidth"
          placeholder="e.g. New app Name"
          @blur="context.$v.topic.$touch()"
        />
        <form-error
          v-if="context.$v.topic.$error"
          :errors="context.$v.topic.$errors"
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

import AddItemSubtle from '@/components/moderator/atoms/AddItemSubtle.vue';
import FormError from '@/components/shared/atoms/FormError.vue';
import ModalBase from '@/components/shared/molecules/ModalBase.vue';

import * as sessionService from '@/services/moderator/session-service';

@Options({
  components: {
    AddItemSubtle,
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

  context = setup(() => {
    return {
      $v: useVuelidate(),
    };
  });

  async saveSession(): Promise<void> {
    await this.context.$v.$validate();
    if (this.context.$v.$error) return;

    const session = await sessionService.post({
      title: this.title,
      description: this.description,
      maxParticipants: 100,
      expirationDate: '2021-09-12',
    });

    await sessionService.postTopic(session.id, {
      title: this.topic,
      description: 'dummy data - this field should be removed in the backend!',
    });

    this.$emit('update:showModal', false);
    await this.$router.push({
      name: 'moderator-session-details',
      params: {
        sessionId: session.id,
      },
    });
  }
}
</script>

<style lang="scss" scoped>
.modal {
  display: flex;
  flex-direction: column;
  padding: 0 1rem;
}

form {
  display: flex;
  flex-direction: column;
}

.topic-group {
  display: flex;

  &__close {
    margin-left: 0.5rem;
  }
}
</style>
