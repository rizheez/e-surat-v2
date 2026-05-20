import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { LockKeyhole, Mail } from 'lucide-react';
import { FormEventHandler } from 'react';

export default function Login({
    status,
    canResetPassword,
}: {
    status?: string;
    canResetPassword: boolean;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false as boolean,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Masuk" />

            {status && (
                <div className="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                    {status}
                </div>
            )}

            <div>
                <p className="text-sm font-medium text-cyan-700">Masuk ke akun Anda</p>
                <h2 className="mt-2 text-3xl font-semibold tracking-normal text-slate-950">Selamat datang kembali</h2>
                <p className="mt-3 text-sm leading-6 text-slate-500">
                    Gunakan akun persuratan untuk melanjutkan pekerjaan Anda di sistem e-Surat.
                </p>
            </div>

            <form onSubmit={submit} className="mt-8 space-y-5">
                <div>
                    <InputLabel htmlFor="email" value="Email" />

                    <div className="relative mt-2">
                        <Mail className="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <TextInput
                            id="email"
                            type="email"
                            name="email"
                            value={data.email}
                            className="block w-full rounded-xl border-slate-200 py-3 pl-11 pr-4 text-sm shadow-sm focus:border-cyan-600 focus:ring-cyan-600"
                            autoComplete="username"
                            isFocused={true}
                            placeholder="nama@unu-kaltim.ac.id"
                            onChange={(e) => setData('email', e.target.value)}
                        />
                    </div>

                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="password" value="Password" />

                    <div className="relative mt-2">
                        <LockKeyhole className="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <TextInput
                            id="password"
                            type="password"
                            name="password"
                            value={data.password}
                            className="block w-full rounded-xl border-slate-200 py-3 pl-11 pr-4 text-sm shadow-sm focus:border-cyan-600 focus:ring-cyan-600"
                            autoComplete="current-password"
                            placeholder="Masukkan password"
                            onChange={(e) => setData('password', e.target.value)}
                        />
                    </div>

                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <label className="flex items-center text-sm text-slate-600">
                        <Checkbox
                            name="remember"
                            checked={data.remember}
                            onChange={(e) =>
                                setData(
                                    'remember',
                                    (e.target.checked || false) as false,
                                )
                            }
                        />
                        <span className="ms-2">Tetap masuk</span>
                    </label>

                    {canResetPassword && (
                        <Link
                            href={route('password.request')}
                            className="text-sm font-medium text-slate-500 underline-offset-4 hover:text-slate-900 hover:underline focus:outline-none focus:ring-2 focus:ring-cyan-600 focus:ring-offset-2"
                        >
                            Lupa password?
                        </Link>
                    )}
                </div>

                <PrimaryButton
                    className="flex w-full justify-center rounded-xl bg-slate-900 px-4 py-3 text-sm tracking-[0.18em] hover:bg-cyan-800 focus:bg-cyan-800 active:bg-slate-950"
                    disabled={processing}
                >
                    {processing ? 'MEMPROSES...' : 'MASUK'}
                </PrimaryButton>

                <p className="text-center text-xs leading-5 text-slate-500">
                    Akses hanya untuk pengguna terdaftar di lingkungan Universitas Nahdlatul Ulama Kalimantan Timur.
                </p>
            </form>
        </GuestLayout>
    );
}
