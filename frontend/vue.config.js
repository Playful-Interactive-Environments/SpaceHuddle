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
  chainWebpack: (config) => {
    config.module
      .rule('vue')
      .use('vue-loader')
      .tap((options) => ({
        ...options,
        compilerOptions: {
          isCustomElement: (tag) => {
            return [
              'container',
              'sprite',
              'graphics',
              'animated-sprite',
              'animatedsprite',
              'particle-container',
              'particlecontainer',
              'simple-rope',
              'simplerope',
              'game-object-test',
              'gameobjecttest',
              'sprite-converter',
              'spriteconverter',
              'particle-player',
              'particleplayer',
            ].includes(tag.toLowerCase());
          },
        },
      }));
  },
};
