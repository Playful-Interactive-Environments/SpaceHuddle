<template>
  <el-form-item
    prop="stateMessage"
    :rules="[defaultFormRules.ruleStateMessage]"
  >
    <el-input v-model="formStateMessage" style="display: none" />
    <el-button
      native-type="submit"
      class=".gradient btn--fullwidth el-button--submit"
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
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;
}
</script>

<style lang="scss" scoped></style>
