import { defineAsyncComponent } from 'vue';
import config from './config.json';
import ModuleComponentType from '@/modules/ModuleComponentType';
import { RouteRecordRaw } from 'vue-router';

export const getAsyncDefaultModule = (componentType: string): any => {
  const module = config.none as any;
  if (module[componentType]) {
    return defineAsyncComponent(
      () => import(`@/modules/${module.path}/${module[componentType]}.vue`)
    );
  }
  return null;
};

export const getAsyncModule = (
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
      return getAsyncModule(componentType, taskType, 'default');
    }
  }
  return getAsyncDefaultModule(componentType);
};

const getDefaultModule = async (componentType: string): Promise<any> => {
  const module = config.none as any;
  if (module[componentType]) {
    let vue: any;
    await import(`@/modules/${module.path}/${module[componentType]}.vue`).then(
      (value) => {
        vue = value.default;
      }
    );
    return vue;
  }
  return null;
};

const getModule = async (
  componentType: string,
  taskType: string | null = null,
  moduleName = 'default'
): Promise<any> => {
  if (taskType) {
    const module = (config as any)[taskType][moduleName];
    if (module[componentType]) {
      let vue: any;
      await import(
        `@/modules/${module.path}/${module[componentType]}.vue`
      ).then((value) => {
        vue = value.default;
      });
      return vue;
    } else if (moduleName != 'default') {
      return await getModule(componentType, taskType, 'default');
    }
  }
  return await getDefaultModule(componentType);
};

export const getModuleConfig = (
  componentType: string,
  taskType: string | null = null,
  moduleName = 'default'
): any => {
  if (taskType) {
    const module = (config as any)[taskType][moduleName];
    if (module[componentType]) {
      return module;
    } else if (moduleName != 'default') {
      return getModuleConfig(componentType, taskType, 'default');
    }
  }
  return null;
};

export const hasModule = (
  componentType: string,
  taskType: string | null = null,
  moduleName = 'default'
): boolean => {
  if (getModuleConfig(componentType, taskType, moduleName)) {
    return true;
  }
  return false;
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
          await import(`@/modules/${module.path}/locales/${locale}.json`).then(
            (value) => {
              locales[taskKey][moduleKey] = value.default;
            }
          );
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

export const getRoutes = async (): Promise<Array<RouteRecordRaw>> => {
  const routes: Array<RouteRecordRaw> = [];
  let taskKey: string;
  let moduleKey: string;
  for (taskKey in config) {
    if (taskKey != 'none') {
      const taskType = (config as any)[taskKey];
      for (moduleKey in taskType) {
        const vue = await getModule(
          ModuleComponentType.PARTICIPANT,
          taskKey,
          moduleKey
        );
        if (vue) {
          routes.push(getRouteItem(taskKey, moduleKey, vue));
        }
      }
    } else {
      const vue = await getDefaultModule(ModuleComponentType.PARTICIPANT);
      if (vue) {
        routes.push(getRouteItem(taskKey, 'default', vue));
      }
    }
  }
  return routes;
};

const getRouteItem = (
  taskType: string,
  moduleName: string,
  vueComponent: any
): RouteRecordRaw => {
  return {
    path: `/task/${taskType}/${moduleName}/:taskId`,
    name: `participant-${taskType}-${moduleName}`,
    component: async () => vueComponent,
    meta: {
      requiresAuth: true,
      requiresParticipant: true,
    },
    props: (route) => ({ taskId: route.params.taskId }),
  };
};
