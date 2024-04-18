<template>
  <el-tabs v-model="activeTab">
    <el-tab-pane
      name="tutorial"
      :label="$t('module.playing.coolit.publicScreen.tutorial')"
    >
      <el-carousel :interval="10000" height="calc(var(--app-height) * 0.6)">
        <el-carousel-item>
          <div class="info-image">
            <el-image
              src="/assets/games/coolit/tutorial/gases.gif"
              alt=""
              :preview-src-list="['/assets/games/coolit/tutorial/gases.gif']"
              :hide-on-click-modal="true"
            />
          </div>
          <div class="info-text">
            <p
              v-html="
                replaceLinebreaks(
                  $t('module.playing.coolit.participant.tutorial.atmosphere')
                )
              "
            ></p>
          </div>
        </el-carousel-item>
        <el-carousel-item>
          <div class="info-image">
            <el-image
              src="/assets/games/coolit/tutorial/playing1.gif"
              alt=""
              :preview-src-list="['/assets/games/coolit/tutorial/playing1.gif']"
              :hide-on-click-modal="true"
            />
          </div>
          <div class="info-text">
            <p
              v-html="
                replaceLinebreaks(
                  $t('module.playing.coolit.participant.tutorial.light')
                )
              "
            ></p>
          </div>
        </el-carousel-item>
        <el-carousel-item>
          <div class="info-image">
            <el-image
              src="/assets/games/coolit/tutorial/playing2.gif"
              alt=""
              :preview-src-list="['/assets/games/coolit/tutorial/playing2.gif']"
              :hide-on-click-modal="true"
            />
          </div>
          <div class="info-text">
            <p
              v-html="
                replaceLinebreaks(
                  $t('module.playing.coolit.participant.tutorial.heat')
                )
              "
            ></p>
          </div>
        </el-carousel-item>
        <el-carousel-item>
          <div class="info-image">
            <el-image
              src="/assets/games/coolit/tutorial/temperatureBar.png"
              alt=""
              :preview-src-list="[
                '/assets/games/coolit/tutorial/temperatureBar.png',
              ]"
              :hide-on-click-modal="true"
            />
          </div>
          <div class="info-text">
            <p
              v-html="
                replaceLinebreaks(
                  $t('module.playing.coolit.participant.tutorial.play')
                )
              "
            ></p>
          </div>
        </el-carousel-item>
      </el-carousel>
    </el-tab-pane>
    <el-tab-pane
      name="highscore"
      :label="$t('module.playing.coolit.publicScreen.highscore')"
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
