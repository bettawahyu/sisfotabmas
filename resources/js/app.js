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

// Forms and buttons with data-konfirmasi ask first in the shared dialog (components/konfirmasi.blade.php).
// Any DELETE form without its own message still gets a generic one.
Alpine.store('konfirmasi', {
    buka: false,
    pesan: '',
    bahaya: false,
    lanjut: null,
    tanya(pesan, bahaya, lanjut) {
        Object.assign(this, { buka: true, pesan, bahaya, lanjut });
    },
    setuju() {
        this.buka = false;
        this.lanjut?.();
    },
    batal() {
        this.buka = false;
        this.lanjut = null;
    },
});

document.addEventListener('submit', (event) => {
    const form = event.target;
    const tombol = event.submitter;
    const hapus = /^delete$/i.test(form.querySelector('input[name="_method"]')?.value ?? '');
    const pesan = tombol?.dataset.konfirmasi ?? form.dataset.konfirmasi
        ?? (hapus ? 'Data yang dihapus tidak bisa dikembalikan.' : null);

    if (! pesan || 'tanpaKonfirmasi' in form.dataset || form.dataset.terkonfirmasi) {
        return;
    }

    event.preventDefault();
    Alpine.store('konfirmasi').tanya(pesan, hapus, () => {
        form.dataset.terkonfirmasi = '1';
        form.requestSubmit(tombol ?? undefined);
        delete form.dataset.terkonfirmasi;
    });
}, true);

// Highlight the fields named in a failed validation and bring the first one into view.
window.tandaiKolomSalah = (kolom) => {
    const nama = (kunci) => kunci.split('.').map((bagian, i) => (i ? `[${bagian}]` : bagian)).join('');
    const ruas = kolom.flatMap((kunci) => [...document.querySelectorAll(
        [kunci, `${kunci}[]`, nama(kunci)].map((n) => `[name="${CSS.escape(n)}"]`).join(', '),
    )]).filter((el) => el.type !== 'hidden');

    ruas.forEach((el) => {
        el.setAttribute('aria-invalid', 'true');
        el.classList.add('border-red-500', 'ring-1', 'ring-red-500');
    });

    const pertama = ruas.find((el) => el.offsetParent !== null);
    if (pertama) {
        pertama.scrollIntoView({ behavior: 'smooth', block: 'center' });
        pertama.focus({ preventScroll: true });
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
