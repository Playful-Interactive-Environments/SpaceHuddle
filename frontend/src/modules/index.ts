import { defineAsyncComponent } from 'vue';
import config from './config.json';
import ModuleComponentType from '@/modules/ModuleComponentType';
import { RouteRecordRaw } from 'vue-router';
import TaskType from '@/types/enum/TaskType';
import { ModuleType } from '@/types/enum/ModuleType';
import { until } from '@/utils/wait';

/* eslint-disable @typescript-eslint/no-explicit-any*/

const defaultModuleName = 'default';

const hiddenModuleList = JSON.parse(process.env.VUE_APP_HIDDEN_MODULES);

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

interface ModuleConfigItem {
  publicScreen?: string;
  participant?: string;
  moderatorContent?: string;
  locales?: string;
  icon?: string;
  iconPrefix?: string;
  type?: string;
  input?: string;
  syncPublicParticipant?: boolean;
  finishManuel?: boolean;
  path: string;
  parameter?: string;
}

let moduleConfig: any = config;
let moduleConfigLoaded = false;
readConfig().then((config) => {
  moduleConfig = config;
  moduleConfigLoaded = true;
});

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
  moduleName: string | string[] = defaultModuleName,
  useAddOns = true
): Promise<any> => {
  await until(() => moduleConfigLoaded);
  if (taskType) {
    const moduleList = Array.isArray(moduleName) ? moduleName : [moduleName];
    if (moduleList.length > 0) {
      const modules: { order: number; module: any }[] = [];
      let moduleIndex = 0;
      let fallback = defaultModuleName;
      while (moduleIndex < moduleList.length) {
        const module = moduleConfig[taskType][moduleList[moduleIndex]];
        if (module.fallback) fallback = module.fallback;
        if (module[componentType] && (useAddOns || module.type !== 'addOn'))
          modules.push({
            order:
              module.type == 'addOn'
                ? 1
                : !module.path.endsWith(defaultModuleName)
                ? 2
                : 3,
            module: module,
          });
        moduleIndex++;
      }
      if (modules.length > 0) {
        const module = modules.sort((a, b) => a.order - b.order)[0].module;
        return defineAsyncComponent(
          () => import(`@/modules/${module.path}/${module[componentType]}.vue`)
        );
      } else if (moduleName != defaultModuleName) {
        return await getAsyncModule(componentType, taskType, fallback);
      }
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
  moduleName = defaultModuleName
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
    } else if (moduleName != defaultModuleName) {
      return await getModule(
        componentType,
        taskType,
        getFallback(taskType, moduleName)
      );
    }
  }
  return await getDefaultModule(componentType);
};

const getFallback = (
  taskType: string | null = null,
  moduleName = defaultModuleName
): string => {
  if (taskType) {
    const module = moduleConfig[taskType][moduleName];
    return module && module.fallback && module.fallback !== moduleName
      ? module.fallback.toString()
      : defaultModuleName;
  }
  return defaultModuleName;
};

export const getModuleConfig = async (
  componentType: string,
  taskType: string | null = null,
  moduleName = defaultModuleName,
  includeFallback = true
): Promise<any> => {
  await until(() => moduleConfigLoaded);
  if (taskType) {
    const module = moduleConfig[taskType][moduleName];
    if (Object.hasOwn(module, componentType)) {
      return module[componentType];
    } else if (moduleName != defaultModuleName && includeFallback) {
      return getModuleConfig(
        componentType,
        taskType,
        getFallback(taskType, moduleName)
      );
    }
  }
  return null;
};

export const hasModule = async (
  componentType: string,
  taskType: string | null = null,
  moduleName = defaultModuleName,
  includeFallback = true
): Promise<boolean> => {
  return getModuleConfig(
    componentType,
    taskType,
    moduleName,
    includeFallback
  ).then((result) => !!result);
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
          if (module.sub_locales) {
            const list = module.sub_locales
              .split(',')
              .map((item) => item.trim());
            for (const item of list) {
              if (item.length > 0) {
                await import(
                  `@/modules/${module.path}/locales/${item}/${locale}.json`
                ).then((value) => {
                  locales[taskKey][moduleKey][item] = value.default;
                });
              }
            }
          }
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
        routes.push(getRouteItem(taskKey, defaultModuleName, vue));
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

export class ModuleTask {
  taskType: string;
  moduleName: string;

  constructor(taskType: string, moduleName: string) {
    this.taskType = taskType;
    this.moduleName = moduleName;
  }

  get key(): string {
    return `${this.taskType}-${this.moduleName}`;
  }

  static createEmpty(): ModuleTask {
    return new ModuleTask('', '');
  }

  eq(value: ModuleTask): boolean {
    const taskTypeEq =
      value.taskType && this.taskType
        ? value.taskType.toUpperCase() === this.taskType.toUpperCase()
        : value.taskType === this.taskType;
    const moduleNameEq =
      value.moduleName && this.moduleName
        ? value.moduleName.toUpperCase() === this.moduleName.toUpperCase()
        : value.moduleName === this.moduleName;
    return taskTypeEq && moduleNameEq;
  }

  like(taskType: string | undefined, moduleName: string): boolean {
    if (taskType)
      return (
        taskType.toUpperCase() === this.taskType.toUpperCase() &&
        moduleName.toUpperCase() === this.moduleName.toUpperCase()
      );
    return false;
  }
}

export const getModulesForTaskType = async (
  taskTypes: (keyof typeof TaskType)[],
  moduleType: string = ModuleType.MAIN
): Promise<ModuleTask[]> => {
  await until(() => moduleConfigLoaded);
  const modules: {
    taskType: string;
    moduleName: string;
    module: ModuleConfigItem;
  }[] = [];
  for (const taskType of taskTypes) {
    const taskTypeName = TaskType[taskType.toUpperCase()];
    for (const moduleName in moduleConfig[taskTypeName]) {
      const moduleParameter = moduleConfig[taskTypeName][moduleName];
      const usable =
        !!moduleParameter &&
        (!Object.hasOwn(moduleParameter, 'usable') || moduleParameter.usable);
      const searchName = `${taskType.toLowerCase()}:${moduleName}`;
      const active = !hiddenModuleList.includes(searchName);
      if (usable && active) {
        modules.push({
          taskType: taskType,
          moduleName: moduleName,
          module: moduleParameter,
        });
      }
    }
  }
  return modules
    .filter((obj) => obj.module.type && obj.module.type === moduleType)
    .map((obj) => new ModuleTask(obj.taskType, obj.moduleName));
};
