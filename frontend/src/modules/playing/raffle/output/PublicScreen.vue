<template>
  <div>
    <lottery-machine
      v-if="options.length > 0"
      v-model="startLottery"
      :options="options.map((item) => item.avatar)"
      @lotteryDone="lotteryDone"
    />
    <el-button
      v-if="hasAdditionAvatar"
      type="primary"
      @click="draw"
      :disabled="startLottery"
    >
      {{ $t('module.playing.raffle.moderatorContent.draw') }}
    </el-button>
    <br />
    <el-space
      wrap
      v-for="(raffle, index) in [...raffleList].reverse()"
      :key="index"
    >
      <div v-if="raffle.length > 0">
        {{ raffleList.length - index }}.
        {{ $t('module.playing.raffle.moderatorContent.raffle') }}:
      </div>
      <div class="ball" v-for="winner of raffle" :key="winner.id">
        <font-awesome-icon
          :icon="winner.symbol"
          :style="{ color: winner.color }"
        ></font-awesome-icon>
      </div>
    </el-space>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import { IModeratorContent } from '@/types/ui/IModeratorContent';
import * as taskParticipantService from '@/services/task-participant-service';
import * as cashService from '@/services/cash-service';
import * as taskService from '@/services/task-service';
import * as ideaService from '@/services/idea-service';
import { Task } from '@/types/api/Task';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { TaskParticipantState } from '@/types/api/TaskParticipantState';
import { Avatar } from '@/types/api/Participant';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import LotteryMachine from '@/modules/playing/raffle/atoms/LotteryMachine.vue';
import TaskParticipantStatesType from '@/types/enum/TaskParticipantStatesType';
import { RaffleCondition } from '../types/RaffleCondition';
import { Idea } from '@/types/api/Idea';

@Options({
  components: {
    LotteryMachine,
    FontAwesomeIcon,
  },
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PublicScreen extends Vue implements IModeratorContent {
  @Prop() readonly taskId!: string;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  task: Task | null = null;
  trackingResult: TaskParticipantState[] = [];
  raffleList: Avatar[][] = [];
  winnerList: Avatar[] = [];
  startLottery = false;

  get currentWinner(): Avatar | null {
    if (this.winnerList.length > 0) return this.winnerList[0];
    return null;
  }

  get hasAdditionAvatar(): boolean {
    if (this.trackingResult.length === 0) return false;
    return this.options.length !== 0;
  }

  get options(): TaskParticipantState[] {
    return this.trackingResult.filter(
      (item) => !this.winnerList.find((winner) => winner.id === item.avatar.id)
    );
  }

  mounted(): void {
    taskService.registerGetTaskById(
      this.taskId,
      this.updateTask,
      this.authHeaderTyp,
      60 * 60
    );

    ideaService.registerGetIdeasForTask(
      this.taskId,
      null,
      null,
      this.updateIdeas,
      this.authHeaderTyp,
      60 * 60
    );
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTask);
    cashService.deregisterAllGet(this.updateIdeas);
    cashService.deregisterAllGet(this.updateFinalResult);
  }

  unmounted(): void {
    this.deregisterAll();
  }

  updateTask(task: Task): void {
    this.task = task;
    cashService.deregisterAllGet(this.updateFinalResult);

    taskParticipantService.registerGetList(
      task.parameter.input[0].view.id,
      this.updateFinalResult,
      this.authHeaderTyp
    );
  }

  updateFinalResult(result: TaskParticipantState[]): void {
    if (this.task) {
      const condition = this.task.modules[0].parameter.condition;
      if (condition === RaffleCondition.FINISHED) {
        this.trackingResult = result.filter(
          (item) =>
            item.state === TaskParticipantStatesType.FINISHED &&
            item.parameter.gameplayResult
        );
      } else if (condition === RaffleCondition.WON) {
        this.trackingResult = result.filter(
          (item) =>
            item.state === TaskParticipantStatesType.FINISHED &&
            item.parameter.gameplayResult?.stars === 3
        );
      } else {
        this.trackingResult = result.filter(
          (item) => item.parameter.gameplayResult
        );
      }
    } else {
      this.trackingResult = result;
    }
  }

  raffleCount = 0;
  updateIdeas(ideas: Idea[]): void {
    if (ideas.length > 0) {
      this.raffleCount =
        ideas.sort((a, b) => b.parameter.raffle - a.parameter.raffle)[0]
          .parameter.raffle + 1;
      this.winnerList.push(
        ...ideas
          .sort((a, b) => a.order - b.order)
          .map((item) => {
            return {
              id: item.parameter.participant,
              color: item.parameter.color,
              symbol: item.parameter.symbol,
            } as Avatar;
          })
      );
      for (let i = 0; i <= this.raffleCount; i++) {
        this.raffleList[i] = ideas
          .filter((item) => item.parameter.raffle === i)
          .sort((a, b) => a.order - b.order)
          .map((item) => {
            return {
              id: item.parameter.participant,
              color: item.parameter.color,
              symbol: item.parameter.symbol,
            } as Avatar;
          });
      }
    }
  }

  reloadTaskSettings(): void {
    //
  }

  draw(): void {
    this.startLottery = true;
  }

  lotteryDone(avatar: Avatar): void {
    this.winnerList.push(avatar);
    this.raffleList[this.raffleCount].push(avatar);
    ideaService.postIdea(
      this.taskId,
      {
        keywords: avatar.id,
        parameter: {
          participant: avatar.id,
          color: avatar.color,
          symbol: avatar.symbol,
          count: this.winnerList.length,
          raffle: this.raffleCount,
        },
      },
      this.authHeaderTyp
    );
  }
}
</script>

<style scoped>
.el-space {
  padding-top: 1rem;
  padding-right: 1rem;
  //display: flex;
}

.ball {
  border-radius: 50%;
  background-color: white;
  width: calc(var(--font-size-default) * 2);
  height: calc(var(--font-size-default) * 2);
  padding-top: calc(var(--font-size-default) * 0.4);
  text-align: center;
}
</style>
