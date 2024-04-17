<template>
  <div class="gameArea">
    <div class="cardBox">
      <div
        class="cardContainer"
        :style="{
          backgroundImage: 'url(' + gameConfig.gameValues.cardBackground + ')',
        }"
      >
        <ul class="cardStats">
          <li class="cardCost">
            {{ cost }}
            <hr />
          </li>
          <li v-for="property in properties" :key="property.unit">
            <span
              class="boxSpan"
              :class="{ filledBoxSpan: property.filled }"
              :id="property.name"
              @click="boxClicked(property.name)"
            >
              <font-awesome-icon
                v-if="property.filled && property.iconChosen !== ''"
                :icon="['fas', property.iconChosen]"
                :class="{ boxIncorrect: showChecks && !property.correct }"
              />
            </span>
            {{ property.num
            }}<span>{{
              $t('module.playing.shopit.participant.cards.' + property.unit)
            }}</span>
          </li>
        </ul>
        <p class="cardCondition">
          {{ $t(getCondition(condition, category)) }}
        </p>
        <img
          :src="getCardSprite(cardName)"
          alt="{{ category }}"
          class="cardImage"
        />
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
      </div>
    </div>
    <div class="propertyBox">
      <div
        v-for="property in buttonOrder"
        class="property"
        :key="property.name"
        @click="propertyClicked(property.name)"
      >
        <font-awesome-icon :icon="['fas', property.icon]" />
        <p>
          {{
            $t(
              'module.playing.shopit.participant.cardProperties.' +
                property.name
            )
          }}
        </p>
      </div>
    </div>
    <div class="TutGameButtons">
      <el-button
        type="primary"
        :disabled="
          this.properties.filter((property) => property.correct).length < 5
        "
        @click="$emit('playFinished')"
        >Continue</el-button
      >
      <el-button id="skip" @click="$emit('playFinished')">
        {{ $t('module.playing.shopit.participant.skip') }}
        skip
      </el-button>
    </div>
  </div>
</template>

<script lang="ts">
import { Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import gameConfig from '@/modules/playing/shopit/data/gameConfig.json';

interface Property {
  name: string;
  icon: string;
  unit: string;
  num: number;
  iconChosen: string;
  filled: boolean;
  correct: boolean;
}

export default class TutorialGame extends Vue {
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
  activeBox: HTMLElement | null = null;
  showChecks = false;

  properties: Property[] = [];
  buttonOrder: Property[] = [];

  mounted() {
    this.properties = [
      {
        name: 'CO²',
        icon: 'smog',
        unit: 'kg',
        num: this.CO2,
        iconChosen: '',
        filled: false,
        correct: false,
      },
      {
        name: 'electricity',
        icon: 'bolt',
        unit: 'kw',
        num: this.energy,
        iconChosen: '',
        filled: false,
        correct: false,
      },
      {
        name: 'lifetime',
        icon: 'clock',
        unit: 'years',
        num: this.lifetime,
        iconChosen: '',
        filled: false,
        correct: false,
      },
      {
        name: 'water',
        icon: 'droplet',
        unit: 'kl',
        num: this.water,
        iconChosen: '',
        filled: false,
        correct: false,
      },
      {
        name: 'money',
        icon: 'coins',
        unit: 'price',
        num: this.money,
        iconChosen: '',
        filled: false,
        correct: false,
      },
    ];
    this.buttonOrder = this.shuffle(this.properties.map((x) => x));
  }

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

  boxClicked(id) {
    if (this.activeBox) {
      this.activeBox.classList.remove('activeBoxSpan');
    }
    const element = document.getElementById(id);
    if (element) {
      const property = this.properties.filter(
        (property) => property.name === element.id
      )[0];
      if (!property.filled) {
        element.classList.add('activeBoxSpan');
        this.activeBox = element;
      } else {
        property.filled = false;
      }
    }
  }

  propertyClicked(name) {
    if (this.activeBox) {
      for (let i = 0; i < this.properties.length; i++) {
        if (this.properties[i].name === this.activeBox.id) {
          this.properties[i].filled = true;
          this.properties[i].correct = this.activeBox.id === name;
          switch (name) {
            case 'CO²':
              this.properties[i].iconChosen = 'smog';
              break;
            case 'electricity':
              this.properties[i].iconChosen = 'bolt';
              break;
            case 'lifetime':
              this.properties[i].iconChosen = 'clock';
              break;
            case 'water':
              this.properties[i].iconChosen = 'droplet';
              break;
            case 'money':
              this.properties[i].iconChosen = 'coins';
              break;
          }
        }
      }
      this.showChecks =
        this.properties.filter((property) => property.filled).length >= 5;
      this.activeBox = null;
    }
  }

  shuffle(cards) {
    let currentIndex = cards.length;
    let randomIndex;

    while (currentIndex > 0) {
      randomIndex = Math.floor(Math.random() * currentIndex);
      currentIndex--;

      [cards[currentIndex], cards[randomIndex]] = [
        cards[randomIndex],
        cards[currentIndex],
      ];
    }
    return cards;
  }
}
</script>

<style scoped lang="scss">
.gameArea {
  width: 100%;
  height: 100%;
}

.cardBox {
  height: 65%;
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
}

.propertyBox {
  height: 25%;
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  flex-wrap: wrap;
  .property {
    margin: 0.1rem 0.2rem;
    height: 45%;
    width: 30%;
    color: var(--color-brown);
    background-color: var(--color-background);
    border-radius: var(--border-radius-small);
    border: 0.3rem solid var(--color-brown);
    display: flex;
    flex-direction: column;
    justify-content: space-evenly;
    align-items: center;
    svg {
      display: block;
      width: 50%;
      height: 50%;
    }
    p {
      display: block;
      font-weight: var(--font-weight-semibold);
      font-size: var(--font-size-small);
    }
  }
}

.cardContainer {
  position: relative;
  aspect-ratio: 1904/2564;
  background-size: cover;
  z-index: 1;
  height: 70%;
  padding: 3.2%;
  margin-left: -10%;
  left: 5%;
  filter: drop-shadow(var(--color-dark-contrast) -0.4rem 0.2rem 0.2rem);
  transition: 0.3s;
  background-color: transparent;
}

hr {
  background-color: var(--color-brown);
  margin: -2pt 0 4pt 0;
  width: 60%;
}

.cardStats {
  position: relative;
  z-index: 10;
  color: var(--color-brown);
  font-size: var(--font-size-xlarge);
  font-weight: var(--font-weight-bold);
  font-family: var(--font-family);

  li {
    margin-top: 2pt;
    display: flex;
    align-items: center;
    span {
      margin: 2pt;
      font-weight: var(--font-weight-semibold);
      font-size: var(--font-size-small);
    }

    .boxSpan {
      margin-right: 4pt;
      height: var(--font-size-xlarge);
      width: var(--font-size-xlarge);
      background-color: var(--color-background-darker);
      border-radius: 2pt;
      border: 2.5pt dashed var(--color-dark-contrast-light);
      display: flex;
      align-content: center;
      justify-content: center;
      svg {
        height: 100%;
        width: 100%;
        align-self: center;
      }
    }
    .activeBoxSpan {
      background-color: var(--color-evaluating);
    }
    .filledBoxSpan {
      background-color: transparent !important;
      border: none !important;
    }
    .boxIncorrect {
      color: var(--color-evaluating);
    }
  }
}

.cardCost {
  margin-top: -2pt !important;
  display: block !important;
  font-size: var(--font-size-xxxlarge);
  font-weight: var(--font-weight-bold);
}

.cardCondition {
  color: var(--color-brown);
  position: absolute;
  top: 0;
  right: 0;
  margin: 5%;
  text-align: right;
  font-size: var(--font-size-medium);
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
  max-width: 60%;
  max-height: 80%;
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

.TutGameButtons {
  height: 10%;
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  #skip {
    position: absolute;
    right: 2rem;
  }
}
</style>
