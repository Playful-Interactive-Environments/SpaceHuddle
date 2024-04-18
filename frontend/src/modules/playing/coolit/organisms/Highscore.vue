<template>
  <el-collapse v-model="openHighScoreLevels">
    <el-collapse-item
      v-for="level of highScoreList"
      :key="level.ideaId"
      :title="getLevelTitle(level.ideaId)"
      :name="level.ideaId"
    >
      <table class="highscore-table">
        <tr v-for="entry of level.details" :key="entry.avatar.symbol">
          <td>
            <font-awesome-icon
              :icon="entry.avatar.symbol"
              :style="{ color: entry.avatar.color }"
            ></font-awesome-icon>
          </td>
          <td>
            {{ Math.round((entry.value.normalisedTime / 60000) * 100) }}
          </td>
          <td>
            <el-rate
              v-model="entry.value.rate"
              size="large"
              :max="3"
              :disabled="true"
            />
          </td>
        </tr>
      </table>
    </el-collapse-item>
  </el-collapse>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { VoteParameterResult } from '@/types/api/Vote';
import * as cashService from '@/services/cash-service';
import * as votingService from '@/services/voting-service';
import { Idea } from '@/types/api/Idea';
import { LevelWorkflowType } from '@/types/game/LevelWorkflowType';
import * as ideaService from '@/services/idea-service';

@Options({
  components: {},
})
export default class Highscore extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  highScoreList: VoteParameterResult[] = [];
  openHighScoreLevels: string[] = [];
  ideas: Idea[] = [];

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    if (this.taskId) {
      ideaService.registerGetIdeasForTask(
        this.taskId,
        null,
        null,
        this.updateIdeas,
        this.authHeaderTyp,
        3
      );
      votingService.registerGetParameterResult(
        this.taskId,
        '-',
        this.updateHighScore,
        this.authHeaderTyp,
        5 * 60
      );
    }
  }

  unmounted(): void {
    cashService.deregisterAllGet(this.updateHighScore);
    cashService.deregisterAllGet(this.updateIdeas);
  }

  updateHighScore(list: VoteParameterResult[]): void {
    if (this.highScoreList.length === 0)
      this.openHighScoreLevels = list.map((item) => item.ideaId);
    for (const level of list) {
      if (level.details) {
        level.details = level.details
          .sort((a, b) => b.value.normalisedTime - a.value.normalisedTime)
          .slice(0, 5);
      }
    }
    this.highScoreList = list;
  }

  updateIdeas(ideas: Idea[]): void {
    this.ideas = ideas.filter(
      (idea) => idea.parameter.state === LevelWorkflowType.approved
    );
  }

  getLevelTitle(levelId: string): string {
    if (this.ideas) {
      const level = this.ideas.find((item) => item.id === levelId);
      if (level) return level.keywords;
    }
    return '';
  }
}
</script>

<style lang="scss" scoped>
.highscore-table {
  color: var(--color-playing);
  width: 100%;

  td {
    width: 33%;
  }
}
</style>
