import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { DispositionInstructionTemplate } from '@/types';
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
    instructionTemplates: DispositionInstructionTemplate[];
};

type InstructionTemplateForm = {
    judul: string;
    isi_instruksi: string;
};

export default function InstructionTemplates({ instructionTemplates }: Props) {
    const [editingItem, setEditingItem] = useState<DispositionInstructionTemplate | null>(null);
    const form = useForm<InstructionTemplateForm>({
        judul: '',
        isi_instruksi: '',
    });

    useEffect(() => {
        if (editingItem) {
            form.setData({
                judul: editingItem.judul,
                isi_instruksi: editingItem.isi_instruksi,
            });
            return;
        }

        form.reset();
        form.clearErrors();
    }, [editingItem]);

    function submit() {
        if (editingItem) {
            form.put(route('master-data.instruction-templates.update', editingItem.id), {
                preserveScroll: true,
                onSuccess: () => setEditingItem(null),
            });

            return;
        }

        form.post(route('master-data.instruction-templates.store'), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    }

    function destroy(item: DispositionInstructionTemplate) {
        if (!window.confirm(`Hapus ${item.judul}?`)) {
            return;
        }

        router.delete(route('master-data.instruction-templates.destroy', item.id), {
            preserveScroll: true,
        });
    }

    return (
        <MasterDataPage
            title="Template Instruksi"
            description="Kelola template instruksi disposisi supaya arahan pimpinan lebih cepat dan konsisten."
            pageTitle="Master Data Template Instruksi"
        >
            <SectionCard
                title="Daftar Template Instruksi"
                description="Sediakan instruksi siap pakai untuk mempercepat pembuatan disposisi."
                form={
                    <div className="grid gap-4">
                        <Field label="Judul" error={form.errors.judul}>
                            <Input
                                value={form.data.judul}
                                onChange={(event) => form.setData('judul', event.target.value)}
                            />
                        </Field>
                        <Field
                            label="Isi instruksi"
                            error={form.errors.isi_instruksi}
                        >
                            <Textarea
                                rows={4}
                                value={form.data.isi_instruksi}
                                onChange={(event) =>
                                    form.setData('isi_instruksi', event.target.value)
                                }
                            />
                        </Field>
                    </div>
                }
                onSubmit={submit}
                submitLabel="Tambah Template"
                processing={form.processing}
            >
                <DataTable
                    headers={['Judul', 'Isi Instruksi', 'Aksi']}
                    rows={instructionTemplates.map((item) => ({
                        id: item.id,
                        cells: [item.judul, item.isi_instruksi],
                        actions: (
                            <TableActions
                                onEdit={() => setEditingItem(item)}
                                onDelete={() => destroy(item)}
                            />
                        ),
                    }))}
                />
            </SectionCard>

            <EditDialog
                open={!!editingItem}
                onOpenChange={(open) => !open && setEditingItem(null)}
                title="Edit Template Instruksi"
                description="Perbarui judul dan isi template instruksi disposisi."
                onSubmit={submit}
                processing={form.processing}
            >
                <div className="grid gap-4">
                    <Field label="Judul" error={form.errors.judul}>
                        <Input
                            value={form.data.judul}
                            onChange={(event) => form.setData('judul', event.target.value)}
                        />
                    </Field>
                    <Field
                        label="Isi instruksi"
                        error={form.errors.isi_instruksi}
                    >
                        <Textarea
                            rows={5}
                            value={form.data.isi_instruksi}
                            onChange={(event) =>
                                form.setData('isi_instruksi', event.target.value)
                            }
                        />
                    </Field>
                </div>
            </EditDialog>
        </MasterDataPage>
    );
}
