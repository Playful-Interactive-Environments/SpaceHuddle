import { ValidationRule, ValidationRules } from '@/types/ui/ValidationRule';
import app from '@/main';
import ValidationMethods from '@/types/ui/ValidationMethods';

/* eslint-disable @typescript-eslint/no-explicit-any*/

const translateMessageKey = (messageKey: string): string | undefined => {
  if (app)
    return app.config.globalProperties.$t(`error.vuelidate.${messageKey}`);
  return undefined;
};

const ruleRequired: ValidationRule = {
  required: true,
  message: () => translateMessageKey('required'),
  trigger: 'blur',
};

const ruleEmail: ValidationRule = {
  type: 'email',
  message: () => translateMessageKey('email'),
  trigger: 'blur',
};

const ruleUrl: ValidationRule = {
  type: 'url',
  message: () => translateMessageKey('url'),
  trigger: 'blur',
};

const ruleNumber: ValidationRule = {
  type: 'number',
  message: () => translateMessageKey('number'),
  trigger: 'blur',
};

const ruleDate: ValidationRule = {
  type: 'date',
  message: () => translateMessageKey('date'),
  trigger: 'blur',
};

const ruleToShort = (min: number): ValidationRule => {
  return {
    min: min,
    message: () => `${translateMessageKey('toShort')} ${min}`,
    trigger: 'blur',
  };
};

const ruleToLong = (max: number): ValidationRule => {
  return {
    max: max,
    message: () => `${translateMessageKey('toLong')} ${max}`,
    trigger: ['blur', 'change'],
  };
};

const rulePassword: ValidationRule = {
  type: 'string',
  pattern: /^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).*$/,
  message: () => translateMessageKey('wrongPasswordSyntax'),
  trigger: 'blur',
};

const validateMatch = (
  rule: ValidationRules,
  value: string,
  callback: (...errors) => boolean
): boolean => {
  if (value == (rule as any).match) {
    callback();
    return true;
  } else {
    callback(new Error(value));
    return false;
  }
};

const ruleMatch = (
  matchString: string,
  messageKey = 'passwordNotMatch'
): ValidationRule => {
  return {
    validator: validateMatch,
    match: matchString,
    message: () => translateMessageKey(messageKey),
    trigger: 'blur',
  };
};

const validateRequiredId = (
  rule: ValidationRules,
  value: any,
  callback: (...errors) => boolean
): boolean => {
  if (!(rule as any).ifCondition || value) {
    callback();
    return true;
  } else {
    callback(new Error(value));
    return false;
  }
};

const ruleRequiredIf = (
  ifCondition: boolean,
  messageKey = 'required'
): ValidationRule => {
  return {
    validator: validateRequiredId,
    ifCondition: ifCondition,
    message: () => translateMessageKey(messageKey),
    trigger: 'blur',
  };
};

const ruleKeyword: ValidationRule = {
  required: true,
  message: () => translateMessageKey('keywordsRequired'),
  trigger: 'blur',
};

const ruleSelection: ValidationRule = {
  required: true,
  message: () => translateMessageKey('noSelection'),
  trigger: ['blur', 'change'],
};

const validateStateMessage = (
  rule: ValidationRules,
  value: string,
  callback: (...errors) => boolean
): boolean => {
  if (value.length == 0) {
    callback();
    return true;
  } else {
    callback(new Error(value));
    return false;
  }
};

const ruleStateMessage: ValidationRule = {
  validator: validateStateMessage,
  trigger: 'change',
};

const triggerValidate = (
  rule: ValidationRules,
  value: string,
  callback: (...errors) => boolean
): boolean => {
  const form = (rule as any).form();
  const triggerRule = (rule as any).triggerRule as string;
  const triggerOnlyIfSet = (rule as any).triggerOnlyIfSet as boolean;
  if (triggerOnlyIfSet) {
    const triggerValue = form.fieldValue(triggerRule);
    if (value !== '' && triggerValue !== '') {
      form?.validateField(triggerRule);
    }
  } else {
    form?.validateField(triggerRule);
  }
  callback();
  return true;
};

const ruleTrigger = (
  form: () => ValidationMethods,
  triggerRule = 'stateMessage',
  triggerOnlyIfSet = true
): ValidationRule => {
  return {
    validator: triggerValidate,
    form: form,
    triggerRule: triggerRule,
    triggerOnlyIfSet: triggerOnlyIfSet,
    trigger: 'blur',
  };
};

export interface ValidationRuleDefinition {
  ruleRequired: ValidationRule;
  ruleEmail: ValidationRule;
  ruleUrl: ValidationRule;
  ruleNumber: ValidationRule;
  ruleDate: ValidationRule;
  ruleToShort: (min: number) => ValidationRule;
  ruleToLong: (max: number) => ValidationRule;
  rulePassword: ValidationRule;
  ruleMatch: (match: string, message: string) => ValidationRule;
  ruleRequiredIf: (ifCondition: boolean, message: string) => ValidationRule;
  ruleKeyword: ValidationRule;
  ruleSelection: ValidationRule;
  ruleStateMessage: ValidationRule;
  ruleTrigger: (
    form: () => ValidationMethods,
    triggerRule: string,
    triggerOnlyIfSet: boolean
  ) => ValidationRule;
}

export const defaultFormRules: ValidationRuleDefinition = {
  ruleRequired: ruleRequired,
  ruleEmail: ruleEmail,
  ruleUrl: ruleUrl,
  ruleNumber: ruleNumber,
  ruleDate: ruleDate,
  ruleToShort: ruleToShort,
  ruleToLong: ruleToLong,
  rulePassword: rulePassword,
  ruleMatch: ruleMatch,
  ruleRequiredIf: ruleRequiredIf,
  ruleKeyword: ruleKeyword,
  ruleSelection: ruleSelection,
  ruleStateMessage: ruleStateMessage,
  ruleTrigger: ruleTrigger,
};
