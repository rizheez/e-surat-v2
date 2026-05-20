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
import { Disposition, Option, PageProps, Paginator } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Download, Eye, Radar, RotateCcw, Search } from 'lucide-react';

type Props = {
    dispositions: Paginator<Disposition>;
    filters: Record<string, string>;
    statuses: Option[];
};

export default function Index({ dispositions, filters, statuses }: Props) {
    const { auth } = usePage<PageProps>().props;
    const canExportReports = auth.permissions.includes('export reports');

    function setFilter(name: string, value: string) {
        router.get(
            route('dispositions.index'),
            { ...filters, [name]: value },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function resetFilters() {
        router.get(route('dispositions.index'), {}, { preserveScroll: true, replace: true });
    }

    const exportUrl = route('reports.dispositions.xlsx', filters);
    const templateUrl = route('import-templates.dispositions.xlsx');

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-sm font-medium text-slate-500">Disposisi</p>
                        <h1 className="mt-1 text-2xl font-semibold tracking-normal">Tindak Lanjut Disposisi</h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Pantau instruksi, penerima, batas waktu, dan tindak lanjut.
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
                        <Button asChild variant="outline">
                            <Link href={route('dispositions.monitor')}>
                                <Radar className="h-4 w-4" />
                                Monitor Disposisi
                            </Link>
                        </Button>
                    </div>
                </div>
            }
        >
            <Head title="Tindak Lanjut Disposisi" />

            <Card>
                <CardHeader className="border-b border-slate-200 pb-4">
                    <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <CardTitle>Daftar Tindak Lanjut</CardTitle>
                            <p className="mt-1 text-sm text-slate-500">
                                {dispositions.total} disposisi tercatat dalam sistem.
                            </p>
                        </div>
                        <div className="grid gap-2 sm:grid-cols-2 lg:flex lg:min-w-[620px]">
                            <div className="relative sm:col-span-2 lg:w-80">
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <Input
                                    defaultValue={filters.search ?? ''}
                                    onChange={(event) => setFilter('search', event.target.value)}
                                    placeholder="Cari perihal atau agenda"
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
                                <TableHead>Surat</TableHead>
                                <TableHead>Pemberi</TableHead>
                                <TableHead>Penerima</TableHead>
                                <TableHead>Batas Waktu</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="w-[120px] text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {dispositions.data.map((disposition) => (
                                <TableRow key={disposition.id}>
                                    <TableCell>
                                        <div className="max-w-xl">
                                            <p className="font-medium text-slate-950">{disposition.incomingLetter?.perihal}</p>
                                            <p className="mt-1 text-xs text-slate-500">{disposition.incomingLetter?.nomor_agenda}</p>
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-slate-600">{disposition.sender?.name}</TableCell>
                                    <TableCell className="text-slate-600">
                                        {disposition.recipients?.map((item) => item.recipient?.name).join(', ') || '-'}
                                    </TableCell>
                                    <TableCell className="text-slate-600">{formatDate(disposition.batas_waktu)}</TableCell>
                                    <TableCell>
                                        <StatusBadge value={disposition.status} />
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex justify-end">
                                            <Button asChild variant="outline" size="sm">
                                                <Link href={route('dispositions.show', disposition.id)}>
                                                    <Eye className="h-4 w-4" />
                                                    Detail
                                                </Link>
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                            {dispositions.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={6} className="h-40 text-center text-slate-500">
                                        Belum ada disposisi yang sesuai filter.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                    <Pagination meta={dispositions} />
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}

function formatDate(value?: string | null) {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
}
