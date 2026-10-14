import Alpine from 'alpinejs';
import feather from 'feather-icons';

window.Alpine = Alpine;

Alpine.start();

// Turn every <i data-feather="..."> into its inline SVG icon.
const gambarIkon = () => feather.replace({ 'stroke-width': 1.75 });

document.addEventListener('DOMContentLoaded', async () => {
    // DataTables (with jQuery) is only loaded on pages that have a data table.
    if (document.querySelector('table[data-tabel-data]')) {
        const { pasangTabelData } = await import('./tabel-data');
        pasangTabelData(gambarIkon);
    }
    gambarIkon();
});
