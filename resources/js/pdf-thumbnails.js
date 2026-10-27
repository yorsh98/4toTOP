import { getDocument, GlobalWorkerOptions } from 'pdfjs-dist';
// Bundled by Vite as a .js worker: web servers often serve .mjs with a MIME type browsers reject for workers.
import PdfWorker from 'pdfjs-dist/build/pdf.worker.min.mjs?worker';

GlobalWorkerOptions.workerPort = new PdfWorker();

const THUMBNAIL_WIDTH = 140;

/**
 * Draws a thumbnail of every page of a local PDF file, in the browser: the server never renders them.
 * Calls onPage({ page, src }) as each one is ready and returns the page count.
 */
export async function renderThumbnails(file, maxPages, onPage) {
    const loading = getDocument({ data: await file.arrayBuffer() });
    try {
        return await render(await loading.promise, maxPages, onPage);
    } finally {
        await loading.destroy();
    }
}

async function render(pdf, maxPages, onPage) {
    if (pdf.numPages > maxPages) {
        throw new Error(`Organizar acepta PDF de hasta ${maxPages} páginas; este tiene ${pdf.numPages}.`);
    }

    for (let number = 1; number <= pdf.numPages; number++) {
        const page = await pdf.getPage(number);
        const viewport = page.getViewport({ scale: THUMBNAIL_WIDTH / page.getViewport({ scale: 1 }).width });
        const canvas = document.createElement('canvas');
        canvas.width = Math.ceil(viewport.width);
        canvas.height = Math.ceil(viewport.height);
        await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
        onPage({ page: number, src: canvas.toDataURL('image/jpeg', 0.7) });
        page.cleanup();
    }

    return pdf.numPages;
}
