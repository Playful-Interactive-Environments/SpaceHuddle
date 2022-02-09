<template>
  <div
    :class="{ 'module-info--centered': isParticipant }"
    class="module-info"
    :style="{ '--module-color': TaskTypeColor[type] }"
  >
    <el-breadcrumb separator="|" class="module-info__type">
      <el-breadcrumb-item>{{ $t(`enum.taskType.${type}`) }}</el-breadcrumb-item>
      <el-breadcrumb-item v-for="module in modules" :key="module">
        {{ $t(`module.${type}.${module}.description.title`) }}
      </el-breadcrumb-item>
    </el-breadcrumb>
    <h3
      :class="{
        'heading--regular': isParticipant,
        shortDescription: shortenDescription,
      }"
      class="module-info__title"
    >
      {{ title }}
    </h3>
    <p
      class="module-info__description"
      :class="{ shortDescription: shortenDescription }"
    >
      {{ description }}
    </p>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import TaskType from '@/types/enum/TaskType';
import TaskTypeColor from '@/types/TaskTypeColor';

@Options({
  components: {},
})
export default class TaskInfo extends Vue {
  @Prop({ default: TaskType.BRAINSTORMING }) type!: TaskType;
  @Prop({ default: '' }) title!: string;
  @Prop({ default: '' }) description!: string;
  @Prop({ default: [] }) modules!: string[];
  @Prop({ default: false }) isParticipant!: boolean;
  @Prop({ default: true }) shortenDescription!: boolean;

  TaskTypeColor = TaskTypeColor;
  TaskType = TaskType;
}
</script>

<style lang="scss" scoped>
@import '~@/assets/styles/breakpoints.scss';

.module-info {
  margin-top: 2rem;
  flex-grow: 1;
  font-size: var(--font-size-small);

  @include md {
    //max-width: 60%;
  }

  &__type {
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--module-color);
  }

  &__title {
    font-weight: var(--font-weight-bold);
    margin: 0.2rem 0 0.1rem;
  }

  &__description {
    margin-top: 0.5rem;
    line-height: 1.2;
    text-align: justify;
    white-space: pre-line;

    @include md {
      margin-top: 0;
      line-height: 1.3;
    }
  }

  &--centered {
    //text-align: center;
    max-width: 100%;
    font-size: var(--font-size-default);
    margin-bottom: 1em;
    line-height: 1.75em;

    span {
      line-height: 3em;
    }
  }
}

.shortDescription {
  display: -webkit-box;
  line-clamp: 1;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-align: left;
}

.el-breadcrumb::v-deep {
  .el-breadcrumb__inner,
  .el-breadcrumb__item:last-child .el-breadcrumb__inner,
  .el-breadcrumb__separator {
    color: unset;
  }

  .el-breadcrumb__item {
    float: unset;
  }
}
</style>
