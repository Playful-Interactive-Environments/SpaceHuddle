import { defineAsyncComponent } from 'vue';
import config from './config.json';
import ModuleComponentType from '@/modules/ModuleComponentType';
import { RouteRecordRaw } from 'vue-router';
import TaskType from '@/types/enum/TaskType';
import { ModuleType } from '@/types/enum/ModuleType';

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

interface ModuleConfigItem {
  publicScreen?: string;
  participant?: string;
  moderatorContent?: string;
  locales?: string;
  icon?: string;
  type?: string;
  input?: string;
  syncPublicParticipant?: boolean;
  path: string;
  parameter?: string;
}

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
  moduleName: string | string[] = 'default'
): Promise<any> => {
  await until(() => moduleConfigLoaded);
  if (taskType) {
    const moduleList = Array.isArray(moduleName) ? moduleName : [moduleName];
    if (moduleList.length > 0) {
      const modules: { order: number; module: any }[] = [];
      let moduleIndex = 0;
      while (moduleIndex < moduleList.length) {
        const module = moduleConfig[taskType][moduleList[moduleIndex]];
        if (module[componentType])
          modules.push({
            order:
              module.type == 'addOn'
                ? 1
                : !module.path.endsWith('default')
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
      } else if (moduleName != 'default') {
        return await getAsyncModule(componentType, taskType, 'default');
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
  moduleName = 'default',
  includeFallback = true
): Promise<any> => {
  await until(() => moduleConfigLoaded);
  if (taskType) {
    const module = moduleConfig[taskType][moduleName];
    if (module[componentType]) {
      return module[componentType];
    } else if (moduleName != 'default' && includeFallback) {
      return getModuleConfig(componentType, taskType, 'default');
    }
  }
  return null;
};

export const hasModule = async (
  componentType: string,
  taskType: string | null = null,
  moduleName = 'default',
  includeFallback = true
): Promise<boolean> => {
  let hasModule = false;
  await getModuleConfig(
    componentType,
    taskType,
    moduleName,
    includeFallback
  ).then((result) => (hasModule = !!result));
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
    return (
      value.taskType.toUpperCase() === this.taskType.toUpperCase() &&
      value.moduleName.toUpperCase() === this.moduleName.toUpperCase()
    );
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
      modules.push({
        taskType: taskType,
        moduleName: moduleName,
        module: moduleConfig[taskTypeName][moduleName],
      });
    }
  }
  return modules
    .filter((obj) => obj.module.type && obj.module.type === moduleType)
    .map((obj) => new ModuleTask(obj.taskType, obj.moduleName));
};
