const appViewport = () => {
  const doc = document.documentElement;
  doc.style.setProperty('--app-height', `${window.innerHeight}px`);
  doc.style.setProperty('--app-width', `${window.innerWidth}px`);
  document.body.style.height = `${window.innerHeight}px`;
};

const setViewportVariables = (): void => {
  window.addEventListener('resize', appViewport);
  appViewport();
};

export default setViewportVariables;
