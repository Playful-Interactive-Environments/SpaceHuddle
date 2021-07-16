<template>
  <ModuleDetailView :sessionId="sessionId" :taskId="taskId">
    <!-- TODO: selection module content -->
    Selection content works!
  </ModuleDetailView>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import ModuleDetailView from "@/components/moderator/organisms/ModuleDetailView.vue";

@Options({
  components: {
    ModuleDetailView,
  },
})
export default class ModeratorSelection extends Vue {
  @Prop({ default: '' }) readonly sessionId!: string;
  @Prop({ default: '' }) readonly taskId!: string;
  errors: string[] = [];
}
</script>