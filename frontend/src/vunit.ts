import { delay } from '@/utils/wait';
import { v4 as uuidv4 } from 'uuid';

/* eslint-disable @typescript-eslint/no-explicit-any*/

interface DomWatchElement {
  domElement: HTMLElement;
  callback: (targetWidth: number, targetHeight: number) => void;
  callbackReset: (() => void) | null;
  calculateStyleSize: boolean;
}
const watchDomElement: { [key: string]: DomWatchElement } = {};
//const resizeObserver: ResizeObserver = new ResizeObserver(resizeObserverHandler);

async function appViewport(): Promise<void> {
  const doc = document.documentElement;
  const viewport = document.querySelector('meta[name=viewport]');
  /*console.log(
    screen.height,
    screen.availHeight,
    window.innerHeight,
    window.outerHeight,
    screen,
    window
  );
  if (window.innerHeight > window.outerHeight) {
    doc.style.setProperty('--app-height', '0');
    doc.style.setProperty('--app-width', '0');
    document.body.style.height = '0';
    //doc.style.removeProperty('--app-height');
    //doc.style.removeProperty('--app-width');
    //document.body.style.removeProperty('height');
    if (viewport) {
      viewport.removeAttribute('content');
    }
    await delay(500);
  }*/
  //const width = screen.availWidth; // window.outerWidth; // window.innerWidth;
  //const height = screen.availHeight; // window.outerHeight; // window.innerHeight;
  const width =
    window.innerWidth < window.outerWidth
      ? window.innerWidth
      : window.outerWidth;
  const height =
    window.innerHeight < window.outerHeight
      ? window.innerHeight
      : window.outerHeight;

  doc.style.setProperty('--app-height', `${height}px`);
  doc.style.setProperty('--app-width', `${width}px`);
  document.body.style.height = `${height}px`;

  setTimeout(() => {
    if (viewport) {
      let viewportContent = `height=${height},width=${width}`;
      const props = viewport.getAttribute('content')?.split(',');
      if (props) {
        for (const prop of props) {
          if (!prop.startsWith('width=') && !prop.startsWith('height=')) {
            viewportContent += `,${prop}`;
          }
        }
      }
      viewport.setAttribute('content', viewportContent);
    }
    calculateFillSize();
  }, 300);
}

const setViewportVariables = (): void => {
  window.addEventListener('resize', appViewport);
  appViewport();
};

export default setViewportVariables;

/*function resizeObserverHandler(entryList: ResizeObserverEntry[]): void {
  for (const entry of entryList) {
    const watcher = Object.values(watchDomElement).find(
      (item) => item.domElement === entry.target
    );
    if (watcher)
      watcher.callback(entry.contentRect.width, entry.contentRect.height);
  }
}*/

async function calculateFillSize(): Promise<void> {
  for (const element of Object.values(watchDomElement)) {
    if (element.calculateStyleSize) {
      delete (element.domElement as any).style.width;
      delete (element.domElement as any).style.height;
      (element.domElement as any).style.height = '0';
    }
    if (element.callbackReset) element.callbackReset();
  }
  await delay(500);
  for (const element of Object.values(watchDomElement)) {
    calculateElementFillSize(element);
  }
}

async function calculateElementFillSize(
  element: DomWatchElement
): Promise<void> {
  const parent = element.domElement.parentElement;
  if (parent) {
    const targetWidth = parent.offsetWidth;
    const targetHeight = parent.offsetHeight;
    let childHeight = 0;
    if (parent.children.length > 1) {
      await delay(100);
      childHeight =
        element.domElement.getBoundingClientRect().top -
        parent.getBoundingClientRect().top;
    }
    if (element.calculateStyleSize) {
      (element.domElement as any).style.width = `${targetWidth}px`;
      (element.domElement as any).style.height = `${
        targetHeight - childHeight
      }px`;
    }
    element.callback(targetWidth, targetHeight - childHeight);
  }
}
export function registerDomElement(
  domElement: HTMLElement,
  callback: (targetWidth: number, targetHeight: number) => void,
  delayTime = 0,
  calculateStyleSize = true,
  callbackReset: (() => void) | null = null
): string {
  if (!domElement) return '';
  //resizeObserver.observe(domElement);
  const key = uuidv4();
  const element = {
    domElement: domElement,
    callback: callback,
    callbackReset: callbackReset,
    calculateStyleSize: calculateStyleSize,
  };
  watchDomElement[key] = element;
  if (delayTime) {
    delay(delayTime).then(() => {
      calculateElementFillSize(element);
    });
  } else calculateElementFillSize(element);
  return key;
}

export function unregisterDomElement(key: string): void {
  if (watchDomElement[key]) {
    //resizeObserver.unobserve(watchDomElement[key].domElement);
    delete watchDomElement[key];
  }
}
