<template>
  <div class="brainstorming container--fullheight container">
    <MenuBar />
    <div class="grid-container">
      <div class="grid-item">
        <img
          :src="require(`@/assets/illustrations/planets/brainstorming.png`)"
          alt="planet"
          class="brainstorming__planet"
        />
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
          class="textarea textarea--fullwidth"
          placeholder="Type your idea here ..."
        ></textarea>
        <div class="brainstorming--bottom">
          <button class="btn btn--mint btn--fullwidth">Submit idea</button>
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
import { Options, Vue } from 'vue-class-component';
import MenuBar from '@/components/client/molecules/Menubar.vue';
import Timer from '@/components/shared/atoms/Timer.vue';
import HalfCard from '@/components/shared/atoms/HalfCard.vue';
import Card from '@/components/shared/atoms/Card.vue';
import ModuleType from '@/types/ModuleType';

@Options({
  components: {
    MenuBar,
    Timer,
    HalfCard,
    Card,
  },
})
export default class ClientBrainstorming extends Vue {
  public topic = { type: ModuleType.BRAINSTORMING };
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

  &__planet {
    position: relative;
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
}
.container {
  padding-bottom: 0;
}
.grid-container {
  display: grid;
  grid-template-columns: 1fr 1fr;
}
</style>
