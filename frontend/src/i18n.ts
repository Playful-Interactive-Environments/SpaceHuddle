import { createI18n, I18n } from 'vue-i18n';
import de from '@/locales/de.json';
import en from '@/locales/en.json';
import { getEnumLocales, getLocales } from '@/modules';
import _ from 'lodash';

/* eslint-disable @typescript-eslint/no-explicit-any*/

const addModuleLocales = async (locale = 'en', dict: any): Promise<any> => {
  dict.module = await getLocales(locale);
  const generalLocales = await getEnumLocales(locale);
  if (generalLocales != null) {
    if (!('module' in dict)) dict.module = {};
    dict.module.general = generalLocales;
  }
  if (process.env.VUE_APP_THEME !== 'default') {
    await import(
      `@/locales/themes/${process.env.VUE_APP_THEME}/${locale}.json`
    ).then((value) => {
      dict = _.merge(dict, value);
    });
  }
  return dict;
};

await addModuleLocales('de', de);
await addModuleLocales('en', en);

const messages = { de, en };

const availableLocales = Object.keys(messages);

const language = navigator.language.substring(0, 2);
const locale =
  language && availableLocales.includes(language)
    ? language
    : process.env.VUE_APP_I18N_LOCALE || 'en';

export const defaultI18n = (): I18n => {
  return createI18n({
    locale: locale,
    fallbackLocale: process.env.VUE_APP_I18N_FALLBACK_LOCALE || 'en',
    messages,
    availableLocales,
    legacy: false,
  });
};

export const customI18n = (language: string | null): I18n => {
  return createI18n({
    locale: language ?? locale,
    fallbackLocale: process.env.VUE_APP_I18N_FALLBACK_LOCALE || 'en',
    messages,
    availableLocales,
    legacy: false,
  });
};
