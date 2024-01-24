<template>
  <div>
    <div class="link new" @click="selection = !selection">
      <span class="link-text">
        {{ $t('module.playing.findit.participant.newLevel') }}
      </span>
      <div class="newLevelSelectionContainer" v-if="selection">
        <el-button
          class="backButtonSelection"
          type="primary"

          >{{ $t('participant.molecules.moduleInfo.prev') }}</el-button
        >
        <div
          v-for="configType of Object.keys(gameConfig)"
          :key="configType"
          :style="{
            height: 100 / Object.keys(gameConfig).length + '%',
            color: getSettingsForLevelType(gameConfig, configType).color,
            backgroundImage:
              'url(' +
              getSettingsForLevelType(gameConfig, configType).background +
              ')',
          }"
          class="newLevelSelection"
          @click="levelSelected(null, configType)"
        >
          <div class="selectionWhite" :style="{height: 100 / Object.keys(gameConfig).length + '%'}"></div>
          <font-awesome-icon
            :icon="getSettingsForLevelType(gameConfig, configType).icon"
          />
          <p>{{
            $t(
                `module.playing.findit.participant.placeables.${configType}.name`
            )
          }}</p>
        </div>
      </div>
      <!--      <el-dropdown
        v-on:command="levelSelected(null, $event)"
        size="large"
        popper-class="select-new-level-type"
      >
        <span class="link-text">
          {{ $t('module.playing.findit.participant.newLevel') }}
        </span>
        <template #dropdown>
          <el-dropdown-menu>
            <el-dropdown-item
              v-for="configType of Object.keys(gameConfig)"
              :key="configType"
              :command="configType"
              :style="{
                color: getSettingsForLevelType(gameConfig, configType).color,
              }"
            >
              <font-awesome-icon
                :icon="getSettingsForLevelType(gameConfig, configType).icon"
              />
              &nbsp;
              {{
                $t(
                  `module.playing.findit.participant.placeables.${configType}.name`
                )
              }}
            </el-dropdown-item>
          </el-dropdown-menu>
        </template>
      </el-dropdown>-->
    </div>
    <div v-if="!selection">
      <div
        class="link media"
        :class="{ own: isOwnLevel(idea), notApproved: !isApproved(idea) }"
        v-for="idea of ideas"
        :key="idea.id"
        :style="{
          '--level-type-color': getSettingsForLevel(gameConfig, idea).color,
        }"
        @click="levelSelected(idea)"
      >
        <div class="media-left">
          <font-awesome-icon
            :icon="getSettingsForLevel(gameConfig, idea).icon"
          />
        </div>
        <div class="media-content">{{ idea.keywords }}</div>
        <div class="media-right">
          <div>
            <el-rate
              v-model="mapping[idea.id]"
              size="large"
              :max="3"
              :disabled="true"
            />
          </div>
          <div>
            <font-awesome-icon icon="coins" />
            {{ getPointsForLevel(idea.id) }} / {{ maxLevelPoints }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import * as cashService from '@/services/cash-service';
import * as authService from '@/services/auth-service';
import * as ideaService from '@/services/idea-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as taskParticipantService from '@/services/task-participant-service';
import { TaskParticipantIterationStep } from '@/types/api/TaskParticipantIterationStep';
import { GameStep } from '@/modules/playing/findit/output/Participant.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { TrackingManager } from '@/types/tracking/TrackingManager';
import * as configParameter from '@/utils/game/configParameter';
import { LevelWorkflowType } from '@/types/game/LevelWorkflowType';

@Options({
  components: { FontAwesomeIcon },
  emits: ['selectionDone'],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class SelectState extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: {} }) readonly gameConfig!: any;
  @Prop() readonly trackingManager!: TrackingManager;
  ideas: Idea[] = [];
  result: TaskParticipantIterationStep[] = [];
  mapping: { [key: string]: number } = {};
  selection = false;

  getSettingsForLevel = configParameter.getSettingsForLevel;
  getSettingsForLevelType = configParameter.getSettingsForLevelType;

  get maxLevelPoints(): number {
    return this.trackingManager.getStarPoints(3);
  }

  getPointsForLevel(ideaId: string): number {
    return this.trackingManager.getStarPoints(this.mapping[ideaId]);
  }

  unmounted(): void {
    cashService.deregisterAllGet(this.updateIdeas);
    cashService.deregisterAllGet(this.updateIterationSteps);
  }

  getStarsForIdea(ideaId: string): number {
    if (this.result) {
      const resultItem = this.result.find(
        (item) => item && item.ideaId === ideaId
      );
      if (resultItem) return resultItem.parameter.stars;
    }
    return 0;
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    if (this.taskId) {
      ideaService.registerGetIdeasForTask(
        this.taskId,
        null,
        null,
        this.updateIdeas,
        EndpointAuthorisationType.PARTICIPANT,
        3
      );
      taskParticipantService.registerGetIterationStepList(
        this.taskId,
        this.updateIterationSteps,
        EndpointAuthorisationType.PARTICIPANT,
        2 * 60
      );
    }
  }

  updateIdeas(ideas: Idea[]): void {
    this.ideas = ideas.filter(
      (item) =>
        item.parameter.state === LevelWorkflowType.approved || item.isOwn
    );
    this.calculateResult();
  }

  isApproved(level: Idea): boolean {
    return level.parameter.state === LevelWorkflowType.approved;
  }

  updateIterationSteps(steps: TaskParticipantIterationStep[]): void {
    const ideaList = steps
      .map((item) => item.ideaId)
      .filter(
        (value, index, self) =>
          self.findIndex((item) => item === value) === index
      );
    this.result = [];
    for (const ideaId of ideaList) {
      const played = steps
        .filter(
          (item) =>
            item.ideaId === ideaId && item.parameter.step === GameStep.Play
        )
        .sort((a, b) => b.parameter.stars - a.parameter.stars);
      if (played.length > 0) this.result.push(played[0]);
    }
    this.calculateResult();
  }

  calculateResult(): void {
    if (this.ideas && this.result) {
      const mapping: { [key: string]: number } = {};
      for (const idea of this.ideas) {
        mapping[idea.id] = this.getStarsForIdea(idea.id);
      }
      this.mapping = mapping;
    }
  }

  isOwnLevel(level: Idea): boolean {
    return level.participantId === authService.getParticipantId();
  }

  levelSelected(level: Idea | null, levelType: string | null = null) {
    if (!level) {
      this.$emit('selectionDone', levelType, null);
    } else {
      const levelType = configParameter.getTypeForLevel(this.gameConfig, level);
      this.$emit('selectionDone', levelType, level);
    }
  }
}
</script>

<style lang="scss">
.select-new-level-type {
  width: 90%;
}
</style>

<style lang="scss" scoped>
.link {
  background-color: var(--color-primary);
  color: white;
  border-radius: var(--border-radius);
  margin: 1rem;
  padding: 1rem;
}

.new {
  background-color: var(--color-dark-contrast-light);
}

.own {
  background-color: var(--color-informing);
}

.notApproved {
  background-color: var(--color-gray);
}

.el-rate {
  --el-rate-fill-color: white;
  --el-rate-disabled-void-color: var(--color-gray-inactive);
}

.el-dropdown {
  width: 100%;
  color: white;

  .link-text {
    width: 100%;
  }
}

.el-dropdown-menu {
  width: 100%;
}

.media-left {
  font-size: 2rem;
  background-color: white;
  color: var(--level-type-color);
  margin: -0.8rem;
  margin-right: 1rem;
  padding: 0.8rem;
  border-radius: calc(var(--border-radius) - 0.2rem) 0 0
    calc(var(--border-radius) - 0.2rem);
  width: 3.5rem;
  display: flex;
  justify-content: center;
  align-items: center;
}

.el-rate--large {
  height: unset;
}

.media {
  align-items: unset;
}

.media-right {
  font-size: var(--font-size-small);
}

:focus-visible {
  outline: unset;
}

.newLevelSelection {
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  background-size: cover;
  flex-direction: column;
  outline: 0.5rem solid var(--color-background);
  svg {
    width: 30%;
    height: 30%;
    z-index: 110;
  }
  p {
    z-index: 110;
    font-size: var(--font-size-xlarge);
    font-weight: var(--font-weight-bold);
    margin-top: 6pt;
  }
  .selectionWhite {
    width: 100%;
    background-color: #ffffff;
    position: absolute;
    opacity: 60%;
    transition: opacity 1s ease-in-out;
    z-index: 100;
  }
}

.newLevelSelectionContainer {
  position: absolute;
  top: 0;
  left: 0;
  width: calc(100%);
  height: 100%;
  z-index: 100;
  .backButtonSelection {
    position: absolute;
    top: 0.5rem;
    left: 0.5rem;
    z-index: 150;
  }
}
</style>
