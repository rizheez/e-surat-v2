import { Input } from '@/Components/ui/input';
import { Select } from '@/Components/ui/select';
import { Unit } from '@/types';
import { router, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import {
    DataTable,
    EditDialog,
    Field,
    MasterDataPage,
    SectionCard,
    TableActions,
} from './Partials/shared';

type Props = {
    units: Unit[];
};

type UnitForm = {
    nama: string;
    kode: string;
    parent_id: string;
    is_cross_unit_target: boolean;
};

export default function Units({ units }: Props) {
    const [editingUnit, setEditingUnit] = useState<Unit | null>(null);
    const form = useForm<UnitForm>({
        nama: '',
        kode: '',
        parent_id: '',
        is_cross_unit_target: false,
    });

    useEffect(() => {
        if (editingUnit) {
            form.setData({
                nama: editingUnit.nama,
                kode: editingUnit.kode,
                parent_id: editingUnit.parent_id ? String(editingUnit.parent_id) : '',
                is_cross_unit_target: !!editingUnit.is_cross_unit_target,
            });
            return;
        }

        form.reset();
        form.clearErrors();
    }, [editingUnit]);

    function submit() {
        if (editingUnit) {
            form.put(route('master-data.units.update', editingUnit.id), {
                preserveScroll: true,
                onSuccess: () => setEditingUnit(null),
            });

            return;
        }

        form.post(route('master-data.units.store'), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    }

    function destroy(unit: Unit) {
        if (!window.confirm(`Hapus ${unit.nama}?`)) {
            return;
        }

        router.delete(route('master-data.units.destroy', unit.id), {
            preserveScroll: true,
        });
    }

    return (
        <MasterDataPage
            title="Unit Kerja"
            description="Kelola struktur unit untuk distribusi surat, disposisi, dan penempatan pengguna."
            pageTitle="Master Data Unit Kerja"
        >
            <SectionCard
                title="Daftar Unit Kerja"
                description="Susun hierarki unit agar routing surat dan disposisi tetap konsisten."
                form={
                    <div className="grid gap-4 md:grid-cols-3">
                        <Field label="Nama" error={form.errors.nama}>
                            <Input
                                value={form.data.nama}
                                onChange={(event) => form.setData('nama', event.target.value)}
                            />
                        </Field>
                        <Field label="Kode" error={form.errors.kode}>
                            <Input
                                value={form.data.kode}
                                onChange={(event) => form.setData('kode', event.target.value)}
                            />
                        </Field>
                        <Field label="Parent unit" error={form.errors.parent_id}>
                            <Select
                                value={form.data.parent_id}
                                onChange={(event) => form.setData('parent_id', event.target.value)}
                            >
                                <option value="">Tanpa parent</option>
                                {units.map((unit) => (
                                    <option key={unit.id} value={unit.id}>
                                        {unit.nama}
                                    </option>
                                ))}
                            </Select>
                        </Field>
                        <Field label="Akses lintas unit">
                            <label className="flex min-h-10 items-center gap-3 rounded-md border border-cyan-950/10 px-3 py-2 text-sm text-slate-700">
                                <input
                                    type="checkbox"
                                    checked={form.data.is_cross_unit_target}
                                    onChange={(event) => form.setData('is_cross_unit_target', event.target.checked)}
                                    className="rounded border-slate-300"
                                />
                                <span>Bisa menerima disposisi lintas cabang unit</span>
                            </label>
                        </Field>
                    </div>
                }
                onSubmit={submit}
                submitLabel="Tambah Unit"
                processing={form.processing}
            >
                <DataTable
                    headers={['Nama', 'Kode', 'Parent', 'Scope', 'Aksi']}
                    rows={units.map((unit) => ({
                        id: unit.id,
                        cells: [
                            unit.nama,
                            unit.kode,
                            unit.parent?.nama ?? '-',
                            unit.is_cross_unit_target ? 'Lintas unit' : 'Lokal',
                        ],
                        actions: (
                            <TableActions
                                onEdit={() => setEditingUnit(unit)}
                                onDelete={() => destroy(unit)}
                            />
                        ),
                    }))}
                />
            </SectionCard>

            <EditDialog
                open={!!editingUnit}
                onOpenChange={(open) => !open && setEditingUnit(null)}
                title="Edit Unit Kerja"
                description="Perbarui nama, kode, atau parent unit."
                onSubmit={submit}
                processing={form.processing}
            >
                <div className="grid gap-4">
                    <Field label="Nama" error={form.errors.nama}>
                        <Input
                            value={form.data.nama}
                            onChange={(event) => form.setData('nama', event.target.value)}
                        />
                    </Field>
                    <Field label="Kode" error={form.errors.kode}>
                        <Input
                            value={form.data.kode}
                            onChange={(event) => form.setData('kode', event.target.value)}
                        />
                    </Field>
                    <Field label="Parent unit" error={form.errors.parent_id}>
                        <Select
                            value={form.data.parent_id}
                            onChange={(event) => form.setData('parent_id', event.target.value)}
                        >
                            <option value="">Tanpa parent</option>
                            {units
                                .filter((unit) => unit.id !== editingUnit?.id)
                                .map((unit) => (
                                    <option key={unit.id} value={unit.id}>
                                        {unit.nama}
                                    </option>
                                ))}
                        </Select>
                    </Field>
                    <Field label="Akses lintas unit">
                        <label className="flex min-h-10 items-center gap-3 rounded-md border border-cyan-950/10 px-3 py-2 text-sm text-slate-700">
                            <input
                                type="checkbox"
                                checked={form.data.is_cross_unit_target}
                                onChange={(event) => form.setData('is_cross_unit_target', event.target.checked)}
                                className="rounded border-slate-300"
                            />
                            <span>Bisa menerima disposisi lintas cabang unit</span>
                        </label>
                    </Field>
                </div>
            </EditDialog>
        </MasterDataPage>
    );
}
