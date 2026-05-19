import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Label } from '@/Components/ui/label';
import { MultiCombobox } from '@/Components/ui/multi-combobox';
import { Select } from '@/Components/ui/select';
import { Textarea } from '@/Components/ui/textarea';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { IncomingLetter, User } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Send } from 'lucide-react';
import { FormEvent } from 'react';

type Template = { id: number; judul: string; isi_instruksi: string };

type Props = {
    letters: IncomingLetter[];
    users: User[];
    templates: Template[];
    selectedIncomingLetterId?: number | null;
};

export default function Create({ letters, users, templates, selectedIncomingLetterId }: Props) {
    const form = useForm({
        incoming_letter_id: selectedIncomingLetterId ? String(selectedIncomingLetterId) : '',
        recipient_ids: [] as number[],
        instruksi: '',
        catatan: '',
        batas_waktu: '',
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post(route('dispositions.store'));
    }

    const recipientOptions = users.map((user) => ({
        value: String(user.id),
        label: user.name,
        description: `${user.position?.nama ?? '-'} - ${user.unit?.nama ?? '-'}`,
    }));

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-sm font-medium text-slate-500">Disposisi</p>
                        <h1 className="mt-1 text-2xl font-semibold tracking-normal">Buat Disposisi</h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Pilih surat, penerima, instruksi, dan batas waktu penyelesaian.
                        </p>
                    </div>
                    <Button asChild variant="outline">
                        <Link href={route('dispositions.index')}>
                            <ArrowLeft className="h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </div>
            }
        >
            <Head title="Buat Disposisi" />

            <form onSubmit={submit} className="grid gap-6 xl:grid-cols-[1fr_420px]">
                <Card>
                    <CardHeader className="border-b border-slate-200">
                        <CardTitle>Instruksi Disposisi</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-5 pt-5">
                        <Field label="Surat masuk" error={form.errors.incoming_letter_id}>
                            <Select
                                value={form.data.incoming_letter_id}
                                onChange={(event) => form.setData('incoming_letter_id', event.target.value)}
                            >
                                <option value="">Pilih surat</option>
                                {letters.map((letter) => (
                                    <option key={letter.id} value={letter.id}>
                                        {letter.nomor_agenda} - {letter.perihal}
                                    </option>
                                ))}
                            </Select>
                        </Field>

                        <Field label="Template instruksi">
                            <Select onChange={(event) => form.setData('instruksi', event.target.value)}>
                                <option value="">Pilih template</option>
                                {templates.map((template) => (
                                    <option key={template.id} value={template.isi_instruksi}>
                                        {template.judul}
                                    </option>
                                ))}
                            </Select>
                        </Field>

                        <Field label="Instruksi" error={form.errors.instruksi}>
                            <Textarea
                                value={form.data.instruksi}
                                onChange={(event) => form.setData('instruksi', event.target.value)}
                                rows={6}
                                placeholder="Tulis instruksi disposisi"
                            />
                        </Field>

                        <Field label="Catatan">
                            <Textarea
                                value={form.data.catatan}
                                onChange={(event) => form.setData('catatan', event.target.value)}
                                rows={4}
                                placeholder="Catatan tambahan jika diperlukan"
                            />
                        </Field>

                        <Field label="Batas waktu">
                            <input
                                type="date"
                                value={form.data.batas_waktu}
                                onChange={(event) => form.setData('batas_waktu', event.target.value)}
                                className="flex h-10 w-full rounded-md border border-gray-200 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-950 focus-visible:ring-offset-2"
                            />
                        </Field>
                    </CardContent>
                </Card>

                <div className="space-y-6">
                    <Card>
                        <CardHeader className="border-b border-slate-200">
                            <CardTitle>Penerima</CardTitle>
                        </CardHeader>
                        <CardContent className="pt-5">
                            {form.errors.recipient_ids && (
                                <p className="mb-3 text-xs text-rose-600">{form.errors.recipient_ids}</p>
                            )}
                            <MultiCombobox
                                options={recipientOptions}
                                value={form.data.recipient_ids.map(String)}
                                onChange={(values) => form.setData('recipient_ids', values.map(Number))}
                                placeholder="Pilih satu atau beberapa penerima"
                                searchPlaceholder="Cari nama, jabatan, atau unit..."
                                emptyText="Penerima tidak ditemukan."
                            />
                            <p className="mt-3 text-xs text-slate-500">
                                Gunakan pencarian untuk memilih banyak penerima tanpa harus scroll daftar panjang.
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="border-b border-slate-200">
                            <CardTitle>Aksi</CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-2 pt-5">
                            <Button disabled={form.processing} type="submit">
                                <Send className="h-4 w-4" />
                                Kirim Disposisi
                            </Button>
                            <Button asChild variant="outline" type="button">
                                <Link href={route('dispositions.index')}>Batal</Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}

function Field({
    label,
    error,
    children,
}: {
    label: string;
    error?: string;
    children: React.ReactNode;
}) {
    return (
        <div>
            <Label>{label}</Label>
            <div className="mt-2">{children}</div>
            {error && <p className="mt-1 text-xs text-rose-600">{error}</p>}
        </div>
    );
}
