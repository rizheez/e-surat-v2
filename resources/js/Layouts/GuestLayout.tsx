import { Link } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

export default function Guest({ children }: PropsWithChildren) {
    return (
        <div className="min-h-screen bg-slate-100 px-4 py-6 sm:px-6 lg:px-8">
            <div className="mx-auto flex min-h-[calc(100vh-3rem)] max-w-6xl items-center">
                <div className="grid w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl lg:grid-cols-[1.15fr_0.85fr]">
                    <section className="relative flex min-h-[320px] flex-col justify-between overflow-hidden bg-slate-900 px-6 py-8 text-white sm:px-8 lg:px-10">
                        <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(34,211,238,0.18),_transparent_40%),linear-gradient(180deg,rgba(15,23,42,0.78),rgba(15,23,42,0.96))]" />
                        <div className="relative z-10">
                            <Link href="/" className="inline-flex items-center gap-4">
                                <img
                                    src="/brand/logo-unu-kaltim.png"
                                    alt="Logo UNU Kaltim"
                                    className="h-16 w-16 rounded-xl bg-white/95 p-2 shadow-lg"
                                />
                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-200">
                                        Portal Persuratan
                                    </p>
                                    <h1 className="mt-1 text-xl font-semibold tracking-normal sm:text-2xl">
                                        Universitas Nahdlatul Ulama Kalimantan Timur
                                    </h1>
                                </div>
                            </Link>
                            <div className="mt-10 max-w-xl">
                                <p className="text-sm font-medium text-cyan-200">e-Surat Internal</p>
                                <p className="mt-3 text-3xl font-semibold leading-tight text-white sm:text-4xl">
                                    Kelola surat, disposisi, dan approval dalam satu alur yang rapi.
                                </p>
                                <p className="mt-4 max-w-lg text-sm leading-6 text-slate-200 sm:text-base">
                                    Masuk ke sistem untuk memantau penerimaan surat, memproses disposisi berjenjang,
                                    dan menindaklanjuti persetujuan naskah keluar tanpa kehilangan jejak kerja.
                                </p>
                            </div>
                        </div>
                        <div className="relative z-10 grid gap-3 sm:grid-cols-3">
                            <div className="rounded-xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                                <p className="text-xs uppercase tracking-[0.18em] text-cyan-100">Penerimaan Surat</p>
                                <p className="mt-2 text-sm leading-6 text-slate-100">
                                    Agenda otomatis dan arsip digital lebih tertata.
                                </p>
                            </div>
                            <div className="rounded-xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                                <p className="text-xs uppercase tracking-[0.18em] text-cyan-100">Disposisi</p>
                                <p className="mt-2 text-sm leading-6 text-slate-100">
                                    Alur berjenjang, monitoring aktif, dan reminder deadline.
                                </p>
                            </div>
                            <div className="rounded-xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                                <p className="text-xs uppercase tracking-[0.18em] text-cyan-100">Approval</p>
                                <p className="mt-2 text-sm leading-6 text-slate-100">
                                    Persetujuan naskah keluar dengan QR tanda tangan elektronik.
                                </p>
                            </div>
                        </div>
                    </section>

                    <section className="flex items-center bg-white px-6 py-8 sm:px-8 lg:px-10">
                        <div className="mx-auto w-full max-w-md">{children}</div>
                    </section>
                </div>
            </div>
        </div>
    );
}
