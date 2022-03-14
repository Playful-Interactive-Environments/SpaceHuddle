<template>
  <el-container class="module-content">
    <el-header>
      <TaskInfo
        :taskId="taskId"
        :modules="[module]"
        :is-participant="true"
        :shortenDescription="false"
        :auth-header-typ="EndpointAuthorisationType.PARTICIPANT"
      />
    </el-header>
    <el-main class="el-main__overflow">
      <slot />
    </el-main>
    <el-footer v-if="!!$slots.footer" class="fixed">
      <slot name="footer"></slot>
    </el-footer>
  </el-container>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import TaskInfo from '@/components/shared/molecules/TaskInfo.vue';
import { Prop } from 'vue-property-decorator';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';

@Options({
  components: {
    TaskInfo,
  },
})
export default class ParticipantModuleDefaultContainer extends Vue {
  @Prop({ required: true }) taskId!: string;
  @Prop({ default: 'default' }) module!: string;

  EndpointAuthorisationType = EndpointAuthorisationType;
}
</script>

<style lang="scss" scoped>
.right {
  position: absolute;
  top: 1rem;
  right: 2rem;
}

.module-content {
  background-color: white;
  color: #1d2948;
  flex-grow: 1;
  justify-content: space-between;
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
  margin: 0;
  padding: 1rem 2rem;
  max-width: inherit;
  position: relative;
}

.el-affix::v-deep {
  .el-affix--fixed {
    background-color: white;
    padding: 1rem 0;
    margin-bottom: -1.3rem;
  }

  .el-form {
    margin-bottom: 2.3rem;
  }

  .el-form-item--default {
    margin-bottom: 0;
  }

  .media {
    flex-direction: row;
    width: 100%;
  }
}

.fixed {
  position: sticky;
  background-color: white;
  margin-bottom: -1rem;
  padding: 1rem 0;
}
</style>
