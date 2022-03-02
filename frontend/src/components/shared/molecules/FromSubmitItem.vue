<template>
  <el-form-item
    prop="stateMessage"
    :rules="[defaultFormRules.ruleStateMessage]"
  >
    <el-input
      v-model="formStateMessage"
      class="hide"
      input-style="display: none"
    />
    <el-button
      native-type="submit"
      class="fullwidth el-button--submit"
      :disabled="disabled"
      :loading="disabled"
    >
      {{ $t(submitLabelKey) }}
    </el-button>
  </el-form-item>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';

@Options({
  components: {},
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class FromSubmitItem extends Vue {
  @Prop({ default: '' }) formStateMessage!: string;
  @Prop({ default: 'submit' }) submitLabelKey!: string;
  @Prop({ default: false }) disabled!: boolean;
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;
}
</script>

<style lang="scss" scoped></style>
