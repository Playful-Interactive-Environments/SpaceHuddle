<template>
  <el-card
    shadow="never"
    v-on:click="changeSelection"
    :class="{
      card__selected: isSelected,
      card__new: isNew,
      card__inappropriate: isInappropriate,
      card__duplicate: isDuplicate,
      draggable: isDraggable,
    }"
    :body-style="{ padding: '0px' }"
  >
    <img v-if="idea.image" :src="idea.image" class="card__image" alt="" />
    <img
      v-if="idea.link && !idea.image"
      :src="idea.link"
      class="card__image"
      alt=""
    />
    <div class="card__text">
      <div class="card__title">
        <span
          ref="title"
          :class="{ threeLineText: cutLongTexts || limitedTextLength }"
        >
          {{ hasKeywords ? idea.keywords : idea.description }}
        </span>
        <span class="actions">
          <slot name="action"></slot>
          <span class="state" v-if="showState">
            <font-awesome-icon
              v-if="stateIcon"
              :icon="stateIcon"
              :style="{ color: stateColor }"
            />
          </span>
          <el-dropdown
            v-if="isEditable"
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
                <el-dropdown-item command="delete">
                  <font-awesome-icon icon="trash" />
                </el-dropdown-item>
                <el-dropdown-item command="state" v-if="canChangeState">
                  <el-dropdown
                    class="card__menu"
                    placement="top-start"
                    v-on:command="menuItemSelected($event)"
                  >
                    <font-awesome-icon icon="star" />
                    <template #dropdown>
                      <el-dropdown-menu>
                        <el-dropdown-item
                          v-for="ideaState in IdeaStates"
                          :key="ideaState"
                          :command="ideaState"
                        >
                          {{ $t(`enum.ideaState.${ideaState}`) }}
                        </el-dropdown-item>
                      </el-dropdown-menu>
                    </template>
                  </el-dropdown>
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </span>
      </div>
      <div
        ref="description"
        v-if="hasKeywords && idea.description"
        class="card__content"
        :class="{ threeLineText: cutLongTexts || limitedTextLength }"
      >
        {{ idea.description }}
      </div>
      <div v-if="limitedTextLength" class="collapse">
        <font-awesome-icon
          icon="angle-down"
          @click="limitedTextLength = !limitedTextLength"
        />
      </div>
    </div>
    <IdeaSettings
      v-if="isEditable"
      v-model:show-modal="showSettings"
      :idea="idea"
      :authHeaderTyp="authHeaderTyp"
    />
  </el-card>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as ideaService from '@/services/idea-service';
import { Idea } from '@/types/api/Idea';
import IdeaStates from '@/types/enum/IdeaStates';
import IdeaSettings from '@/components/moderator/organisms/settings/IdeaSettings.vue';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';

@Options({
  components: { IdeaSettings },
  emits: ['ideaDeleted', 'update:isSelected'],
})
export default class IdeaCard extends Vue {
  @Prop() idea!: Idea;
  @Prop({ default: true }) isEditable!: boolean;
  @Prop({ default: true }) canChangeState!: boolean;
  @Prop({ default: true }) showState!: boolean;
  @Prop({ default: false }) isSelectable!: boolean;
  @Prop({ default: false, reactive: true }) isSelected!: boolean;
  @Prop({ default: false }) isDraggable!: boolean;
  @Prop({ default: false }) cutLongTexts!: boolean;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  showSettings = false;
  limitedTextLength = false;

  IdeaStates = IdeaStates;

  @Watch('idea.keywords', { immediate: true })
  @Watch('idea.description', { immediate: true })
  onIdeaChanged(): void {
    setTimeout(() => {
      const titleControl: HTMLElement = this.$refs.title as HTMLElement;
      if (titleControl) {
        if (titleControl.clientHeight > 70) this.limitedTextLength = true;
      }
      const descriptionControl: HTMLElement = this.$refs.description as HTMLElement;
      if (descriptionControl) {
        if (descriptionControl.clientHeight > 70) this.limitedTextLength = true;
      }
    }, 100);
  }

  get stateIcon(): string {
    switch (IdeaStates[this.idea.state]) {
      case IdeaStates.NEW:
        return 'star';
      case IdeaStates.INAPPROPRIATE:
        return 'times';
      case IdeaStates.DUPLICATE:
        return 'clone';
      case IdeaStates.HANDLED:
        return 'check';
    }
    return '';
  }

  get stateColor(): string {
    switch (IdeaStates[this.idea.state]) {
      case IdeaStates.NEW:
        return '#f1be3a';
      case IdeaStates.INAPPROPRIATE:
        return '#fe6e5d';
      case IdeaStates.DUPLICATE:
        return '#01cf9e';
      case IdeaStates.HANDLED:
        return '#999999';
    }
    return '#999999';
  }

  get isNew(): boolean {
    if (this.idea) return IdeaStates[this.idea.state] == IdeaStates.NEW;
    return false;
  }

  get isInappropriate(): boolean {
    if (this.idea)
      return IdeaStates[this.idea.state] == IdeaStates.INAPPROPRIATE;
    return false;
  }

  get isDuplicate(): boolean {
    if (this.idea) return IdeaStates[this.idea.state] == IdeaStates.DUPLICATE;
    return false;
  }

  changeSelection(): void {
    if (this.isSelectable) {
      this.$emit('update:isSelected', !this.isSelected);
    }
  }

  get hasKeywords(): boolean {
    return !!(this.idea.keywords && this.idea.keywords.length > 0);
  }

  async deleteIdea(): Promise<void> {
    ideaService.deleteIdea(this.idea.id, this.authHeaderTyp).then(() => {
      this.$emit('ideaDeleted', this.idea.id);
    });
  }

  menuItemSelected(command: string): void {
    switch (command) {
      case 'edit':
        this.showSettings = true;
        break;
      case 'delete':
        this.deleteIdea();
        break;
      case 'state':
        break;
      case IdeaStates.NEW:
      case IdeaStates.HANDLED:
      case IdeaStates.DUPLICATE:
      case IdeaStates.INAPPROPRIATE:
        this.idea.state = command;
        ideaService
          .putIdea(this.idea.id, this.idea, this.authHeaderTyp)
          .then((idea) => (this.idea.state = idea.state));
        break;
    }
  }
}
</script>

<style lang="scss" scoped>
.actions::v-deep > * {
  margin-left: 0.5rem;
}

.draggable {
  cursor: grab;
}

.card {
  &__selected {
    background-color: var(--color-blue);
  }

  &__new {
    border-color: var(--el-color-warning);
  }

  &__inappropriate {
    border-color: var(--el-color-error);
  }

  &__duplicate {
    border-color: var(--color-mint);
  }

  &__content {
    color: var(--color-darkblue-light);
  }

  &__text {
    padding: 14px;
  }

  &__title {
    align-items: center;
  }
}

.state {
  background-color: var(--color-background-gray);
  border-radius: 50%;
  aspect-ratio: 1;
  height: 1.5rem;
  display: inline-flex;

  svg {
    margin: auto;
  }
}

.collapse {
  display: flex;
  justify-content: end;
}
</style>
