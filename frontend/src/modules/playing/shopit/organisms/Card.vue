<template>
  <ul class="cardStats">
    <li class="cardCost">
      {{ cost }}
      <hr />
    </li>
    <li>
      {{ CO2
      }}<span>{{ $t('module.playing.shopit.participant.cards.kg') }}</span>
    </li>
    <li>
      {{ energy
      }}<span>{{ $t('module.playing.shopit.participant.cards.kw') }}</span>
    </li>
    <li>
      {{ lifetime
      }}<span>{{ $t('module.playing.shopit.participant.cards.years') }}</span>
    </li>
    <li>
      {{ water
      }}<span>{{ $t('module.playing.shopit.participant.cards.kl') }}</span>
    </li>
    <li>
      {{ money
      }}<span>{{ $t('module.playing.shopit.participant.cards.price') }}</span>
    </li>
  </ul>
  <img :src="getCardSprite(cardName)" alt="{{ category }}" class="cardImage" />
  <font-awesome-icon
    :icon="gameConfig.categories[category].settings.icon"
    class="categoryCardIcon"
  />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import { ObjectSpace } from '@/types/enum/ObjectSpace';
import { until } from '@/utils/wait';
import * as tutorialService from '@/services/tutorial-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Tutorial } from '@/types/api/Tutorial';
import * as cashService from '@/services/cash-service';
import * as themeColors from '@/utils/themeColors';
import gameConfig from '@/modules/playing/shopit/data/gameConfig.json';
import { Idea } from '@/types/api/Idea';
import * as ideaService from '@/services/idea-service';
import ModuleInfo from "@/components/participant/molecules/ModuleInfo.vue";
import PlayState from "@/modules/playing/shopit/organisms/PlayState.vue";
import SingleplayerState from "@/modules/playing/shopit/organisms/SingleplayerState.vue";
import JoinState from "@/modules/playing/shopit/organisms/JoinState.vue";


export default class Card extends Vue {
  @Prop({ default: 0 }) readonly cost!: number;
  @Prop({ default: 0 }) readonly CO2!: number;
  @Prop({ default: 0 }) readonly energy!: number;
  @Prop({ default: 0 }) readonly lifetime!: number;
  @Prop({ default: 0 }) readonly water!: number;
  @Prop({ default: 0 }) readonly money!: number;
  @Prop({ default: '' }) readonly category!: string;
  @Prop({ default: '' }) readonly cardName!: string;

  gameConfig = gameConfig;
  cardSpriteFolder = gameConfig.gameValues.spriteFolder;

  getCardSprite(cardName) {
    return this.cardSpriteFolder + cardName + '.png';
  }
}
</script>

<style scoped lang="scss">
hr {
  background-color: var(--color-brown);
  margin: -2pt 0 4pt 0;
  width: 60%;
}

.cardStats {
  position: relative;
  z-index: 10;
  color: var(--color-brown);
  font-size: var(--font-size-small);
  font-weight: var(--font-weight-bold);
  font-family: var(--font-family);

  li {
    margin-top: -2pt;

    span {
      margin-left: 1pt;
      font-weight: var(--font-weight-semibold);
      font-size: var(--font-size-xxsmall);
    }
  }
}

.cardCost {
  font-size: var(--font-size-xlarge);
  font-weight: var(--font-weight-bold);
}

.categoryCardIcon {
  color: var(--color-brown-light);
  position: absolute;
  bottom: 0;
  left: 0;
  margin: 5%;
  transition: 0.3s;
}

.categoryCardIconHighlighted {
  color: var(--color-evaluating);
  transform: scale(200%);
  bottom: 0.5rem;
  left: 0.5rem;
  animation: shake 0.3s;
}

@keyframes shake {
  0% {
    transform: translateX(0) scale(100%);
  }
  25% {
    transform: translateX(5px) scale(125%);
  }
  50% {
    transform: translateX(-5px) scale(150%);
  }
  75% {
    transform: translateX(5px) scale(175%);
  }
  100% {
    transform: translateX(0) scale(200%);
  }
}

.cardImage {
  z-index: 0;
  position: absolute;
  width: 70%;
  bottom: 0;
  right: 0;
  margin: 2.7%;
}
</style>
