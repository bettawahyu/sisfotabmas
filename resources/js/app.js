import Alpine from 'alpinejs';
import feather from 'feather-icons';

window.Alpine = Alpine;

// The desktop sidebar can be collapsed to its icons; each browser keeps its own choice.
const layarLebar = () => window.matchMedia('(min-width: 768px)').matches;
window.sidebarCiut = () => layarLebar() && document.documentElement.classList.contains('sidebar-ciut');
window.aturSidebar = (ciut = ! window.sidebarCiut()) => {
    document.documentElement.classList.toggle('sidebar-ciut', ciut);
    try {
        localStorage.setItem('sidebar-ciut', ciut ? '1' : '0');
    } catch (e) {
        // Storage can be blocked; the sidebar still toggles for this page.
    }
};

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
