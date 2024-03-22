<template>
  <div class="vue-html2pdf">
    <section class="layout-container">
      <section
        class="content-wrapper"
        :style="`width: ${pdfContentWidth};`"
        ref="pdfContent"
      >
        <slot name="pdf-content" />
      </section>
    </section>

    <transition name="transition-anim">
      <section class="pdf-preview" v-if="pdfFile">
        <button @click.self="closePreview()">&times;</button>

        <iframe :src="pdfFile" width="100%" height="100%" />
      </section>
    </transition>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import { createPdf, PDFOptions } from '@/utils/html2pdf';

@Options({
  components: {},
})

// adaption of vue3-html2pdf due to critical import errors
// https://github.com/raiblaze/vue3-html2pdf/blob/main/src/vue3-html2pdf.vue
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PDFConverter extends Vue {
  @Prop({ default: true }) enableDownload!: boolean;
  @Prop({ default: false }) previewModal!: boolean;
  @Prop() paginateElementsByHeight!: number;
  @Prop({ default: `${new Date().getTime()}` }) filename!: string;
  @Prop({ default: 2 }) pdfQuality!: number;
  @Prop({ default: 'a4' }) pdfFormat!: string;
  @Prop({ default: 'portrait' }) pdfOrientation!: string;
  @Prop({ default: '800px' }) pdfContentWidth!: string;
  @Prop() htmlToPdfOptions!: PDFOptions;

  hasAlreadyParsed = false;
  pdfFile = null;

  async generatePdf(): Promise<void> {
    this.createPaginationOfElements();
    await this.downloadPdf();
  }

  createPaginationOfElements(): void {
    if (!this.hasAlreadyParsed && this.paginateElementsByHeight) {
      const parentElement = this.$refs.pdfContent as HTMLElement;
      const ArrOfContentChildren = Array.from(parentElement.children);

      let childrenHeight = 0;

      for (const childElement of ArrOfContentChildren) {
        const elementFirstClass = childElement.classList[0];
        const isPageBreakClass = elementFirstClass === 'html2pdf__page-break';
        if (isPageBreakClass) {
          childrenHeight = 0;
        } else {
          const elementHeight = childElement.clientHeight;

          const elementComputedStyle =
            (childElement as any).currentStyle ||
            window.getComputedStyle(childElement);
          const elementMarginTopBottom =
            parseInt(elementComputedStyle.marginTop) +
            parseInt(elementComputedStyle.marginBottom);

          const elementHeightWithMargin =
            elementHeight + elementMarginTopBottom;

          if (childrenHeight + elementHeight < this.paginateElementsByHeight) {
            childrenHeight += elementHeightWithMargin;
          } else {
            const section = document.createElement('div');
            section.classList.add('html2pdf__page-break');
            parentElement.insertBefore(section, childElement);

            childrenHeight = elementHeightWithMargin;
          }
        }
      }

      this.hasAlreadyParsed = true;
    }
  }

  async downloadPdf(): Promise<void> {
    const pdfContent = this.$refs.pdfContent as HTMLElement;
    const options = this.setOptions();

    const pdf = createPdf(pdfContent, options);
    let pdfBlobUrl = null;

    if (this.previewModal) {
      this.pdfFile = await pdf.output('bloburl');
      pdfBlobUrl = this.pdfFile;
    }

    if (this.enableDownload) {
      pdfBlobUrl = await pdf.save().output('bloburl');
    }

    if (pdfBlobUrl) {
      const res = await fetch(pdfBlobUrl);
      const blobFile = await res.blob();
      this.$emit('hasDownloaded', blobFile);
    }
  }

  setOptions(): PDFOptions {
    if (this.htmlToPdfOptions !== undefined && this.htmlToPdfOptions !== null) {
      return this.htmlToPdfOptions;
    }

    return {
      margin: 0,

      filename: `${this.filename}.pdf`,

      image: {
        type: 'jpeg',
        quality: 0.98,
      },

      enableLinks: false,

      html2canvas: {
        scale: this.pdfQuality,
        useCORS: true,
      },

      jsPDF: {
        unit: 'in',
        format: this.pdfFormat,
        orientation: this.pdfOrientation,
      },
    };
  }

  closePreview() {
    this.pdfFile = null;
  }
}
</script>

<style lang="scss" scoped>
.vue-html2pdf {
  .layout-container {
    position: fixed;
    width: 100vw;
    height: 100vh;
    left: -100vw;
    top: 0;
    z-index: -9999;
    background: rgba(95, 95, 95, 0.8);
    display: flex;
    justify-content: center;
    align-items: flex-start;
    overflow: auto;
  }

  .pdf-preview {
    position: fixed;
    width: 65%;
    min-width: 600px;
    height: 80vh;
    top: 100px;
    z-index: 9999999;
    left: 50%;
    transform: translateX(-50%);
    border-radius: 5px;
    box-shadow: 0 0 10px #00000048;

    button {
      position: absolute;
      top: -20px;
      left: -15px;
      width: 35px;
      height: 35px;
      background: #555;
      border: 0;
      box-shadow: 0 0 10px #00000048;
      border-radius: 50%;
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      cursor: pointer;
    }

    iframe {
      border: 0;
    }
  }

  .transition-anim-enter-active,
  .transition-anim-leave-active {
    transition: opacity 0.3s ease-in;
  }

  .transition-anim-enter,
  .transition-anim-leave-to {
    opacity: 0;
  }
}
</style>
