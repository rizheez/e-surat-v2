import Pagination from '@/Components/Pagination';
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
import { Role, Unit, User, Paginator } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { KeyRound, Pencil, Plus, RotateCcw, Search, Trash2, UserCog } from 'lucide-react';

type Props = {
    users: Paginator<User>;
    filters: Record<string, string>;
    roles: Role[];
    units: Unit[];
};

export default function Index({ users, filters, roles, units }: Props) {
    function setFilter(name: string, value: string) {
        router.get(route('users.index'), { ...filters, [name]: value }, { preserveState: true, preserveScroll: true, replace: true });
    }

    function resetFilters() {
        router.get(route('users.index'), {}, { preserveScroll: true, replace: true });
    }

    function toggleStatus(user: User) {
        router.patch(route('users.status', user.id), {}, { preserveScroll: true });
    }

    function resetPassword(user: User) {
        if (!window.confirm(`Reset password untuk ${user.name}? Password sementara akan ditampilkan setelah berhasil.`)) {
            return;
        }

        router.post(route('users.reset-password', user.id), {}, { preserveScroll: true });
    }

    function destroyUser(user: User) {
        if (!window.confirm(`Hapus user ${user.name}?`)) {
            return;
        }

        router.delete(route('users.destroy', user.id), { preserveScroll: true });
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-sm font-medium text-slate-500">Administrasi</p>
                        <h1 className="mt-1 text-2xl font-semibold tracking-normal">Manajemen User</h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Kelola akun, role, unit kerja, jabatan, dan status akses pengguna.
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={route('users.create')}>
                            <Plus className="h-4 w-4" />
                            Tambah User
                        </Link>
                    </Button>
                </div>
            }
        >
            <Head title="Manajemen User" />

            <Card>
                <CardHeader className="border-b border-slate-200 pb-4">
                    <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <CardTitle>Daftar User</CardTitle>
                            <p className="mt-1 text-sm text-slate-500">{users.total} user tercatat dalam sistem.</p>
                        </div>
                        <div className="grid gap-2 sm:grid-cols-2 xl:grid-cols-5">
                            <div className="relative sm:col-span-2">
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <Input
                                    defaultValue={filters.search ?? ''}
                                    onChange={(event) => setFilter('search', event.target.value)}
                                    placeholder="Cari nama atau email"
                                    className="pl-9"
                                />
                            </div>
                            <Select value={filters.role ?? ''} onChange={(event) => setFilter('role', event.target.value)}>
                                <option value="">Semua role</option>
                                {roles.map((role) => (
                                    <option key={role.id} value={role.name}>
                                        {role.name}
                                    </option>
                                ))}
                            </Select>
                            <Select value={filters.unit_id ?? ''} onChange={(event) => setFilter('unit_id', event.target.value)}>
                                <option value="">Semua unit</option>
                                {units.map((unit) => (
                                    <option key={unit.id} value={unit.id}>
                                        {unit.nama}
                                    </option>
                                ))}
                            </Select>
                            <Select value={filters.status ?? ''} onChange={(event) => setFilter('status', event.target.value)}>
                                <option value="">Semua status</option>
                                <option value="active">Aktif</option>
                                <option value="inactive">Nonaktif</option>
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
                                <TableHead>User</TableHead>
                                <TableHead>Role</TableHead>
                                <TableHead>Unit</TableHead>
                                <TableHead>Jabatan</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="w-[280px] text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {users.data.map((user) => (
                                <TableRow key={user.id}>
                                    <TableCell>
                                        <div>
                                            <p className="font-medium text-slate-950">{user.name}</p>
                                            <p className="mt-1 text-xs text-slate-500">{user.email}</p>
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-slate-600">{user.primary_role ?? '-'}</TableCell>
                                    <TableCell className="text-slate-600">{user.unit?.nama ?? '-'}</TableCell>
                                    <TableCell className="text-slate-600">{user.position?.nama ?? '-'}</TableCell>
                                    <TableCell>
                                        <span className={user.is_active ? 'inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700' : 'inline-flex rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700'}>
                                            {user.is_active ? 'Aktif' : 'Nonaktif'}
                                        </span>
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex flex-wrap justify-end gap-2">
                                            <Button asChild variant="outline" size="sm">
                                                <Link href={route('users.edit', user.id)}>
                                                    <Pencil className="h-4 w-4" />
                                                    Edit
                                                </Link>
                                            </Button>
                                            <Button type="button" variant="ghost" size="sm" onClick={() => toggleStatus(user)}>
                                                <UserCog className="h-4 w-4" />
                                                {user.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                                            </Button>
                                            <Button type="button" variant="ghost" size="sm" onClick={() => resetPassword(user)}>
                                                <KeyRound className="h-4 w-4" />
                                                Reset Password
                                            </Button>
                                            <Button type="button" variant="destructive" size="sm" onClick={() => destroyUser(user)}>
                                                <Trash2 className="h-4 w-4" />
                                                Hapus
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                            {users.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={6} className="h-40 text-center text-slate-500">
                                        Belum ada user yang sesuai filter.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                    <Pagination meta={users} />
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
