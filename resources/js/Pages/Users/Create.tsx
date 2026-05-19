import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select } from '@/Components/ui/select';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Position, Role, Unit } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import { FormEvent } from 'react';

type Props = {
    roles: Role[];
    units: Unit[];
    positions: (Position & { unit?: Unit | null })[];
};

export default function Create({ roles, units, positions }: Props) {
    const form = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: '',
        unit_id: '',
        position_id: '',
        is_active: '1',
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post(route('users.store'));
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-sm font-medium text-slate-500">Manajemen User</p>
                        <h1 className="mt-1 text-2xl font-semibold tracking-normal">Tambah User</h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Buat akun baru dan tentukan role, unit kerja, serta jabatan pengguna.
                        </p>
                    </div>
                    <Button asChild variant="outline">
                        <Link href={route('users.index')}>
                            <ArrowLeft className="h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </div>
            }
        >
            <Head title="Tambah User" />

            <form onSubmit={submit} className="grid gap-6 xl:grid-cols-[1fr_360px]">
                <Card>
                    <CardHeader className="border-b border-slate-200">
                        <CardTitle>Informasi User</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-5 pt-5 md:grid-cols-2">
                        <Field label="Nama" error={form.errors.name}>
                            <Input value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} />
                        </Field>

                        <Field label="Email" error={form.errors.email}>
                            <Input type="email" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} />
                        </Field>

                        <Field label="Password" error={form.errors.password}>
                            <Input type="password" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} />
                        </Field>

                        <Field label="Konfirmasi password" error={form.errors.password_confirmation}>
                            <Input
                                type="password"
                                value={form.data.password_confirmation}
                                onChange={(event) => form.setData('password_confirmation', event.target.value)}
                            />
                        </Field>

                        <Field label="Role" error={form.errors.role}>
                            <Select value={form.data.role} onChange={(event) => form.setData('role', event.target.value)}>
                                <option value="">Pilih role</option>
                                {roles.map((role) => (
                                    <option key={role.id} value={role.name}>
                                        {role.name}
                                    </option>
                                ))}
                            </Select>
                        </Field>

                        <Field label="Status akun" error={form.errors.is_active}>
                            <Select value={form.data.is_active} onChange={(event) => form.setData('is_active', event.target.value)}>
                                <option value="1">Aktif</option>
                                <option value="0">Nonaktif</option>
                            </Select>
                        </Field>

                        <Field label="Unit kerja" error={form.errors.unit_id}>
                            <Select value={form.data.unit_id} onChange={(event) => form.setData('unit_id', event.target.value)}>
                                <option value="">Pilih unit</option>
                                {units.map((unit) => (
                                    <option key={unit.id} value={unit.id}>
                                        {unit.nama}
                                    </option>
                                ))}
                            </Select>
                        </Field>

                        <Field label="Jabatan" error={form.errors.position_id}>
                            <Select value={form.data.position_id} onChange={(event) => form.setData('position_id', event.target.value)}>
                                <option value="">Pilih jabatan</option>
                                {positions.map((position) => (
                                    <option key={position.id} value={position.id}>
                                        {position.nama}{position.unit?.nama ? ` - ${position.unit.nama}` : ''}
                                    </option>
                                ))}
                            </Select>
                        </Field>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="border-b border-slate-200">
                        <CardTitle>Aksi</CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-2 pt-5">
                        <Button disabled={form.processing} type="submit">
                            <Save className="h-4 w-4" />
                            Simpan User
                        </Button>
                        <Button asChild variant="outline" type="button">
                            <Link href={route('users.index')}>Batal</Link>
                        </Button>
                    </CardContent>
                </Card>
            </form>
        </AuthenticatedLayout>
    );
}

function Field({
    label,
    error,
    className,
    children,
}: {
    label: string;
    error?: string;
    className?: string;
    children: React.ReactNode;
}) {
    return (
        <div className={className}>
            <Label>{label}</Label>
            <div className="mt-2">{children}</div>
            {error && <p className="mt-1 text-xs text-rose-600">{error}</p>}
        </div>
    );
}
