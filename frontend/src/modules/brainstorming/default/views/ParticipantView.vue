<template>
  <ParticipantModuleDefaultContainer :task-id="taskId">
    <template v-slot:planet>
      <transition name="fade" v-for="(planet, index) in planets" :key="index">
        <img
          v-if="activePlanetIndex === index"
          :src="planets[index]"
          alt="planet"
          class="brainstorming__planet"
        />
      </transition>
    </template>
    <input
      id="keywords"
      v-if="showSecondInput"
      v-model="keywords"
      class="textarea textarea--fullwidth"
      :placeholder="$t('module.brainstorming.default.participant.keywordInfo')"
    />
    <FormError
      v-if="
        context.$v.keywords.$error ||
        (context.$v.keywords.$dirty && keywordsEmpty)
      "
      :errors="
        context.$v.keywords.$error
          ? context.$v.keywords.$errors
          : { keywordsEmptyMsg }
      "
      :isSmall="true"
    />
    <textarea
      id="description"
      class="textarea textarea--fullwidth"
      :placeholder="
        $t('module.brainstorming.default.participant.descriptionInfo')
      "
      ref="ideaTextfield"
      rows="4"
      contenteditable
      v-model="description"
      @blur="context.$v.description.$touch()"
    ></textarea>
    <FormError
      v-if="context.$v.description.$error"
      :errors="context.$v.description.$errors"
      :isSmall="true"
    />
    <form-error :errors="errors"></form-error>
    <div class="brainstorming--bottom">
      <button class="btn btn--mint btn--fullwidth" @click.prevent="submitIdea">
        {{ $t('module.brainstorming.default.participant.submit') }}
      </button>
      <button class="btn btn--icon btn--fullwidth" type="button">
        <!--<font-awesome-icon :icon="['fac', 'rocket']" />-->
        <font-awesome-icon icon="rocket" />
        <span>{{
          $t('module.brainstorming.default.participant.startGame')
        }}</span>
      </button>
    </div>
  </ParticipantModuleDefaultContainer>
</template>

<script lang="ts">
import { Options, setup, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import ParticipantModuleDefaultContainer from '@/components/participant/organisms/ParticipantModuleDefaultContainer.vue';

import ModuleInfo from '@/components/shared/molecules/ModuleInfo.vue';
import useVuelidate from '@vuelidate/core';
import { maxLength, required } from '@vuelidate/validators';
import FormError from '@/components/shared/atoms/FormError.vue';
import * as ideaService from '@/services/idea-service';
import { EventType } from '@/types/enum/EventType';
import SnackbarType from '@/types/enum/SnackbarType';
import {
  addError,
  clearErrors,
  getErrorMessage,
} from '@/services/exception-service';

@Options({
  components: {
    ParticipantModuleDefaultContainer,
    ModuleInfo,
    FormError,
  },
  validations: {
    keywords: {
      max: maxLength(36),
    },
    description: {
      required,
      max: maxLength(255),
    },
  },
})
export default class ParticipantView extends Vue {
  @Prop() readonly taskId!: string;

  activePlanetIndex = 0;
  planets = [
    require('@/assets/illustrations/planets/brainstorming01.png'),
    require('@/assets/illustrations/planets/brainstorming02.png'),
    require('@/assets/illustrations/planets/brainstorming03.png'),
    require('@/assets/illustrations/planets/brainstorming04.png'),
  ];
  description = '';
  errors: string[] = [];
  keywords = '';
  readonly keywordsEmptyMsg = 'error.vuelidate.keywordsRequired';
  scalePlanet = false;

  context = setup(() => {
    return {
      $v: useVuelidate(),
    };
  });

  get keywordsEmpty(): boolean {
    return this.showSecondInput && this.keywords.length <= 0;
  }

  async submitIdea(): Promise<void> {
    clearErrors(this.errors);
    await this.context.$v.$validate();
    if (this.context.$v.$error || this.keywordsEmpty) return;

    ideaService
      .postIdea(this.taskId, {
        description: this.keywords.length > 0 ? this.description : '',
        keywords: this.keywords.length > 0 ? this.keywords : this.description,
        image: '',
        link: '',
      })
      .then(
        (queryResult) => {
          this.description = '';
          this.keywords = '';
          this.$nextTick(() => {
            this.context.$v.$reset();
          });
          setTimeout(this.setNewPlanet, 500);
          this.eventBus.emit(EventType.SHOW_SNACKBAR, {
            type: SnackbarType.SUCCESS,
            message: `info.postIdea`,
            messageContent: [queryResult.keywords],
          });
        },
        (error) => {
          addError(this.errors, getErrorMessage(error));
        }
      );
  }

  get showSecondInput(): boolean {
    return this.description.length > 60;
  }

  setNewPlanet(): void {
    if (this.activePlanetIndex < this.planets.length - 1) {
      this.activePlanetIndex++;
    } else {
      this.scalePlanet = true;
      setTimeout(() => {
        this.scalePlanet = false;
      }, 1000);
    }
  }
}
</script>

<style scoped></style>
