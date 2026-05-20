import { toLineItems, toParagraphs } from '@/Pages/OutgoingLetters/Partials/letterContent';

type PreviewData = {
    nomor_surat_keluar: string;
    tanggal_surat: string;
    lampiran_text: string;
    perihal: string;
    tujuan_surat: string;
    kepada_text: string;
    lokasi_tujuan: string;
    salam_pembuka: string;
    isi_surat: string;
    ringkasan: string;
    lampiran_detail: string;
    penutup_text: string;
    penandatangan_jabatan: string;
    penandatangan_nama: string;
    tembusan_text: string;
};

export default function GeneratedLetterPreview({ data }: { data: PreviewData }) {
    const bodyParagraphs = toParagraphs(data.isi_surat || data.ringkasan);
    const closingParagraphs = toParagraphs(data.penutup_text);
    const attachmentItems = toLineItems(data.lampiran_detail);
    const ccItems = toLineItems(data.tembusan_text);
    const letterDate = formatDate(data.tanggal_surat);

    return (
        <div className="overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
            <div className="relative mx-auto aspect-[210/297] w-full max-w-[420px] bg-white px-8 pb-16 pt-5 text-[11px] leading-5 text-slate-900 shadow-sm">
                <img src="/brand/header-kop.png" alt="Header kop surat UNU Kaltim" className="w-full" />

                <div className="mt-7 flex items-start justify-between gap-4">
                    <div className="min-w-0 flex-1">
                        <table className="w-full text-left align-top">
                            <tbody>
                                <MetaRow label="Nomor" value={data.nomor_surat_keluar || '-'} />
                                <MetaRow label="Perihal" value={data.perihal || '-'} />
                                <MetaRow label="Lampiran" value={data.lampiran_text || '-'} />
                            </tbody>
                        </table>
                    </div>
                    <p className="shrink-0 whitespace-nowrap text-right">Samarinda, {letterDate}</p>
                </div>

                <div className="mt-7 space-y-4">
                    <div>
                        <p>Kepada Yth.</p>
                        <p className="font-semibold">{data.kepada_text || data.tujuan_surat || '-'}</p>
                        {data.lokasi_tujuan && <p>di {data.lokasi_tujuan}</p>}
                    </div>

                    {data.salam_pembuka && <p className="font-semibold">{data.salam_pembuka}</p>}

                    <div className="space-y-2 text-justify">
                        {(bodyParagraphs.length ? bodyParagraphs : ['-']).map((paragraph, index) => (
                            <p key={index} className="whitespace-pre-line indent-8">
                                {paragraph}
                            </p>
                        ))}
                    </div>

                    {attachmentItems.length > 0 && (
                        <div>
                            <p className="font-semibold">Bersama surat ini turut kami lampirkan:</p>
                            <ol className="mt-1 list-decimal space-y-1 pl-5">
                                {attachmentItems.map((item, index) => (
                                    <li key={index}>{item}</li>
                                ))}
                            </ol>
                        </div>
                    )}

                    {closingParagraphs.length > 0 && (
                        <div className="space-y-2 text-justify">
                            {closingParagraphs.map((paragraph, index) => (
                                <p key={index} className="whitespace-pre-line indent-8">
                                    {paragraph}
                                </p>
                            ))}
                        </div>
                    )}

                    <div className="space-y-1 font-semibold">
                        <p>Wallahul Muwaffieq Ilaa Aqwamith Tharieq</p>
                        <p>Wassalamu&apos;alaikum Wr. Wb.</p>
                    </div>

                    <div className="ml-auto w-[11rem] pt-2">
                        <p>Kutai Timur, {letterDate}</p>
                        <p>{data.penandatangan_jabatan || '-'}</p>
                        <p className="mt-16 font-semibold underline underline-offset-2">
                            {data.penandatangan_nama || '-'}
                        </p>
                    </div>

                    {ccItems.length > 0 && (
                        <div className="text-[10px] leading-4">
                            <p className="font-semibold">Tembusan:</p>
                            <ol className="mt-1 list-decimal space-y-0.5 pl-5">
                                {ccItems.map((item, index) => (
                                    <li key={index}>{item}</li>
                                ))}
                            </ol>
                        </div>
                    )}
                </div>

                <div className="absolute bottom-7 left-8 right-8">
                    <img src="/brand/footer-kop.png" alt="Footer kop surat UNU Kaltim" className="w-full" />
                </div>
            </div>
        </div>
    );
}

function MetaRow({
    label,
    value,
    bold = false,
}: {
    label: string;
    value: string;
    bold?: boolean;
}) {
    return (
        <tr>
            <td className="w-16 py-0.5 align-top">{label}</td>
            <td className={`py-0.5 align-top ${bold ? 'font-semibold' : ''}`}>: {value}</td>
        </tr>
    );
}

function formatDate(value: string) {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    }).format(new Date(value));
}
