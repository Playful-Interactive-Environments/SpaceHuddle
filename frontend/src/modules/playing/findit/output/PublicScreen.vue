<template>
  {{ $t('general.noPublicView') }}
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';

@Options({
  components: {},
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PublicScreen extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
}
</script>

<style lang="scss" scoped></style>
