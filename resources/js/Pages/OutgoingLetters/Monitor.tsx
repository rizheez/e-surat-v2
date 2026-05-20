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
import { Option, OutgoingLetter, Paginator, User } from '@/types';
import { Head, Link, router, usePoll } from '@inertiajs/react';
import { CheckCircle2, Clock3, Eye, PanelTopOpen, RotateCcw, Search, TriangleAlert, Undo2 } from 'lucide-react';

type Props = {
    letters: Paginator<OutgoingLetter>;
    filters: Record<string, string>;
    statuses: Option[];
    signatories: User[];
    creators: Pick<User, 'id' | 'name'>[];
    summary: {
        total: number;
        pending: number;
        revision: number;
        approved: number;
        stuck: number;
    };
};

export default function Monitor({ letters, filters, statuses, signatories, creators, summary }: Props) {
    usePoll(20000, { only: ['letters', 'summary'] });

    const activeFilters = [
        filters.search ? `Cari: ${filters.search}` : null,
        filters.status ? `Status: ${statuses.find((status) => status.value === filters.status)?.label ?? filters.status}` : null,
        filters.focus ? `Fokus: ${filters.focus === 'stuck' ? 'Hanya tertahan' : filters.focus}` : null,
        filters.signatory_id
            ? `Penandatangan: ${signatories.find((item) => String(item.id) === String(filters.signatory_id))?.name ?? filters.signatory_id}`
            : null,
        filters.creator_id
            ? `Pembuat: ${creators.find((item) => String(item.id) === String(filters.creator_id))?.name ?? filters.creator_id}`
            : null,
    ].filter(Boolean) as string[];

    function setFilter(name: string, value: string) {
        router.get(
            route('outgoing-letters.monitor'),
            { ...filters, [name]: value },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function resetFilters() {
        router.get(route('outgoing-letters.monitor'), {}, { preserveScroll: true, replace: true });
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p className="text-sm font-medium text-slate-500">Penyusunan Surat</p>
                        <h1 className="mt-1 text-2xl font-semibold tracking-normal">Monitor Persetujuan</h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Lihat antrean persetujuan, surat yang tertahan, revisi yang belum ditutup, dan approval yang baru selesai.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline">
                            <Link href={route('outgoing-letters.approvals')}>
                                <PanelTopOpen className="h-4 w-4" />
                                Inbox Persetujuan
                            </Link>
                        </Button>
                        <Button asChild variant="outline">
                            <Link href={route('outgoing-letters.index')}>
                                <Eye className="h-4 w-4" />
                                Penyusunan Surat
                            </Link>
                        </Button>
                    </div>
                </div>
            }
        >
            <Head title="Monitor Persetujuan" />

            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <SummaryCard
                    title="Total dipantau"
                    value={summary.total}
                    hint="Surat generated web pada jalur approval"
                    icon={PanelTopOpen}
                    href={route('outgoing-letters.monitor')}
                />
                <SummaryCard
                    title="Menunggu"
                    value={summary.pending}
                    hint="Masih menunggu tanda tangan"
                    icon={Clock3}
                    tone="warning"
                    href={route('outgoing-letters.monitor', { status: 'menunggu_persetujuan' })}
                />
                <SummaryCard
                    title="Perlu revisi"
                    value={summary.revision}
                    hint="Perlu aksi dari pembuat surat"
                    icon={Undo2}
                    tone="danger"
                    href={route('outgoing-letters.monitor', { status: 'perlu_revisi' })}
                />
                <SummaryCard
                    title="Disetujui"
                    value={summary.approved}
                    hint="Sudah lolos approval"
                    icon={CheckCircle2}
                    tone="success"
                    href={route('outgoing-letters.monitor', { status: 'disetujui' })}
                />
                <SummaryCard
                    title="Tertahan >2 hari"
                    value={summary.stuck}
                    hint="Approval yang mulai macet"
                    icon={TriangleAlert}
                    tone="danger"
                    href={route('outgoing-letters.monitor', { focus: 'stuck' })}
                />
            </div>

            <Card className="mt-6">
                <CardHeader className="border-b border-slate-200 pb-4">
                    <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <CardTitle>Antrean Persetujuan</CardTitle>
                            <p className="mt-1 text-sm text-slate-500">
                                Fokus default pada status approval aktif. Data otomatis disegarkan tiap 20 detik.
                            </p>
                        </div>
                        <div className="grid gap-2 sm:grid-cols-2 xl:flex xl:min-w-[980px]">
                            <div className="relative sm:col-span-2 xl:w-80">
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <Input
                                    defaultValue={filters.search ?? ''}
                                    onChange={(event) => setFilter('search', event.target.value)}
                                    placeholder="Cari nomor, tujuan, atau perihal"
                                    className="pl-9"
                                />
                            </div>
                            <Select
                                value={filters.status ?? ''}
                                onChange={(event) => setFilter('status', event.target.value)}
                                className="xl:w-52"
                            >
                                <option value="">Semua status approval</option>
                                {statuses.map((status) => (
                                    <option key={status.value} value={status.value}>
                                        {status.label}
                                    </option>
                                ))}
                            </Select>
                            <Select
                                value={filters.focus ?? ''}
                                onChange={(event) => setFilter('focus', event.target.value)}
                                className="xl:w-44"
                            >
                                <option value="">Semua fokus</option>
                                <option value="stuck">Hanya tertahan</option>
                            </Select>
                            <Select
                                value={filters.signatory_id ?? ''}
                                onChange={(event) => setFilter('signatory_id', event.target.value)}
                                className="xl:w-56"
                            >
                                <option value="">Semua penandatangan</option>
                                {signatories.map((signatory) => (
                                    <option key={signatory.id} value={signatory.id}>
                                        {signatory.name}
                                    </option>
                                ))}
                            </Select>
                            <Select
                                value={filters.creator_id ?? ''}
                                onChange={(event) => setFilter('creator_id', event.target.value)}
                                className="xl:w-56"
                            >
                                <option value="">Semua pembuat</option>
                                {creators.map((creator) => (
                                    <option key={creator.id} value={creator.id}>
                                        {creator.name}
                                    </option>
                                ))}
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
                                <TableHead className="w-[180px]">Nomor</TableHead>
                                <TableHead>Surat</TableHead>
                                <TableHead>Pembuat</TableHead>
                                <TableHead>Penandatangan</TableHead>
                                <TableHead>Umur Approval</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="w-[120px] text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {letters.data.map((letter) => (
                                <TableRow key={letter.id}>
                                    <TableCell className="font-semibold">{letter.nomor_surat_keluar}</TableCell>
                                    <TableCell>
                                        <div className="max-w-xl">
                                            <p className="font-medium text-slate-950">{letter.perihal}</p>
                                            <p className="mt-1 line-clamp-1 text-xs text-slate-500">{letter.tujuan_surat}</p>
                                            {letter.approval_note && (
                                                <p className="mt-2 rounded-md bg-rose-50 px-2 py-1 text-xs text-rose-700">
                                                    Catatan revisi: {letter.approval_note}
                                                </p>
                                            )}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div className="space-y-1 text-sm text-slate-600">
                                            <p className="font-medium text-slate-900">{letter.createdBy?.name ?? '-'}</p>
                                            <p className="text-xs text-slate-500">{letter.createdBy?.unit?.nama ?? '-'}</p>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div className="space-y-1 text-sm text-slate-600">
                                            <p className="font-medium text-slate-900">{letter.signatory?.name ?? '-'}</p>
                                            <p className="text-xs text-slate-500">{letter.signatory?.position?.nama ?? '-'}</p>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div className="space-y-1 text-sm">
                                            <p className="font-medium text-slate-900">{formatApprovalAge(letter.approval_age_days)}</p>
                                            <div className="flex flex-wrap gap-1.5">
                                                {letter.is_stuck && <Badge variant="danger">Tertahan</Badge>}
                                                {letter.status === 'perlu_revisi' && <Badge variant="warning">Tunggu perbaikan</Badge>}
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <StatusBadge value={letter.status} />
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex justify-end">
                                            <Button asChild variant="outline" size="sm">
                                                <Link href={route('outgoing-letters.show', letter.id)}>
                                                    <Eye className="h-4 w-4" />
                                                    Detail
                                                </Link>
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                            {letters.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={7} className="h-40 text-center text-slate-500">
                                        <div className="flex flex-col items-center gap-3 py-6">
                                            <p>Belum ada surat approval yang cocok untuk dimonitor pada filter ini.</p>
                                            <div className="flex flex-wrap justify-center gap-2">
                                                <Button type="button" variant="outline" onClick={resetFilters}>
                                                    <RotateCcw className="h-4 w-4" />
                                                    Reset Filter
                                                </Button>
                                                <Button asChild variant="outline">
                                                    <Link href={route('outgoing-letters.index')}>
                                                        <Eye className="h-4 w-4" />
                                                        Buka Daftar Surat
                                                    </Link>
                                                </Button>
                                            </div>
                                        </div>
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
    icon: typeof PanelTopOpen;
    href?: string;
    tone?: 'default' | 'warning' | 'danger' | 'success';
}) {
    const iconTone =
        tone === 'danger'
            ? 'bg-rose-50 text-rose-700'
            : tone === 'warning'
              ? 'bg-amber-50 text-amber-700'
              : tone === 'success'
                ? 'bg-emerald-50 text-emerald-700'
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

function formatApprovalAge(value?: number | null) {
    if (value === null || value === undefined) {
        return 'Belum diajukan';
    }

    if (value === 0) {
        return 'Hari ini';
    }

    if (value === 1) {
        return '1 hari';
    }

    return `${value} hari`;
}
