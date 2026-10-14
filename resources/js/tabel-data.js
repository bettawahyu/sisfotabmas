import DataTable from 'datatables.net-dt';

const bahasa = {
    search: '',
    searchPlaceholder: 'Cari di tabel...',
    lengthMenu: 'Tampilkan _MENU_ baris',
    info: 'Baris _START_ sampai _END_ dari _TOTAL_',
    infoEmpty: 'Tidak ada data',
    infoFiltered: '(disaring dari _MAX_ baris)',
    zeroRecords: 'Tidak ada data yang cocok',
    emptyTable: 'Belum ada data',
    paginate: { first: '«', last: '»', next: '›', previous: '‹' },
    aria: { orderable: 'Urutkan kolom ini', orderableReverse: 'Balik urutan kolom ini' },
};

/**
 * Enhance every table marked with data-tabel-data: search, sorting and paging in the browser.
 * The "empty" placeholder row from @forelse is dropped and its text becomes DataTables' own empty message.
 */
export function pasangTabelData(setelahDigambar) {
    document.querySelectorAll('table[data-tabel-data]').forEach((tabel) => {
        const baris = tabel.tBodies[0]?.rows ?? [];
        let kosong = bahasa.emptyTable;

        if (baris.length === 1 && baris[0].cells.length === 1 && baris[0].cells[0].colSpan > 1) {
            kosong = baris[0].cells[0].textContent.trim() || kosong;
            baris[0].remove();
        }

        // Columns without a heading (actions) are not sortable or searchable.
        const kolomAksi = [...tabel.tHead.rows[0].cells]
            .map((sel, i) => (sel.textContent.trim() === '' ? i : null))
            .filter((i) => i !== null);

        const tabelData = new DataTable(tabel, {
            language: { ...bahasa, emptyTable: kosong },
            pageLength: Number(tabel.dataset.tabelBaris ?? 10),
            order: tabel.dataset.tabelUrut === undefined ? [] : [[Number(tabel.dataset.tabelUrut), 'asc']],
            columnDefs: [{ targets: kolomAksi, orderable: false, searchable: false }],
            autoWidth: false,
        });

        tabelData.on('draw', setelahDigambar);
    });
}
