/*const sav = `$theme: "${process.env['VUE_APP_THEME']}";`;

module.exports = {
  css: {
    loaderOptions: {
      scss: {
        additionalData: sav,
      },
    },
  },
};*/

module.exports = {
  css: {
    loaderOptions: {
      sass: {
        additionalData: `
          @use "@/assets/themes/${process.env['VUE_APP_THEME']}.scss" as *;
        `,
      },
    },
  },
};
