<template>
  <el-tabs v-model="activeTab">
    <el-tab-pane
      name="tutorial"
      :label="$t('module.playing.moveit.publicScreen.tutorial')"
    >
      <el-carousel :interval="10000" height="calc(var(--app-height) * 0.6)">
        <el-carousel-item>
          <div class="info-image">
            <el-image
              src="/assets/games/moveit/tutorial/select.png"
              alt=""
              :preview-src-list="['/assets/games/moveit/tutorial/select.png']"
              :hide-on-click-modal="true"
            />
          </div>
          <div class="info-text">
            <p
              v-html="
                replaceLinebreaks(
                  $t('module.playing.moveit.participant.tutorial.select')
                )
              "
            ></p>
          </div>
        </el-carousel-item>
        <el-carousel-item>
          <div class="info-image">
            <el-image
              src="/assets/games/moveit/tutorial/stats.gif"
              alt=""
              :preview-src-list="['/assets/games/moveit/tutorial/stats.gif']"
              :hide-on-click-modal="true"
            />
          </div>
          <div class="info-text">
            <p
              v-html="
                replaceLinebreaks(
                  $t('module.playing.moveit.participant.tutorial.statistics')
                )
              "
            ></p>
          </div>
        </el-carousel-item>
        <el-carousel-item>
          <div class="info-image">
            <el-image
              src="/assets/games/moveit/tutorial/drive.gif"
              alt=""
              :preview-src-list="['/assets/games/moveit/tutorial/drive.gif']"
              :hide-on-click-modal="true"
            />
          </div>
          <div class="info-text">
            <p
              v-html="
                replaceLinebreaks(
                  $t('module.playing.moveit.participant.tutorial.drive')
                )
              "
            ></p>
          </div>
        </el-carousel-item>
        <el-carousel-item>
          <div class="info-image">
            <el-image
              src="/assets/games/moveit/tutorial/statsGame.png"
              alt=""
              :preview-src-list="[
                '/assets/games/moveit/tutorial/statsGame.png',
              ]"
              :hide-on-click-modal="true"
            />
          </div>
          <div class="info-text">
            <p
              v-html="
                replaceLinebreaks(
                  $t('module.playing.moveit.participant.tutorial.statsGame')
                )
              "
            ></p>
          </div>
        </el-carousel-item>
        <el-carousel-item>
          <div class="info-image">
            <el-image
              src="/assets/games/moveit/tutorial/Controls2.png"
              alt=""
              :preview-src-list="[
                '/assets/games/moveit/tutorial/Controls2.png',
              ]"
              :hide-on-click-modal="true"
            />
          </div>
          <div class="info-text">
            <p
              v-html="
                replaceLinebreaks(
                  $t('module.playing.moveit.participant.tutorial.speed')
                )
              "
            ></p>
          </div>
        </el-carousel-item>
        <el-carousel-item>
          <div class="info-image">
            <el-image
              src="/assets/games/moveit/tutorial/Controls3.png"
              alt=""
              :preview-src-list="[
                '/assets/games/moveit/tutorial/Controls3.png',
              ]"
              :hide-on-click-modal="true"
            />
          </div>
          <div class="info-text">
            <p
              v-html="
                replaceLinebreaks(
                  $t('module.playing.moveit.participant.tutorial.joystick')
                )
              "
            ></p>
          </div>
        </el-carousel-item>
        <el-carousel-item>
          <div class="info-image">
            <el-image
              src="/assets/games/moveit/tutorial/Controls1.png"
              alt=""
              :preview-src-list="[
                '/assets/games/moveit/tutorial/Controls1.png',
              ]"
              :hide-on-click-modal="true"
            />
          </div>
          <div class="info-text">
            <p
              v-html="
                replaceLinebreaks(
                  $t('module.playing.moveit.participant.tutorial.combined')
                )
              "
            ></p>
          </div>
        </el-carousel-item>
        <el-carousel-item>
          <div class="info-image">
            <el-image
              src="/assets/games/moveit/tutorial/bus.png"
              alt=""
              :preview-src-list="['/assets/games/moveit/tutorial/bus.png']"
              :hide-on-click-modal="true"
            />
          </div>
          <div class="info-text">
            <p
              v-html="
                replaceLinebreaks(
                  $t('module.playing.moveit.participant.tutorial.bus')
                )
              "
            ></p>
          </div>
        </el-carousel-item>
        <el-carousel-item>
          <div class="info-image">
            <el-image
              src="/assets/games/moveit/tutorial/cleanUp.gif"
              alt=""
              :preview-src-list="['/assets/games/moveit/tutorial/cleanUp.gif']"
              :hide-on-click-modal="true"
            />
          </div>
          <div class="info-text">
            <p
              v-html="
                replaceLinebreaks(
                  $t('module.playing.moveit.participant.tutorial.cleanUp')
                )
              "
            ></p>
          </div>
        </el-carousel-item>
        <el-carousel-item>
          <div class="info-image">
            <el-image
              src="/assets/games/moveit/tutorial/nudging.gif"
              alt=""
              :preview-src-list="['/assets/games/moveit/tutorial/nudging.gif']"
              :hide-on-click-modal="true"
            />
          </div>
          <div class="info-text">
            <p
              v-html="
                replaceLinebreaks(
                  $t('module.playing.moveit.participant.tutorial.nudging')
                )
              "
            ></p>
          </div>
        </el-carousel-item>
        <el-carousel-item>
          <div class="info-image">
            <el-image
              src="/assets/games/moveit/tutorial/improve.gif"
              alt=""
              :preview-src-list="['/assets/games/moveit/tutorial/improve.gif']"
              :hide-on-click-modal="true"
            />
          </div>
          <div class="info-text">
            <p
              v-html="
                replaceLinebreaks(
                  $t('module.playing.moveit.participant.tutorial.improve')
                )
              "
            ></p>
          </div>
        </el-carousel-item>
      </el-carousel>
    </el-tab-pane>
    <el-tab-pane
      name="highscore"
      :label="$t('module.playing.moveit.publicScreen.highscore')"
    >
      <Highscore
        class="highscore"
        :task-id="taskId"
        :auth-header-typ="authHeaderTyp"
      />
    </el-tab-pane>
  </el-tabs>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import Highscore from '../organisms/Highscore.vue';

@Options({
  components: { Highscore },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PublicScreen extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  activeTab = 'tutorial';

  replaceLinebreaks(text): string {
    return text.replace(/\n/g, '<br><br>');
  }
}
</script>

<style lang="scss" scoped>
.info-image {
  height: 70%;
  text-align: center;

  .el-image {
    max-height: 50vh;
    height: 100%;
  }
}

.info-text {
  padding: 2rem 0;
  overflow: auto;
}

.highscore::v-deep(table) {
  font-size: var(--font-size-large);
}
</style>
