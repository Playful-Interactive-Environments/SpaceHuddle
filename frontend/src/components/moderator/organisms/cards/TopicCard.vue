<template>
  <div class="card__extension">
    <TutorialStep
      type="sessionDetails"
      step="selectTopic"
      :order="6"
      :width="450"
      placement="bottom"
    >
      <el-card class="card" shadow="hover" :body-style="{ padding: '0px' }">
        <span class="level" ref="item">
          <span class="level-left">
            <div class="level-item card__info link" v-on:click="goToDetails">
              <h3 class="twoLineText line-break">{{ topic.title }}</h3>
              <p class="twoLineText line-break">{{ topic.description }}</p>
            </div>
          </span>
          <span class="level-right">
            <div class="level-item settings">
              <el-dropdown
                v-if="canModify"
                class="card__menu"
                v-on:command="menuItemSelected($event)"
              >
                <span class="el-dropdown-link">
                  <font-awesome-icon icon="ellipsis-h" />
                </span>
                <template #dropdown>
                  <el-dropdown-menu>
                    <el-dropdown-item command="edit">
                      <font-awesome-icon icon="pen" />
                    </el-dropdown-item>
                    <el-dropdown-item command="clone">
                      <font-awesome-icon icon="clone" />
                    </el-dropdown-item>
                    <el-dropdown-item command="delete">
                      <font-awesome-icon icon="trash" />
                    </el-dropdown-item>
                  </el-dropdown-menu>
                </template>
              </el-dropdown>
            </div>
            <TutorialStep
              type="sessionDetails"
              step="changeOrder"
              :order="7"
              placement="left"
            >
              <div class="card__drag level-item">
                <font-awesome-icon
                  icon="grip-vertical"
                  class="card__drag__icon"
                />
              </div>
            </TutorialStep>
          </span>
        </span>
      </el-card>
    </TutorialStep>
    <div class="card__extension__slot">
      <slot></slot>
    </div>
    <TopicSettings
      v-model:show-modal="showSettings"
      :session-id="sessionId"
      :topic-id="editingTopicId"
      v-on:topicUpdated="reload"
    />
  </div>
</template>

<script lang="ts">
import { Prop } from 'vue-property-decorator';
import { Options, Vue } from 'vue-class-component';
import { Topic } from '@/types/api/Topic';
import * as topicService from '@/services/topic-service';
import TopicSettings from '@/components/moderator/organisms/settings/TopicSettings.vue';
import TutorialStep from '@/components/shared/atoms/TutorialStep.vue';
import { ElMessageBox } from 'element-plus';

@Options({
  components: { TutorialStep, TopicSettings },
  emits: ['topicDeleted'],
})
export default class TopicCard extends Vue {
  @Prop({ default: '' }) readonly sessionId!: string;
  @Prop() topic!: Topic;
  @Prop({ default: true }) readonly canModify!: boolean;
  showSettings = false;
  editingTopicId = '';

  mounted(): void {
    this.editingTopicId = this.topic.id;
  }

  goToDetails(): void {
    this.$router.push(`/topic/${this.sessionId}/${this.topic.id}`);
  }

  async reload(topic: Topic): Promise<void> {
    this.topic.title = topic.title;
    this.topic.description = topic.description;
  }

  async deleteTopic(): Promise<void> {
    topicService.deleteTopic(this.topic.id).then((deleted) => {
      if (deleted) {
        topicService.refreshGetTopicList(this.sessionId);
        this.$emit('topicDeleted', this.topic.id);
      }
    });
  }

  menuItemSelected(command: string): void {
    switch (command) {
      case 'edit':
        this.editingTopicId = this.topic.id;
        this.showSettings = true;
        break;
      case 'clone':
        this.cloneTopic();
        break;
      case 'delete':
        this.deleteTopic();
        break;
    }
  }

  async cloneTopic(): Promise<void> {
    try {
      await ElMessageBox.confirm(
        this.$t('moderator.organism.cards.topicCard.clonePrompt'),
        this.$t('moderator.organism.cards.topicCard.clone'),
        {
          boxType: 'confirm',
          confirmButtonText: this.$t('moderator.organism.cards.topicCard.clone'),
        }
      );
      const clonedTopic = await topicService.clone(this.topic.id);
      this.editingTopicId = clonedTopic.id;
      this.showSettings = true;
    } catch {
      // do nothing if the MessageBox is declined
    }
  }
}
</script>

<style lang="scss" scoped>
.inactive {
  background-color: rgba(0, 0, 0, 0.5);
}

.link {
  cursor: pointer;
}

.level {
  align-items: stretch;
}

.settings {
  align-self: baseline;
  padding-top: 1rem;
}

.card {
  min-width: 18rem;
  height: 100%;
  border-top: unset;
  border-right: unset;
  border-left: unset;

  &__extension {
    border: 1px solid var(--el-border-color-light);
    border-radius: var(--border-radius-xs);

    &__slot {
      padding: 1rem;
      padding-top: 0.5rem;
    }
  }

  &__info {
    padding: 1rem;
    width: 100%;

    h3 {
      font-weight: var(--font-weight-semibold);
      color: var(--color-primary);
    }
  }

  &__drag {
    background-color: var(--color-primary);
    border-radius: 0 var(--border-radius-xs) var(--border-radius-xs) 0;
    width: 1.5rem;
    align-self: stretch;
    cursor: grab;

    &__icon {
      color: white;
    }
  }
}

@media only screen and (max-width: 768px) {
  .level-right {
    display: flex;
    align-items: center;
  }
}

@media only screen and (min-width: 950px) {
  .level-left {
    max-width: calc(100% - 2rem);
    width: calc(100% - 2rem);
  }

  .level-right {
    max-width: 2rem;
  }

  .level {
    align-items: stretch;
  }

  .level-right .level-item:not(:last-child) {
    margin-right: 1.2rem;
  }

  .card {
    &__info {
      max-width: 100%;
      width: 100%;
      display: unset;
    }
  }
}

@media only screen and (max-width: 949px) {
  .level,
  .level-left,
  .level-right {
    flex-direction: column;
    align-items: stretch;
  }

  .level-right {
    align-items: center;
  }

  .level-left .level-item:not(:last-child) {
    margin-right: 0;
  }

  .level-item:not(:last-child) {
    margin-bottom: 0.75rem;
  }

  .level-left + .level-right {
    margin-top: 1.5rem;
  }

  .card {
    &__drag {
      width: unset;
      height: 1.5rem;
      border-radius: 0 0 var(--border-radius-xs) var(--border-radius-xs);
    }
  }

  .center {
    width: 100%;
    height: calc(100% - var(--border-radius-xs));
  }
}

.clickArea {
  width: 100%;
}
</style>
