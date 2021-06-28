<template>
  <div class="brainstorming container--fullheight container" ref="item">
    <MenuBar />
    <div class="grid-container">
      <div class="grid-item">
        <div
          class="brainstorming__planetDiv"
          :class="{ 'brainstorming__planetDiv--animation': scalePlanet }"
        >
          <transition
            name="fade"
            v-for="(planet, index) in planets"
            :key="index"
          >
            <img
              v-if="activepPlanetIndex === index"
              :src="planets[index]"
              alt="planet"
              class="brainstorming__planet"
            />
          </transition>
        </div>
      </div>
      <div class="grid-item brainstorming--center">
        <div class="brainstorming--uppercase">Time Left</div>
        <Timer class="brainstorming__timer"></Timer>
      </div>
    </div>
    <HalfCard :type="type">
      <slot>
        <ModuleInfo
          :type="type"
          title="Think of a Name"
          description="this is a fancy description"
          :is-client="true"
        />

        <input
          id="keywords"
          v-if="showSecondInput"
          v-model="keywords"
          class="textarea textarea--fullwidth"
          placeholder="Please provide some keywords"
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
          placeholder="Type your idea here ..."
          ref="ideaTextfield"
          rows="5"
          contenteditable
          v-model="description"
          @input="checkCharacterCount()"
          @blur="context.$v.description.$touch()"
        ></textarea>
        <FormError
          v-if="context.$v.description.$error"
          :errors="context.$v.description.$errors"
          :isSmall="true"
        />
        <div class="brainstorming--bottom">
          <button
            class="btn btn--mint btn--fullwidth"
            @click.prevent="submitIdea"
          >
            Submit idea
          </button>
          <button class="btn btn--icon btn--fullwidth" type="button">
            <img
              src="@/assets/icons/rocket.svg"
              alt="rocket-icon"
              width="20"
              height="auto"
            />
            <span>No ideas? Get inspired!</span>
          </button>
        </div>
      </slot>
    </HalfCard>
  </div>
</template>

<script lang="ts">
import { Options, setup, Vue } from 'vue-class-component';
import MenuBar from '@/components/client/molecules/Menubar.vue';
import Timer from '@/components/shared/atoms/Timer.vue';
import HalfCard from '@/components/shared/atoms/HalfCard.vue';
import ModuleInfo from '@/components/shared/molecules/ModuleInfo.vue';
import Card from '@/components/shared/atoms/Card.vue';
import ModuleType from '@/types/ModuleType';
import useVuelidate from '@vuelidate/core';
import { Prop } from 'vue-property-decorator';
import { setModuleStyles } from '@/utils/moduleStyles';
import { getTaskById } from '@/services/task-service';
import { maxLength, required } from '@vuelidate/validators';
import FormError from '@/components/shared/atoms/FormError.vue';
import * as taskService from '@/services/task-service';

@Options({
  components: {
    MenuBar,
    Timer,
    HalfCard,
    Card,
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
export default class ClientBrainstorming extends Vue {
  @Prop({ required: true }) taskId!: string;

  type = ModuleType.BRAINSTORMING;
  activepPlanetIndex = 0;
  planets = [
    require('../../assets/illustrations/planets/brainstorming01.png'),
    require('../../assets/illustrations/planets/brainstorming02.png'),
    require('../../assets/illustrations/planets/brainstorming03.png'),
    require('../../assets/illustrations/planets/brainstorming04.png'),
  ];
  currentPlanet = this.planets[this.activepPlanetIndex];
  myVar = null;
  description = '';
  keywords = '';
  readonly keywordsEmptyMsg =
    'Your idea is very long, please provide some keywords.';
  scalePlanet = false;

  context = setup(() => {
    return {
      $v: useVuelidate(),
    };
  });

  mounted(): void {
    setModuleStyles(this.$refs.item as HTMLElement, this.type);
  }

  updated(): void {
    setModuleStyles(this.$refs.item as HTMLElement, this.type);
  }

  get keywordsEmpty() {
    return this.showSecondInput && this.keywords.length <= 0;
  }

  async submitIdea(): Promise<void> {
    await this.context.$v.$validate();
    if (this.context.$v.$error || this.keywordsEmpty) return;

    await taskService.postIdea(this.taskId, {
      description: this.description,
      keywords: this.keywords,
      image: '',
      link: '',
    });

    this.description = '';
    this.keywords = '';
    this.$nextTick(() => {
      this.context.$v.$reset();
    });
    setTimeout(this.setNewPlanet, 500);
  }

  get showSecondInput(): boolean {
    return this.description.length > 60 ? true : false;
  }

  setNewPlanet(): void {
    if (this.activepPlanetIndex < this.planets.length - 1) {
      this.activepPlanetIndex++;
    } else {
      this.scalePlanet = true;
      setTimeout(() => {
        this.scalePlanet = false;
      }, 1000);
    }
  }
}
</script>

<style lang="scss" scoped>
@keyframes planetAnimation {
  0% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
  }
  100% {
    transform: scale(1);
  }
}

.brainstorming {
  color: #fff;
  background: var(--color-darkblue);
  background-image: url('../../assets/illustrations/background.png');
  background-attachment: fixed;
  background-repeat: no-repeat;
  background-size: cover;

  &__planetDiv {
    position: relative;
    left: 1rem;
    width: 11rem;
    height: 11rem;
    transition: transform 0.2;

    &--animation {
      animation-name: planetAnimation;
      animation-duration: 0.6s;
    }
  }

  &__planet {
    position: absolute;
    left: 1rem;
    width: 11rem;
  }

  &__timer {
    text-transform: uppercase;
    color: white;
    font-size: 1.8rem;
    padding: 0;
    background-color: transparent;
  }

  &--uppercase {
    text-transform: uppercase;
    color: white;
    font-size: 0.75rem;
    text-align: center;
  }

  &--center {
    margin: auto;
  }
  &--bottom {
    position: absolute;
    bottom: 0;
    width: 100%;
    padding: 1rem 2rem;
    margin-left: -50vw;
    left: 50%;
  }
  .fade-enter-active,
  .fade-leave-active {
    transition: opacity 0.3s;
  }
  .fade-enter, .fade-leave-to /* .fade-leave-active below version 2.1.8 */ {
    opacity: 0;
  }
}

.container {
  padding-bottom: 0;
}
.grid-container {
  display: grid;
  grid-template-columns: 1fr 1fr;
}
</style>
