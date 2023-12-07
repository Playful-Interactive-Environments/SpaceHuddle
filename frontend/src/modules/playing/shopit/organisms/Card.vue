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
  <p class="cardCondition">
    {{ $t(getCondition(condition, category)) }}
  </p>
  <img :src="getCardSprite(cardName)" alt="{{ category }}" class="cardImage" />
  <font-awesome-icon
    :icon="gameConfig.categories[category].settings.icon"
    class="categoryCardIcon"
  />
  <img
    :src="gameConfig.gameValues.sparkle"
    alt="sparkle"
    class="sparkle"
    v-if="condition === 1"
  />
</template>

<script lang="ts">
import { Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import gameConfig from '@/modules/playing/shopit/data/gameConfig.json';

export default class Card extends Vue {
  @Prop({ default: 0 }) readonly cost!: number;
  @Prop({ default: 0 }) readonly CO2!: number;
  @Prop({ default: 0 }) readonly energy!: number;
  @Prop({ default: 0 }) readonly lifetime!: number;
  @Prop({ default: 0 }) readonly water!: number;
  @Prop({ default: 0 }) readonly money!: number;
  @Prop({ default: '' }) readonly category!: string;
  @Prop({ default: 2 }) readonly condition!: number;
  @Prop({ default: '' }) readonly cardName!: string;

  gameConfig = gameConfig;
  cardSpriteFolder = gameConfig.gameValues.spriteFolder;

  getCardSprite(cardName) {
    return this.cardSpriteFolder + cardName + '.png';
  }

  getCondition(condition: number, category: string) {
    return condition === 2
      ? category === 'food'
        ? 'module.playing.shopit.participant.cards.conditions.regional'
        : 'module.playing.shopit.participant.cards.conditions.2ndHand'
      : category === 'food'
      ? 'module.playing.shopit.participant.cards.conditions.exotic'
      : 'module.playing.shopit.participant.cards.conditions.brandNew';
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

.cardCondition {
  color: var(--color-brown);
  position: absolute;
  top: 0;
  right: 0;
  margin: 5%;
  text-align: right;
  font-size: var(--font-size-xsmall);
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

.sparkle {
  width: 100%;
  height: 100%;
  position: absolute;
  top: 0;
  bottom: 0;
  right: 0;
  left: 0;
  pointer-events: none;
}
</style>
