import { defineAsyncComponent } from 'vue';
import config from './config.json';
import ModuleComponentType from '@/modules/ModuleComponentType';
import { RouteRecordRaw } from 'vue-router';
import TaskType from '@/types/enum/TaskType';

/* eslint-disable @typescript-eslint/no-explicit-any*/

const isObject = (item) => {
  return item && typeof item === 'object' && !Array.isArray(item);
};

const mergeDeep = (target, ...sources) => {
  if (!sources.length) return target;
  const source = sources.shift();

  if (isObject(target) && isObject(source)) {
    for (const key in source) {
      if (isObject(source[key])) {
        if (!target[key])
          Object.assign(target, {
            [key]: {},
          });
        mergeDeep(target[key], source[key]);
      } else {
        Object.assign(target, {
          [key]: source[key],
        });
      }
    }
  }
  return mergeDeep(target, ...sources);
};

const readConfig = async (): Promise<any> => {
  let jsonResult: any = config;
  const configPath = process.env.VUE_APP_MODULE_CONFIG_PATH;
  if (configPath) {
    await fetch(configPath).then((res) => {
      jsonResult = res.json();
    });
    await Promise.all([config, jsonResult]).then((listOfConfigs) => {
      jsonResult = mergeDeep({}, listOfConfigs[0], listOfConfigs[1]);
    });
  }

  return jsonResult;
};

let moduleConfig: any = config;
let moduleConfigLoaded = false;
readConfig().then((config) => {
  moduleConfig = config;
  moduleConfigLoaded = true;
});

function until(conditionFunction) {
  const poll = (resolve) => {
    if (conditionFunction()) resolve();
    else setTimeout(() => poll(resolve), 400);
  };

  return new Promise(poll);
}

export const getAsyncTaskParameter = async (
  taskType: string | null = null
): Promise<any> => {
  await until(() => moduleConfigLoaded);
  if (taskType) {
    const module = moduleConfig[taskType];
    if (module && module.settings && module.settings.parameter) {
      return defineAsyncComponent(
        () =>
          import(
            `@/modules/${module.settings.path}/${module.settings.parameter}.vue`
          )
      );
    }
  }
  return null;
};

export const getEmptyComponent = (): any => {
  return defineAsyncComponent(() => import(`@/modules/empty.vue`));
};

export const getAsyncDefaultModule = async (
  componentType: string
): Promise<any> => {
  await until(() => moduleConfigLoaded);
  const module = moduleConfig.none as any;
  if (module[componentType]) {
    return defineAsyncComponent(
      () => import(`@/modules/${module.path}/${module[componentType]}.vue`)
    );
  }
  return null;
};

export const getAsyncModule = async (
  componentType: string,
  taskType: string | null = null,
  moduleName = 'default'
): Promise<any> => {
  await until(() => moduleConfigLoaded);
  if (taskType) {
    const module = moduleConfig[taskType][moduleName];
    if (module[componentType]) {
      return defineAsyncComponent(
        () => import(`@/modules/${module.path}/${module[componentType]}.vue`)
      );
    } else if (moduleName != 'default') {
      return await getAsyncModule(componentType, taskType, 'default');
    }
  }
  return await getAsyncDefaultModule(componentType);
};

const getDefaultModule = async (componentType: string): Promise<any> => {
  await until(() => moduleConfigLoaded);
  const module = moduleConfig.none as any;
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
  await until(() => moduleConfigLoaded);
  if (taskType) {
    const module = moduleConfig[taskType][moduleName];
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

export const getModuleConfig = async (
  componentType: string,
  taskType: string | null = null,
  moduleName = 'default'
): Promise<any> => {
  await until(() => moduleConfigLoaded);
  if (taskType) {
    const module = moduleConfig[taskType][moduleName];
    if (module[componentType]) {
      return module[componentType];
    } else if (moduleName != 'default') {
      return getModuleConfig(componentType, taskType, 'default');
    }
  }
  return null;
};

export const hasModule = async (
  componentType: string,
  taskType: string | null = null,
  moduleName = 'default'
): Promise<boolean> => {
  let hasModule = false;
  await getModuleConfig(componentType, taskType, moduleName).then(
    (result) => (hasModule = !!result)
  );
  return hasModule;
};

export const getLocales = async (locale = 'en'): Promise<any> => {
  await until(() => moduleConfigLoaded);
  const locales: any = {};
  for (const taskKey in moduleConfig) {
    if (taskKey != 'none') {
      locales[taskKey] = {};
      const taskType = moduleConfig[taskKey];
      for (const moduleKey in taskType) {
        const module = moduleConfig[taskKey][moduleKey];
        if (module.locales && module.locales.includes(locale)) {
          await import(`@/modules/${module.path}/locales/${locale}.json`).then(
            (value) => {
              locales[taskKey][moduleKey] = value.default;
            }
          );
        }
      }
    } else {
      const module = moduleConfig[taskKey];
      if (module.locales && module.locales.includes(locale)) {
        await import(`@/modules/none/locales/${locale}.json`).then((value) => {
          locales[taskKey] = value.default;
        });
      }
    }
  }
  return locales;
};

export const getEnumLocales = async (locale = 'en'): Promise<any> => {
  await until(() => moduleConfigLoaded);
  let locales: any = {};
  await import(`@/modules/locales/${locale}.json`)
    .then((value) => {
      locales = value.default;
    })
    .catch(() => {
      locales = {};
    });
  return locales;
};

export const getRoutes = async (): Promise<Array<RouteRecordRaw>> => {
  await until(() => moduleConfigLoaded);
  const routes: Array<RouteRecordRaw> = [];
  let taskKey: string;
  let moduleKey: string;
  for (taskKey in moduleConfig) {
    if (taskKey != 'none') {
      const taskType = moduleConfig[taskKey];
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

export const getModulesForTaskType = async (
  taskType: keyof typeof TaskType
): Promise<string[]> => {
  await until(() => moduleConfigLoaded);
  const taskTypeName = TaskType[taskType];
  const modules = moduleConfig[taskTypeName];
  const moduleNameList = Object.keys(modules) as string[];
  return moduleNameList.filter((obj) => obj !== 'settings');
};
