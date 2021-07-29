import { defineAsyncComponent } from 'vue';
import config from './config.json';

export const getDefaultModule = (componentType: string): any => {
  const module = config.none as any;
  return defineAsyncComponent(
    () => import(`@/modules/${module.path}/${module[componentType]}.vue`)
  );
};

export const getModule = (
  componentType: string,
  taskType: string | null = null,
  moduleName = 'default'
): any => {
  if (taskType) {
    const module = (config as any)[taskType][moduleName];
    if (module[componentType]) {
      return defineAsyncComponent(
        () => import(`@/modules/${module.path}/${module[componentType]}.vue`)
      );
    } else if (moduleName != 'default') {
      return getModule(componentType, taskType, 'default');
    }
  }
  return getDefaultModule(componentType);
};

export const getLocales = async (locale = 'en'): Promise<any> => {
  const locales: any = {};
  let taskKey: string;
  let moduleKey: string;
  for (taskKey in config) {
    if (taskKey != 'none') {
      locales[taskKey] = {};
      const taskType = (config as any)[taskKey];
      for (moduleKey in taskType) {
        const moduleType = taskType[moduleKey];
        const module = (config as any)[taskKey][moduleKey];
        if (module.locales && module.locales.includes(locale)) {
          await import(`@/modules/${module.path}/locales/${locale}.json`).then((value) => {
              locales[taskKey][moduleKey] = value.default;
          });
        }
      }
    } else {
      const module = (config as any)[taskKey];
      if (module.locales && module.locales.includes(locale)) {
        await import(`@/modules/none/locales/${locale}.json`).then((value) => {
          locales[taskKey] = value.default;
        });
      }
    }
  }
  return locales;
};
