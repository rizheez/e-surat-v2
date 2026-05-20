import Pagination from '@/Components/Pagination';
import StatusBadge from '@/Components/StatusBadge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Select } from '@/Components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { IncomingLetter, LetterNature, Option, PageProps, Paginator } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Download, Eye, FileText, Pencil, Plus, RotateCcw, Search, Send } from 'lucide-react';

type Props = {
    letters: Paginator<IncomingLetter>;
    filters: Record<string, string>;
    natures: LetterNature[];
    statuses: Option[];
};

export default function Index({ letters, filters, natures, statuses }: Props) {
    const { auth } = usePage<PageProps>().props;
    const canCreateIncomingLetter = auth.permissions.includes('create incoming letters');
    const canCreateDisposition = auth.permissions.includes('create disposition');
    const canUpdateIncomingLetter = auth.permissions.includes('update incoming letters');
    const canExportReports = auth.permissions.includes('export reports');

    function setFilter(name: string, value: string) {
        router.get(
            route('incoming-letters.index'),
            { ...filters, [name]: value },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function resetFilters() {
        router.get(route('incoming-letters.index'), {}, { preserveScroll: true, replace: true });
    }

    const exportUrl = route('reports.incoming-letters.xlsx', filters);
    const templateUrl = route('import-templates.incoming-letters.xlsx');

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-sm font-medium text-slate-500">Penerimaan Surat</p>
                        <h1 className="mt-1 text-2xl font-semibold tracking-normal">Penerimaan Surat</h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Kelola agenda penerimaan, file PDF, dan proses disposisi.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline">
                            <a href={templateUrl}>
                                <Download className="h-4 w-4" />
                                Download Template
                            </a>
                        </Button>
                        {canExportReports && (
                            <Button asChild variant="outline">
                                <a href={exportUrl}>
                                    <Download className="h-4 w-4" />
                                    Export Excel
                                </a>
                            </Button>
                        )}
                        {canCreateIncomingLetter && (
                            <Button asChild>
                                <Link href={route('incoming-letters.create')}>
                                    <Plus className="h-4 w-4" />
                                    Catat Surat
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>
            }
        >
            <Head title="Penerimaan Surat" />

            <Card>
                <CardHeader className="border-b border-slate-200 pb-4">
                    <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <CardTitle>Daftar Surat Diterima</CardTitle>
                            <p className="mt-1 text-sm text-slate-500">
                                {letters.total} surat tercatat dalam sistem.
                            </p>
                        </div>
                        <div className="grid gap-2 sm:grid-cols-2 xl:grid-cols-5">
                            <div className="relative sm:col-span-2 lg:w-80">
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <Input
                                    defaultValue={filters.search ?? ''}
                                    onChange={(event) => setFilter('search', event.target.value)}
                                    placeholder="Cari agenda, nomor, asal, perihal"
                                    className="pl-9"
                                />
                            </div>
                            <Select
                                value={filters.status ?? ''}
                                onChange={(event) => setFilter('status', event.target.value)}
                                className="lg:w-44"
                            >
                                <option value="">Semua status</option>
                                {statuses.map((status) => (
                                    <option key={status.value} value={status.value}>
                                        {status.label}
                                    </option>
                                ))}
                            </Select>
                            <Select
                                value={filters.sifat_id ?? ''}
                                onChange={(event) => setFilter('sifat_id', event.target.value)}
                                className="lg:w-44"
                            >
                                <option value="">Semua sifat</option>
                                {natures.map((nature) => (
                                    <option key={nature.id} value={nature.id}>
                                        {nature.nama}
                                    </option>
                                ))}
                            </Select>
                            <Input
                                type="date"
                                value={filters.date_from ?? ''}
                                onChange={(event) => setFilter('date_from', event.target.value)}
                            />
                            <Input
                                type="date"
                                value={filters.date_to ?? ''}
                                onChange={(event) => setFilter('date_to', event.target.value)}
                            />
                            <Button type="button" variant="outline" onClick={resetFilters}>
                                <RotateCcw className="h-4 w-4" />
                                Reset
                            </Button>
                        </div>
                    </div>
                </CardHeader>

                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-slate-50 hover:bg-slate-50">
                                <TableHead className="w-[130px]">Agenda</TableHead>
                                <TableHead>Surat</TableHead>
                                <TableHead>Asal</TableHead>
                                <TableHead>Sifat</TableHead>
                                <TableHead>Tanggal Terima</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="w-[220px] text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {letters.data.map((letter) => (
                                <TableRow key={letter.id}>
                                    <TableCell className="font-semibold">{letter.nomor_agenda}</TableCell>
                                    <TableCell>
                                        <div className="max-w-xl">
                                            <p className="font-medium text-slate-950">{letter.perihal}</p>
                                            <p className="mt-1 text-xs text-slate-500">{letter.nomor_surat}</p>
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-slate-600">{letter.asal_surat}</TableCell>
                                    <TableCell className="text-slate-600">{letter.nature?.nama ?? '-'}</TableCell>
                                    <TableCell className="text-slate-600">{formatDate(letter.tanggal_diterima)}</TableCell>
                                    <TableCell>
                                        <StatusBadge value={letter.status} />
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex justify-end gap-2">
                                            <Button asChild variant="outline" size="sm">
                                                <Link href={route('incoming-letters.show', letter.id)}>
                                                    <Eye className="h-4 w-4" />
                                                    Detail
                                                </Link>
                                            </Button>
                                            {letter.has_file && letter.file_url && (
                                                <Button asChild variant="ghost" size="sm">
                                                    <a href={letter.file_url} target="_blank" rel="noreferrer">
                                                        <FileText className="h-4 w-4" />
                                                        PDF
                                                    </a>
                                                </Button>
                                            )}
                                            {canUpdateIncomingLetter && letter.status !== 'diarsipkan' && (
                                                <Button asChild variant="ghost" size="sm">
                                                    <Link href={route('incoming-letters.edit', letter.id)}>
                                                        <Pencil className="h-4 w-4" />
                                                        Edit
                                                    </Link>
                                                </Button>
                                            )}
                                            {canCreateDisposition && letter.status === 'baru' && (
                                                <Button asChild variant="ghost" size="sm">
                                                    <Link href={route('dispositions.create', { incoming_letter_id: letter.id })}>
                                                        <Send className="h-4 w-4" />
                                                        Disposisi
                                                    </Link>
                                                </Button>
                                            )}
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                            {letters.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={7} className="h-40 text-center text-slate-500">
                                        Belum ada penerimaan surat yang sesuai filter.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                    <Pagination meta={letters} />
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}

function formatDate(value: string) {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
}
