<template>
  <el-tabs v-model="activeTab">
    <el-tab-pane
      name="tutorial"
      :label="$t('module.playing.shopit.publicScreen.tutorial')"
    >
      <el-carousel :interval="10000" height="calc(var(--app-height) * 0.6)">
        <el-carousel-item>
          <div class="info-image">
            <el-image
              src="/assets/games/shopit/tutorial/tut1.gif"
              alt=""
              :preview-src-list="['/assets/games/shopit/tutorial/tut1.gif']"
              :hide-on-click-modal="true"
            />
          </div>
          <div class="info-text">
            <p
              v-html="
                replaceLinebreaks(
                  $t('module.playing.shopit.participant.tutorial.rules1')
                )
              "
            ></p>
          </div>
        </el-carousel-item>
        <el-carousel-item>
          <div class="info-image">
            <el-image
              src="/assets/games/shopit/tutorial/tut2.png"
              alt=""
              :preview-src-list="['/assets/games/shopit/tutorial/tut2.png']"
              :hide-on-click-modal="true"
            />
          </div>
          <div class="info-text">
            <p
              v-html="
                replaceLinebreaks(
                  $t('module.playing.shopit.participant.tutorial.rules2')
                )
              "
            ></p>
          </div>
        </el-carousel-item>
        <el-carousel-item>
          <div class="info-image">
            <el-image
              src="/assets/games/shopit/tutorial/PointCalc.png"
              alt=""
              :preview-src-list="[
                '/assets/games/shopit/tutorial/PointCalc.png',
              ]"
              :hide-on-click-modal="true"
            />
          </div>
          <div class="info-text">
            <p
              v-html="
                replaceLinebreaks(
                  $t('module.playing.shopit.participant.tutorial.PointCalc')
                )
              "
            ></p>
          </div>
        </el-carousel-item>
        <el-carousel-item>
          <div class="info-image">
            <el-image
              src="/assets/games/shopit/tutorial/tutGame.gif"
              alt=""
              :preview-src-list="['/assets/games/shopit/tutorial/tutGame.gif']"
              :hide-on-click-modal="true"
            />
          </div>
          <div class="info-text">
            <p
              v-html="
                replaceLinebreaks(
                  $t('module.playing.shopit.participant.tutorial.tutGame')
                )
              "
            ></p>
          </div>
        </el-carousel-item>
      </el-carousel>
    </el-tab-pane>
    <el-tab-pane
      name="highscore"
      :label="$t('module.playing.shopit.publicScreen.highscore')"
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
