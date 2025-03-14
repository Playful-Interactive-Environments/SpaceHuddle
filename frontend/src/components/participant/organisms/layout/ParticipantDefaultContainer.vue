<template>
  <div class="participant-background" :lang="$i18n.locale">
    <el-container
      class="participant-container full-height"
      :class="backgroundClass"
    >
      <el-header
        v-if="drawNavigation"
        class="participant-header"
        :class="{
          'participant-header__background': !useFullSize && !backgroundClass,
          'participant-header__fixedHead': useFullSize,
        }"
      >
        <slot name="header"></slot>
      </el-header>
      <el-main>
        <slot />
      </el-main>
    </el-container>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';

@Options({
  components: {},
})
export default class ParticipantDefaultContainer extends Vue {
  @Prop({ default: false }) readonly useFullSize!: boolean;
  @Prop({ default: '' }) readonly backgroundClass!: string;
  @Prop({ default: true }) readonly drawNavigation!: boolean;

  mounted(): void {
    this.scrollToTop(50);
  }

  scrollToTop(delay = 100): void {
    setTimeout(() => {
      window.scroll(0, 0);
    }, delay);
  }
}
</script>

<style lang="scss" scoped>
.el-main {
  margin-top: calc(-1 * var(--corner-radius));
  display: flex;
  max-width: inherit;
  overflow: inherit;
}

.participant-container {
  border-right: var(--color-primary) 1px solid;
  border-left: var(--color-primary) 1px solid;
}

@media screen and (max-width: 949px) and (orientation: landscape) {
  .participant-background {
    transform: rotate(-90deg);
    transform-origin: left top;
    width: 100vh;
    height: 100vw;
    overflow-x: hidden;
    position: absolute;
    top: 100%;
    left: 0;
  }
}

.participant-header {
  pointer-events: none;
  z-index: 100;
  position: sticky;
  top: 0;
  color: #fff;
  padding: 1rem;
  &__background {
    background: var(--color-dark-contrast);
    background-image: var(--participant-background-image);
    //background-size: 50%;
    //background-size: cover;
    //background-position: top;
    //background-repeat: no-repeat;
    mask-image: radial-gradient(
        circle farthest-corner at 100% 100%,
        transparent 69%,
        white 70%
      ),
      radial-gradient(
        circle farthest-corner at 0% 100%,
        transparent 69%,
        white 70%
      ),
      linear-gradient(white, white);
    mask-size: var(--corner-radius) var(--corner-radius),
      var(--corner-radius) var(--corner-radius),
      100% calc(100% - var(--corner-radius) + 1px);
    mask-position: bottom left, bottom right, top left;
    mask-repeat: no-repeat;
    padding: 1rem 1rem calc(1rem + var(--corner-radius)) 1rem;
  }

  &__fixedHead {
    position: fixed;
    width: 100%;
    max-width: inherit;
  }
}
</style>
