import { defineAsyncComponent } from 'vue';
import surveyConfig from '@/modules/information/personalityTest/data/survey.json';

/* eslint-disable @typescript-eslint/no-explicit-any*/

export async function importCalculation(test: string): Promise<any> {
  const config = surveyConfig[test].calculation;
  const input = await import(
    `@/modules/information/personalityTest/types/${config.file}`
  );
  return new input[config.class]();
}

export async function importComponent(folder: string, test: string): Promise<any> {
  return await defineAsyncComponent(
    () =>
      import(
        `@/modules/information/personalityTest/organisms/${folder}/${test}.vue`
      )
  );
}
