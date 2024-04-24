import { apiExecuteGet } from '@/services/config-service';

/* eslint-disable @typescript-eslint/no-explicit-any*/
let config: any | null = null;
async function loadConfig(): Promise<void> {
  config = await apiExecuteGet<{ [key: string]: string }>('assets/config.json');
}
await loadConfig();

export function getString(name: string): string {
  return config[name] || process.env[name];
}

export function getNumber(name: string): number {
  return JSON.parse(config[name] || process.env[name]);
}

export function getBool(name: string): boolean {
  return JSON.parse(config[name] || process.env[name]);
}
