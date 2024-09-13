<template>
  <table v-if="step === Step.categorise">
    <tr>
      <th></th>
      <th>
        {{ $t(`module.playing.coolit.participant.moleculeType.greenhouseGas`) }}
      </th>
      <th>
        {{
          $t(`module.playing.coolit.participant.moleculeType.atmosphericGas`)
        }}
      </th>
    </tr>
    <tr>
      <td>
        <draggable
          class="dragContainer"
          v-model="uncategorisedMolecules"
          group="item"
          item-key="name"
        >
          <template v-slot:item="{ element }">
            <div
              :id="`${element}`"
              class="clickable molecule"
              :class="{
                selected: activeMolecule === element,
              }"
              @click="activeObjectChanged(element)"
              @touchstart="activeObjectChanged(element)"
            >
              <div
                class="molecule-image"
                :style="{
                  '--molecule-color': getMoleculeConfig(element).color,
                }"
              >
                <img :src="moleculeImages[element]" :alt="element" />
              </div>
              {{
                $t(
                  `module.playing.coolit.participant.moleculeInfo.${element}.title`
                )
              }}
            </div>
          </template>
        </draggable>
      </td>
      <td>
        <draggable
          class="dragContainer"
          v-model="greenhouseMolecules"
          group="item"
          item-key="name"
          @add="addToClassification($event)"
        >
          <template v-slot:item="{ element }">
            <div
              :id="`${element}`"
              class="clickable molecule"
              :class="{
                selected: activeMolecule === element,
                error:
                  getMoleculeConfig(element).type !== GasType.greenhouseGas,
                correct:
                  getMoleculeConfig(element).type === GasType.greenhouseGas,
              }"
              @click="activeObjectChanged(element)"
              @touchstart="activeObjectChanged(element)"
            >
              <div
                class="molecule-image"
                :style="{
                  '--molecule-color': getMoleculeConfig(element).color,
                }"
              >
                <img :src="moleculeImages[element]" :alt="element" />
              </div>
              {{
                $t(
                  `module.playing.coolit.participant.moleculeInfo.${element}.title`
                )
              }}
            </div>
          </template>
        </draggable>
      </td>
      <td>
        <draggable
          class="dragContainer"
          v-model="nonGreenhouseMolecules"
          group="item"
          item-key="name"
          @add="addToClassification($event)"
        >
          <template v-slot:item="{ element }">
            <div
              :id="`${element}`"
              class="clickable molecule"
              :class="{
                selected: activeMolecule === element,
                error:
                  getMoleculeConfig(element).type !== GasType.atmosphericGas,
                correct:
                  getMoleculeConfig(element).type === GasType.atmosphericGas,
              }"
              @click="activeObjectChanged(element)"
              @touchstart="activeObjectChanged(element)"
            >
              <div
                class="molecule-image"
                :style="{
                  '--molecule-color': getMoleculeConfig(element).color,
                }"
              >
                <img :src="moleculeImages[element]" :alt="element" />
              </div>
              {{
                $t(
                  `module.playing.coolit.participant.moleculeInfo.${element}.title`
                )
              }}
            </div>
          </template>
        </draggable>
      </td>
    </tr>
  </table>
  <div v-else>
    <h1>
      {{
        $t(`module.playing.coolit.participant.tutorial.order.${orderProperty}`)
      }}
    </h1>
    <draggable
      class="dragContainer"
      v-model="greenhouseMolecules"
      group="item"
      item-key="name"
      @change="orderChanged()"
    >
      <template v-slot:item="{ element }">
        <div
          :id="`${element}`"
          class="clickable molecule"
          :class="{
            selected: activeMolecule === element,
            error: !isCorrectIndex(element),
            correct: isCorrectIndex(element),
          }"
          @click="activeObjectChanged(element)"
          @touchstart="activeObjectChanged(element)"
        >
          <div
            class="molecule-image"
            :style="{
              '--molecule-color': getMoleculeConfig(element).color,
            }"
          >
            <img :src="moleculeImages[element]" :alt="element" />
          </div>
          {{
            $t(
              `module.playing.coolit.participant.moleculeInfo.${element}.title`
            )
          }}
          <div v-if="correct && getMoleculeConfig(element)[orderProperty]">
            {{ getMoleculeConfig(element)[orderProperty] }}
            {{ unitText }}
          </div>
          <div v-else-if="correct">
            {{ $t('module.playing.coolit.participant.tutorial.unknown') }}
          </div>
        </div>
      </template>
    </draggable>
  </div>
  <div class="TutGameButtons">
    <el-button type="primary" id="continue" :disabled="!correct" @click="next">
      {{
        $t('module.playing.coolit.participant.tutorial.button.continueButton')
      }}
    </el-button>
    <el-button id="skip" @click="$emit('done')">
      {{ $t('module.playing.coolit.participant.tutorial.button.skip') }}
    </el-button>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as pixiUtil from '@/utils/pixi';
import { TrackingManager } from '@/types/tracking/TrackingManager';
import { Module } from '@/types/api/Module';
import draggable from 'vuedraggable';
import gameConfig from '@/modules/playing/coolit/data/gameConfig.json';

enum GasType {
  atmosphericGas = 'atmosphericGas',
  greenhouseGas = 'greenhouseGas',
}

enum Step {
  categorise = 'categorise',
  order = 'order',
}

@Options({
  components: { draggable },
  emits: ['done'],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class TutorialMolecule extends Vue {
  @Prop() readonly trackingManager!: TrackingManager;
  @Prop() readonly taskId!: string;
  @Prop() readonly module!: Module;
  textureToken = pixiUtil.createLoadingToken();
  moleculeImages: { [key: string]: string } = {};
  uncategorisedMolecules: string[] = [];
  greenhouseMolecules: string[] = [];
  nonGreenhouseMolecules: string[] = [];
  activeMolecule = '';
  gameConfig = gameConfig;
  correct = false;
  step = Step.categorise;
  orderProperty = 'globalWarmingFactorReal';
  parameterValue: { [key: string]: number } = {};

  GasType = GasType;
  Step = Step;

  get unitText(): string {
    switch (this.orderProperty) {
      case 'globalWarmingFactorReal':
        return this.$t(
          'module.playing.coolit.participant.moleculeInfo.gwpInfo'
        );
      case 'lifespan':
        return this.$t('module.playing.coolit.participant.moleculeInfo.years');
      case 'rationGreenhouse':
        return '%';
    }
    return '';
  }

  categorisationIsCorrect(): boolean {
    if (this.uncategorisedMolecules.length > 0) return false;
    for (const item of this.greenhouseMolecules) {
      if (this.getMoleculeConfig(item).type !== GasType.greenhouseGas)
        return false;
    }
    for (const item of this.nonGreenhouseMolecules) {
      if (this.getMoleculeConfig(item).type !== GasType.atmosphericGas)
        return false;
    }
    return true;
  }

  calculateParameterValues(): void {
    this.parameterValue = {};
    for (const item of this.greenhouseMolecules) {
      this.parameterValue[item] = parseFloat(
        this.getMoleculeConfig(item)[this.orderProperty]
      );
      if (isNaN(this.parameterValue[item])) this.parameterValue[item] = -1;
    }
  }

  correctOrder(): string[] {
    return [...this.greenhouseMolecules].sort(
      (a, b) => this.parameterValue[a] - this.parameterValue[b]
    );
  }

  orderIsCorrect(): boolean {
    const correctOrder = this.correctOrder();
    for (let i = 0; i < this.greenhouseMolecules.length; i++) {
      if (correctOrder[i] !== this.greenhouseMolecules[i]) return false;
    }
    return true;
  }

  isCorrectIndex(name: string): boolean {
    const correctOrder = this.correctOrder();
    const index = correctOrder.indexOf(name);
    return index >= 0 && this.greenhouseMolecules[index] === name;
  }

  mounted(): void {
    setTimeout(() => {
      pixiUtil
        .loadTexture('/assets/games/moveit/molecules.json', this.textureToken)
        .then((sheet) => {
          pixiUtil
            .convertSpritesheetToBase64(sheet, this.moleculeImages)
            .then(() => {
              this.uncategorisedMolecules = Object.keys(
                gameConfig.molecules
              ).sort(() => 0.5 - Math.random());
            });
        });
    }, 100);
  }

  unmounted(): void {
    pixiUtil.cleanupToken(this.textureToken);
  }

  @Watch('taskId', { immediate: true })
  onIdChanged(): void {
    //todo
  }

  activeObjectChanged(item: string): void {
    this.activeMolecule = item;
  }

  getMoleculeConfig(objectName: string): {
    formula: string;
    type: string;
    reference: string;
    color: string;
    globalWarmingFactor: number;
    globalWarmingFactorReal: number;
    lifespan: number | string;
    rationAtmosphere: number;
    rationGreenhouse: number;
    riseFactor: number;
    impactGreenhouse: number;
  } {
    if (objectName) {
      return gameConfig.molecules[objectName];
    }
    return {
      formula: '',
      type: 'greenhouseGas',
      reference: '',
      color: '#ffffff',
      globalWarmingFactor: 1,
      globalWarmingFactorReal: 1,
      lifespan: 1,
      rationAtmosphere: 0,
      rationGreenhouse: 0,
      riseFactor: 0,
      impactGreenhouse: 0,
    };
  }

  addToClassification(): void {
    this.correct = this.categorisationIsCorrect();
  }

  orderChanged(): void {
    this.correct = this.orderIsCorrect();
  }

  next(): void {
    this.greenhouseMolecules = this.greenhouseMolecules.sort(
      () => 0.5 - Math.random()
    );
    this.correct = false;
    if (this.step === Step.categorise) {
      this.step = Step.order;
    } else {
      if (this.orderProperty === 'globalWarmingFactorReal')
        this.orderProperty = 'lifespan';
      else if (this.orderProperty === 'lifespan')
        this.orderProperty = 'rationGreenhouse';
      else if (this.orderProperty === 'rationGreenhouse') this.$emit('done');
    }
    this.calculateParameterValues();
  }
}
</script>

<style lang="scss" scoped>
.molecule {
  font-size: var(--font-size-xxsmall);
  text-align: center;
  margin-bottom: 0.5rem;
  width: 100%;
}

.clickable {
  cursor: pointer;
}

.selected {
  //border: red 2px solid;
  //border-radius: var(--border-radius);
}

.error {
  background-color: color-mix(in srgb, var(--color-red) 45%, transparent);
  border-radius: var(--border-radius);
}

.correct {
  background-color: color-mix(in srgb, var(--color-green) 45%, transparent);
  border-radius: var(--border-radius);
}

.molecule-image {
  background-image: radial-gradient(
    circle,
    var(--molecule-color) 30%,
    #ffffff00 75%
  );
  border-radius: 50%;
  height: 5rem;
  width: 5rem;
  margin: 0.2rem auto;
  padding: 1rem;
  display: flex;

  img {
    margin: auto;
  }
}

.dragContainer {
  height: 100%;
  width: 100%;
}

table {
  width: 100%;
  height: 1px;
  border-spacing: 0.5rem;
  border-collapse: separate;

  th {
    text-align: center;
    width: 33%;
  }
}

.TutGameButtons {
  height: 10%;
  width: 100%;
  display: flex;
  justify-content: flex-start;
  align-items: flex-start;
  #continue {
    position: absolute;
    left: 2rem;
  }
  #skip {
    position: absolute;
    right: 2rem;
  }
}

h1 {
  font-weight: var(--font-weight-semibold);
  font-size: var(--font-size-xlarge);
}
</style>
