const appViewport = () => {
  const doc = document.documentElement;
  doc.style.setProperty('--app-height', `${window.innerHeight}px`);
  doc.style.setProperty('--app-width', `${window.innerWidth}px`);
  document.body.style.height = `${window.innerHeight}px`;

  setTimeout(() => {
    const viewport = document.querySelector('meta[name=viewport]');
    if (viewport) {
      /*viewport.setAttribute(
        'content',
        `height=${screen.height}px,width=${screen.width}px,initial-scale=1.0`
      );*/
      viewport.setAttribute(
        'content',
        `height=${window.innerHeight}px,width=${window.innerWidth}px,initial-scale=1.0`
      );
    }
  }, 300);
};

const setViewportVariables = (): void => {
  window.addEventListener('resize', appViewport);
  appViewport();
};

export default setViewportVariables;
