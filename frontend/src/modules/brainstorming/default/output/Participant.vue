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
    <!--<div class="brainstorming--bottom">
      <button class="btn btn--mint btn--fullwidth" @click.prevent="submitIdea">
        {{ $t('module.brainstorming.default.participant.submit') }}
      </button>
      <button class="btn btn--icon btn--fullwidth" type="button">
        <font-awesome-icon icon="rocket" />
        <span>{{
          $t('module.brainstorming.default.participant.startGame')
        }}</span>
      </button>
    </div>-->
    <nav class="level is-mobile columns is-gapless">
      <div class="level-left column">
        <input
          id="link"
          v-if="showLinkInput"
          v-model="imageWebLink"
          class="input input--fullwidth"
          :placeholder="$t('module.brainstorming.default.participant.linkInfo')"
        />
      </div>
      <div class="level-right column" role="button">
        <div class="level-item" v-on:click="showLinkInput = !showLinkInput">
          <font-awesome-icon icon="share-alt" />
        </div>
        <div
          class="level-item"
          v-on:click="showUploadDialog = !showUploadDialog"
        >
          <font-awesome-icon icon="paperclip" />
        </div>
        <div class="level-item" v-on:click="submitIdea">
          <font-awesome-icon icon="paper-plane" />
        </div>
      </div>
    </nav>
    <my-upload
      id="upload"
      @crop-success="cropSuccess"
      v-model="showUploadDialog"
      :width="200"
      :height="200"
      img-format="png"
      langType="en"
      :no-square="true"
      :no-circle="true"
      :no-rotate="false"
      :with-credentials="true"
    ></my-upload>
    <img :src="imgDataUrl" />
  </ParticipantModuleDefaultContainer>
</template>

<script lang="ts">
import { Options, setup, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import ParticipantModuleDefaultContainer from '@/components/participant/organisms/ParticipantModuleDefaultContainer.vue';
import myUpload from 'vue-image-crop-upload/upload-3.vue';

import useVuelidate from '@vuelidate/core';
import { maxLength, required } from '@vuelidate/validators';
import FormError from '@/components/shared/atoms/FormError.vue';
import * as moduleService from '@/services/module-service';
import * as ideaService from '@/services/idea-service';
import { EventType } from '@/types/enum/EventType';
import SnackbarType from '@/types/enum/SnackbarType';
import {
  addError,
  clearErrors,
  getErrorMessage,
} from '@/services/exception-service';
import { Module } from '@/types/api/Module';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';

@Options({
  components: {
    ParticipantModuleDefaultContainer,
    FormError,
    'my-upload': myUpload,
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
export default class Participant extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly moduleId!: string;
  module: Module | null = null;

  imgDataUrl = ''; // the datebase64 url of created image
  showUploadDialog = false;
  showLinkInput = false;

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
  imageWebLink = '';
  readonly keywordsEmptyMsg = 'error.vuelidate.keywordsRequired';
  scalePlanet = false;

  @Watch('moduleId', { immediate: true })
  onModuleIdChanged(): void {
    this.getModule();
  }

  async getModule(): Promise<void> {
    if (this.moduleId) {
      await moduleService
        .getModuleById(this.moduleId, EndpointAuthorisationType.PARTICIPANT)
        .then((module) => {
          this.module = module;
        });
    }
  }

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
        image: this.imgDataUrl,
        link: this.showLinkInput ? this.imageWebLink : '',
      })
      .then(
        (queryResult) => {
          this.description = '';
          this.keywords = '';
          this.imageWebLink = '';
          this.imgDataUrl = '';
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

  cropSuccess(imgDataUrl: string): void {
    this.imgDataUrl = imgDataUrl;
  }
}
</script>

<style lang="scss" scoped>
.level-item {
  margin: 1.3rem auto;
  //margin-top: 0.5rem;
  //margin-bottom: 1rem;
}

.level.is-mobile .level-item:not(:last-child) {
  margin-bottom: auto;
}
</style>
