import Pagination from '@/Components/Pagination';
import StatusBadge from '@/Components/StatusBadge';
import { Badge } from '@/Components/ui/badge';
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
import { MonitoringDisposition, Option, Paginator, Unit } from '@/types';
import { Head, Link, router, usePoll } from '@inertiajs/react';
import { Eye, ListChecks, Radar, RotateCcw, Search, Send, TriangleAlert } from 'lucide-react';

type Props = {
    dispositions: Paginator<MonitoringDisposition>;
    filters: Record<string, string>;
    statuses: Option[];
    units: Unit[];
    summary: {
        total: number;
        overdue: number;
        due_today: number;
        forwarded: number;
        active_assignees: number;
    };
};

export default function Monitor({ dispositions, filters, statuses, units, summary }: Props) {
    usePoll(20000, { only: ['dispositions', 'summary'] });

    const activeFilters = [
        filters.search ? `Cari: ${filters.search}` : null,
        filters.status ? `Status: ${statuses.find((status) => status.value === filters.status)?.label ?? filters.status}` : null,
        filters.unit_id ? `Unit: ${units.find((unit) => String(unit.id) === String(filters.unit_id))?.nama ?? filters.unit_id}` : null,
        filters.focus
            ? `Fokus: ${
                  {
                      overdue: 'Terlambat',
                      due_today: 'Jatuh tempo hari ini',
                      forwarded: 'Sudah diteruskan',
                  }[filters.focus] ?? filters.focus
              }`
            : null,
    ].filter(Boolean) as string[];

    function setFilter(name: string, value: string) {
        router.get(
            route('dispositions.monitor'),
            { ...filters, [name]: value },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function resetFilters() {
        router.get(route('dispositions.monitor'), {}, { preserveScroll: true, replace: true });
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p className="text-sm font-medium text-slate-500">Disposisi</p>
                        <h1 className="mt-1 text-2xl font-semibold tracking-normal">Monitor Disposisi</h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Pantau jalur aktif, deadline, unit yang sedang bergerak, dan rantai yang mulai terlambat.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline">
                            <Link href={route('dispositions.index')}>
                                <ListChecks className="h-4 w-4" />
                                Tindak Lanjut
                            </Link>
                        </Button>
                    </div>
                </div>
            }
        >
            <Head title="Monitor Disposisi" />

            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <SummaryCard
                    title="Rantai aktif"
                    value={summary.total}
                    hint="Root disposisi yang masih dipantau"
                    icon={Radar}
                    href={route('dispositions.monitor')}
                />
                <SummaryCard
                    title="Terlambat"
                    value={summary.overdue}
                    hint="Minimal satu node melewati deadline"
                    icon={TriangleAlert}
                    tone="danger"
                    href={route('dispositions.monitor', { focus: 'overdue' })}
                />
                <SummaryCard
                    title="Jatuh tempo hari ini"
                    value={summary.due_today}
                    hint="Butuh perhatian hari ini"
                    icon={TriangleAlert}
                    tone="warning"
                    href={route('dispositions.monitor', { focus: 'due_today' })}
                />
                <SummaryCard
                    title="Sudah diteruskan"
                    value={summary.forwarded}
                    hint="Rantai dengan child disposition"
                    icon={Send}
                    href={route('dispositions.monitor', { focus: 'forwarded' })}
                />
                <SummaryCard
                    title="Penerima aktif"
                    value={summary.active_assignees}
                    hint="Orang yang masih memegang aksi"
                    icon={ListChecks}
                    href={route('dispositions.monitor')}
                />
            </div>

            <Card className="mt-6">
                <CardHeader className="border-b border-slate-200 pb-4">
                    <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <CardTitle>Daftar Monitoring</CardTitle>
                            <p className="mt-1 text-sm text-slate-500">
                                Fokus default pada disposisi yang belum selesai. Data otomatis disegarkan tiap 20 detik.
                            </p>
                        </div>
                        <div className="grid gap-2 sm:grid-cols-2 xl:flex xl:min-w-[920px]">
                            <div className="relative sm:col-span-2 xl:w-80">
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <Input
                                    defaultValue={filters.search ?? ''}
                                    onChange={(event) => setFilter('search', event.target.value)}
                                    placeholder="Cari perihal, agenda, atau pengirim"
                                    className="pl-9"
                                />
                            </div>
                            <Select
                                value={filters.status ?? ''}
                                onChange={(event) => setFilter('status', event.target.value)}
                                className="xl:w-48"
                            >
                                <option value="">Aktif saja</option>
                                {statuses.map((status) => (
                                    <option key={status.value} value={status.value}>
                                        {status.label}
                                    </option>
                                ))}
                            </Select>
                            <Select
                                value={filters.unit_id ?? ''}
                                onChange={(event) => setFilter('unit_id', event.target.value)}
                                className="xl:w-56"
                            >
                                <option value="">Semua unit aktif</option>
                                {units.map((unit) => (
                                    <option key={unit.id} value={unit.id}>
                                        {unit.nama}
                                    </option>
                                ))}
                            </Select>
                            <Select
                                value={filters.focus ?? ''}
                                onChange={(event) => setFilter('focus', event.target.value)}
                                className="xl:w-44"
                            >
                                <option value="">Semua fokus</option>
                                <option value="overdue">Hanya terlambat</option>
                                <option value="due_today">Jatuh tempo hari ini</option>
                                <option value="forwarded">Sudah diteruskan</option>
                            </Select>
                            <Button type="button" variant="outline" onClick={resetFilters}>
                                <RotateCcw className="h-4 w-4" />
                                Reset
                            </Button>
                        </div>
                    </div>
                    {activeFilters.length > 0 && (
                        <div className="mt-3 flex flex-wrap items-center gap-2">
                            {activeFilters.map((filter) => (
                                <Badge key={filter} variant="secondary">
                                    {filter}
                                </Badge>
                            ))}
                            <Button type="button" variant="ghost" size="sm" onClick={resetFilters}>
                                Bersihkan filter
                            </Button>
                        </div>
                    )}
                </CardHeader>

                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-slate-50 hover:bg-slate-50">
                                <TableHead>Surat</TableHead>
                                <TableHead>Pengirim</TableHead>
                                <TableHead>Jalur Aktif</TableHead>
                                <TableHead>Deadline</TableHead>
                                <TableHead>Progress</TableHead>
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
                                    <TableCell>
                                        <div className="space-y-1 text-sm text-slate-600">
                                            <p className="font-medium text-slate-900">{disposition.sender?.name ?? '-'}</p>
                                            <p className="text-xs text-slate-500">{disposition.sender?.unit?.nama ?? '-'}</p>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div className="max-w-md space-y-2">
                                            <div className="flex flex-wrap gap-1.5">
                                                {disposition.active_recipients.length > 0 ? (
                                                    disposition.active_recipients.slice(0, 3).map((recipient) => (
                                                        <Badge key={`${disposition.id}-${recipient.id}`} variant="outline" className="max-w-full">
                                                            <span className="truncate">
                                                                {recipient.name}
                                                                {recipient.unit ? ` - ${recipient.unit}` : ''}
                                                            </span>
                                                        </Badge>
                                                    ))
                                                ) : (
                                                    <span className="text-sm text-slate-500">Tidak ada penerima aktif.</span>
                                                )}
                                                {disposition.active_recipients.length > 3 && (
                                                    <Badge variant="secondary">+{disposition.active_recipients.length - 3}</Badge>
                                                )}
                                            </div>
                                            <p className="text-xs text-slate-500">
                                                {disposition.node_count} node - {disposition.active_child_count} node masih aktif
                                            </p>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div className="space-y-1 text-sm">
                                            <p className="font-medium text-slate-900">{formatDate(disposition.latest_deadline)}</p>
                                            <div className="flex flex-wrap gap-1.5">
                                                {disposition.overdue_nodes_count > 0 && (
                                                    <Badge variant="danger">{disposition.overdue_nodes_count} terlambat</Badge>
                                                )}
                                                {disposition.due_today_nodes_count > 0 && (
                                                    <Badge variant="warning">{disposition.due_today_nodes_count} jatuh tempo hari ini</Badge>
                                                )}
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div className="space-y-1 text-sm text-slate-600">
                                            <p>{disposition.children?.length ? 'Sudah diteruskan' : 'Masih di level ini'}</p>
                                            <p className="text-xs text-slate-500">
                                                {disposition.active_units.map((unit) => unit.name).join(', ') || '-'}
                                            </p>
                                        </div>
                                    </TableCell>
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
                                    <TableCell colSpan={7} className="h-40 text-center text-slate-500">
                                        <div className="flex flex-col items-center gap-3 py-6">
                                            <p>Belum ada disposisi yang cocok untuk dipantau pada filter ini.</p>
                                            <Button type="button" variant="outline" onClick={resetFilters}>
                                                <RotateCcw className="h-4 w-4" />
                                                Reset Filter
                                            </Button>
                                        </div>
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

function SummaryCard({
    title,
    value,
    hint,
    icon: Icon,
    href,
    tone = 'default',
}: {
    title: string;
    value: number;
    hint: string;
    icon: typeof Radar;
    href?: string;
    tone?: 'default' | 'warning' | 'danger';
}) {
    const iconTone =
        tone === 'danger'
            ? 'bg-rose-50 text-rose-700'
            : tone === 'warning'
              ? 'bg-amber-50 text-amber-700'
              : 'bg-cyan-50 text-cyan-700';

    const content = (
        <section className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-cyan-300 hover:shadow-md">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="text-sm font-medium text-slate-500">{title}</p>
                    <p className="mt-2 text-3xl font-semibold text-slate-950">{value}</p>
                </div>
                <span className={`flex h-10 w-10 items-center justify-center rounded-lg ${iconTone}`}>
                    <Icon className="h-5 w-5" />
                </span>
            </div>
            <p className="mt-3 text-xs leading-5 text-slate-500">{hint}</p>
        </section>
    );

    if (!href) {
        return content;
    }

    return <Link href={href}>{content}</Link>;
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
