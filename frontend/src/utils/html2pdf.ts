import html2pdf from 'html2pdf.js';

/* eslint-disable @typescript-eslint/no-explicit-any*/
export interface PDFOptions {
  margin: number | [number, number] | [number, number, number, number];
  filename: string;
  image: {
    type: string;
    quality: number;
  };
  enableLinks: boolean;
  html2canvas: any;
  jsPDF: any;
}

export function createPdf(pdfContent: HTMLElement, options: PDFOptions): any {
  return html2pdf().set(options).from(pdfContent);
}
