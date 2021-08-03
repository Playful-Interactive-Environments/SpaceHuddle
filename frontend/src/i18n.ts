import { createI18n } from 'vue3-i18n';
import de from '@/locales/de.json';
import en from '@/locales/en.json';
import { Messages } from 'vue3-i18n/src/types';
import { getEnumLocales, getLocales } from '@/modules';

const addModuleLocales = async (locale = 'en', dict: any): Promise<any> => {
  dict.module = await getLocales(locale);
  const enumLocales = await getEnumLocales(locale);
  if (enumLocales != null) {
    if (!('enum' in dict))
      dict.enum = {};
    dict.enum.moduleType = enumLocales;
  }
  return dict;
};

addModuleLocales('de', de);
addModuleLocales('en', en);

const i18n = createI18n({
  locale: process.env.VUE_APP_I18N_LOCALE || 'en',
  fallbackLocale: process.env.VUE_APP_I18N_FALLBACK_LOCALE || 'en',
  messages: { de: de, en: en },
});

i18n.t2 = (key: string, fallback_message: string, itemContent: [] | null): string => {
  let translation = i18n.t(key) as string;

  if (translation.length == 0) {
    if (fallback_message) translation = fallback_message;
    const keyParts = key.split('.');
    if (keyParts.length > 0) translation = keyParts[keyParts.length - 1];
  }

  if (itemContent && translation.length > 0) {
    let contentIndex: any;
    for (contentIndex in itemContent) {
      translation = translation.replace(new RegExp("\\{" + contentIndex + "\\}", 'g'), itemContent[contentIndex]);
    }
  }

  return translation;
};

i18n.containsKey = (key: string): boolean => {
  const recursiveRetrieve = (chain: string[], messages: Messages): boolean => {
    if (
      !messages[chain[0]] ||
      messages[chain[0]] === '' ||
      typeof messages[chain[0]] !== 'string'
    ) {
      return false;
    } else if (chain.length === 1) {
      return true;
    } else {
      return recursiveRetrieve(chain.slice(1), messages[chain[0]]);
    }
  };

  return recursiveRetrieve(key.split('.'), i18n.en);
};

i18n.translateWithFallback = (item: string, itemContent: [] | null, prefix = ''): string => {
  const translateParts = item.split(':');
  if (translateParts.length > 1) {
    const translateCode = translateParts[0];
    const fallbackMessage = translateParts[1].trim();
    item = i18n.t2(`${prefix}${translateCode}`, fallbackMessage, itemContent);
  } else {
    item = i18n.t2(`${prefix}${item}`, item, itemContent);
  }
  return item;
};

i18n.translateList = (list: string[]): string[] => {
  list.forEach((item: string, index: number) => {
    list[index] = i18n.translateWithFallback(item);
  });
  return list;
};

export default i18n;
