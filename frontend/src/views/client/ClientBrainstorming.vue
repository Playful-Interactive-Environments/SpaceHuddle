<template>
  <div class="brainstorming container--fullheight container">
    <MenuBar />
    <div class="grid-container">
      <div class="grid-item">
        <div class="brainstorming__planetDiv">
          <transition name="fade">
            <img
              v-if="index === 0"
              :src="planets[0]"
              alt="planet"
              class="brainstorming__planet"
            />
          </transition>
          <transition name="fade">
            <img
              v-if="index === 1"
              :src="planets[1]"
              alt="planet"
              class="brainstorming__planet"
            />
          </transition>
          <transition name="fade">
            <img
              v-if="index === 2"
              :src="planets[2]"
              alt="planet"
              class="brainstorming__planet"
            />
          </transition>
          <transition name="fade">
            <img
              v-if="index === 3"
              :src="planets[3]"
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
    <HalfCard :type="topic.type">
      <slot>
        <h1 class="heading--regular">Think of a name for our app</h1>
        <p>
          Lorem ipsum dolor sit amet, consectetur et verotateum adipiscing elit.
        </p>
        <textarea
          v-if="showSecondInput"
          v-model="keywords"
          class="textarea textarea--fullwidth"
          placeholder="Your idea is very long, provide some keywords"
        ></textarea>
        <textarea
          class="textarea textarea--fullwidth"
          placeholder="Type your idea here ..."
          ref="ideaTextfield"
          v-model="description"
          @input="checkCharacterCount($event.target.value)"
        ></textarea>
        <div class="brainstorming--bottom">
          <button class="btn btn--mint btn--fullwidth" @click="submitIdea">
            Submit idea
          </button>
          <button class="btn btn--icon btn--fullwidth">
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
import Card from '@/components/shared/atoms/Card.vue';
import ModuleType from '@/types/ModuleType';
import useVuelidate from '@vuelidate/core';
import * as taskService from '@/services/moderator/task-service';
import { Prop } from 'vue-property-decorator';
import { maxLength, required } from '@vuelidate/validators';

@Options({
  components: {
    MenuBar,
    Timer,
    HalfCard,
    Card,
  },
})
export default class ClientBrainstorming extends Vue {
  @Prop({ required: true }) taskId!: string;
  public topic = { type: ModuleType.BRAINSTORMING };
  public showSecondInput = false;
  index = 0;
  public planets = [
    require(`@/assets/illustrations/planets/brainstorming01.png`),
    require(`@/assets/illustrations/planets/brainstorming02.png`),
    require(`@/assets/illustrations/planets/brainstorming03.png`),
    require(`@/assets/illustrations/planets/brainstorming04.png`),
  ];
  public currentPlanet = this.planets[this.index];
  public myVar = null;
  description = '';
  keywords = '';

  context = setup(() => {
    return {
      $v: useVuelidate(),
    };
  });

  async createIdea(): Promise<void> {
    console.log('createIdea');
    console.log(
      'DESCRIPTION: ',
      this.description,
      ' & KEYWORDS: ',
      this.keywords
    );
    await this.context.$v.$validate();
    if (this.context.$v.$error) return;

    await taskService.postIdea(this.taskId, {
      description: this.description,
      keywords: this.keywords,
      image: '',
      link: '',
    });
    this.$emit('update:showModal', false);
    this.$emit('moduleCreated');
  }

  checkCharacterCount(value: string): void {
    if (value.length > 60) {
      this.showSecondInput = true;
    }
    console.log('text entered: ', value.length, ' - ', this.showSecondInput);
  }

  submitIdea(): void {
    console.log('submit');
    console.log(this.index);
    this.createIdea();
    setTimeout(this.setNewPlanet, 2000);
  }
  setNewPlanet(): void {
    console.log('new planet');
    if (this.index < this.planets.length - 1) {
      this.index++;
    } else {
      this.index = 0;
    }
  }
}
</script>

<style lang="scss" scoped>
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
