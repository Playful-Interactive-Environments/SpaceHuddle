import { createI18n } from 'vue-i18n';
import de from '@/locales/de.json';
import en from '@/locales/en.json';
import { getEnumLocales, getLocales } from '@/modules';

/* eslint-disable @typescript-eslint/no-explicit-any*/

const addModuleLocales = async (locale = 'en', dict: any): Promise<any> => {
  dict.module = await getLocales(locale);
  const generalLocales = await getEnumLocales(locale);
  if (generalLocales != null) {
    if (!('module' in dict)) dict.module = {};
    dict.module.general = generalLocales;
  }
  return dict;
};

addModuleLocales('de', de);
addModuleLocales('en', en);

const messages = { de, en };

const availableLocales = Object.keys(messages);

const language = navigator.language.substring(0, 2);
const locale =
  language && availableLocales.includes(language)
    ? language
    : process.env.VUE_APP_I18N_LOCALE || 'en';

const i18n = createI18n({
  locale: locale,
  fallbackLocale: process.env.VUE_APP_I18N_FALLBACK_LOCALE || 'en',
  messages,
  availableLocales,
  legacy: false,
});

export default i18n;
