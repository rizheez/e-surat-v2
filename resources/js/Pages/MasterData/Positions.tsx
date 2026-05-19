import { Input } from '@/Components/ui/input';
import { Select } from '@/Components/ui/select';
import { Position, Unit } from '@/types';
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
    positions: (Position & { unit?: Unit | null })[];
    units: Unit[];
};

type PositionForm = {
    nama: string;
    level: string;
    unit_id: string;
};

export default function Positions({ positions, units }: Props) {
    const [editingPosition, setEditingPosition] = useState<(Position & { unit?: Unit | null }) | null>(null);
    const form = useForm<PositionForm>({
        nama: '',
        level: '1',
        unit_id: '',
    });

    useEffect(() => {
        if (editingPosition) {
            form.setData({
                nama: editingPosition.nama,
                level: String(editingPosition.level),
                unit_id: editingPosition.unit_id ? String(editingPosition.unit_id) : '',
            });
            return;
        }

        form.reset();
        form.clearErrors();
        form.setData('level', '1');
    }, [editingPosition]);

    function submit() {
        if (editingPosition) {
            form.put(route('master-data.positions.update', editingPosition.id), {
                preserveScroll: true,
                onSuccess: () => setEditingPosition(null),
            });

            return;
        }

        form.post(route('master-data.positions.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                form.setData('level', '1');
            },
        });
    }

    function destroy(position: Position) {
        if (!window.confirm(`Hapus ${position.nama}?`)) {
            return;
        }

        router.delete(route('master-data.positions.destroy', position.id), {
            preserveScroll: true,
        });
    }

    return (
        <MasterDataPage
            title="Jabatan"
            description="Kelola jabatan internal untuk struktur organisasi, assignment user, dan alur disposisi."
            pageTitle="Master Data Jabatan"
        >
            <SectionCard
                title="Daftar Jabatan"
                description="Hubungkan jabatan dengan unit kerja agar hak akses dan routing tetap jelas."
                form={
                    <div className="grid gap-4 md:grid-cols-3">
                        <Field label="Nama" error={form.errors.nama}>
                            <Input
                                value={form.data.nama}
                                onChange={(event) => form.setData('nama', event.target.value)}
                            />
                        </Field>
                        <Field label="Level" error={form.errors.level}>
                            <Input
                                type="number"
                                min={1}
                                max={10}
                                value={form.data.level}
                                onChange={(event) => form.setData('level', event.target.value)}
                            />
                        </Field>
                        <Field label="Unit kerja" error={form.errors.unit_id}>
                            <Select
                                value={form.data.unit_id}
                                onChange={(event) => form.setData('unit_id', event.target.value)}
                            >
                                <option value="">Tanpa unit spesifik</option>
                                {units.map((unit) => (
                                    <option key={unit.id} value={unit.id}>
                                        {unit.nama}
                                    </option>
                                ))}
                            </Select>
                        </Field>
                    </div>
                }
                onSubmit={submit}
                submitLabel="Tambah Jabatan"
                processing={form.processing}
            >
                <DataTable
                    headers={['Nama', 'Level', 'Unit', 'Aksi']}
                    rows={positions.map((position) => ({
                        id: position.id,
                        cells: [position.nama, String(position.level), position.unit?.nama ?? '-'],
                        actions: (
                            <TableActions
                                onEdit={() => setEditingPosition(position)}
                                onDelete={() => destroy(position)}
                            />
                        ),
                    }))}
                />
            </SectionCard>

            <EditDialog
                open={!!editingPosition}
                onOpenChange={(open) => !open && setEditingPosition(null)}
                title="Edit Jabatan"
                description="Perbarui nama jabatan, level, dan unit kerja."
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
                    <Field label="Level" error={form.errors.level}>
                        <Input
                            type="number"
                            min={1}
                            max={10}
                            value={form.data.level}
                            onChange={(event) => form.setData('level', event.target.value)}
                        />
                    </Field>
                    <Field label="Unit kerja" error={form.errors.unit_id}>
                        <Select
                            value={form.data.unit_id}
                            onChange={(event) => form.setData('unit_id', event.target.value)}
                        >
                            <option value="">Tanpa unit spesifik</option>
                            {units.map((unit) => (
                                <option key={unit.id} value={unit.id}>
                                    {unit.nama}
                                </option>
                            ))}
                        </Select>
                    </Field>
                </div>
            </EditDialog>
        </MasterDataPage>
    );
}
