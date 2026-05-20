import Dropdown from '@/Components/Dropdown';
import { Button } from '@/Components/ui/button';
import { masterDataNavigation } from '@/lib/master-data-navigation';
import { cn } from '@/lib/utils';
import { AppNotification, PageProps } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import {
    Archive,
    Bell,
    ChevronDown,
    ChevronsUpDown,
    Database,
    FileInput,
    FileOutput,
    GraduationCap,
    LayoutDashboard,
    PanelTopOpen,
    Radar,
    Menu,
    Search,
    Send,
    ShieldCheck,
    Users,
} from 'lucide-react';
import { PropsWithChildren, ReactNode, useEffect, useState } from 'react';

const navigationSections = [
    {
        title: 'Ringkasan',
        items: [{ name: 'Dashboard', href: 'dashboard', active: 'dashboard', icon: LayoutDashboard }],
    },
    {
        title: 'Penerimaan',
        items: [{ name: 'Penerimaan Surat', href: 'incoming-letters.index', active: 'incoming-letters.*', icon: FileInput }],
    },
    {
        title: 'Disposisi',
        items: [
            {
                name: 'Tindak Lanjut',
                href: 'dispositions.index',
                active: 'dispositions.index',
                icon: Send,
                permission: 'view disposition',
            },
            {
                name: 'Monitor Disposisi',
                href: 'dispositions.monitor',
                active: 'dispositions.monitor',
                icon: Radar,
                permission: 'view disposition',
            },
        ],
    },
    {
        title: 'Penyusunan',
        items: [
            { name: 'Penyusunan Surat', href: 'outgoing-letters.index', active: 'outgoing-letters.*', icon: FileOutput },
            {
                name: 'Inbox Persetujuan',
                href: 'outgoing-letters.approvals',
                active: 'outgoing-letters.approvals',
                icon: ShieldCheck,
            },
            {
                name: 'Monitor Persetujuan',
                href: 'outgoing-letters.monitor',
                active: 'outgoing-letters.monitor',
                icon: PanelTopOpen,
                permission: 'view outgoing letters',
            },
        ],
    },
    {
        title: 'Arsip dan Referensi',
        items: [
            { name: 'Arsip Digital', href: 'archives.index', active: 'archives.*', icon: Archive },
            {
                name: 'Master Data',
                active: 'master-data.*',
                icon: Database,
                permission: 'manage master data',
                children: masterDataNavigation.map((item) => ({
                    name: item.label,
                    href: item.route,
                    active: item.route,
                })),
            },
            { name: 'Manajemen User', href: 'users.index', active: 'users.*', icon: Users, permission: 'manage users' },
        ],
    },
];

export default function Authenticated({
    header,
    children,
}: PropsWithChildren<{ header?: ReactNode }>) {
    const { auth, flash, notifications } = usePage<PageProps>().props;
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const masterDataActive = route().current('master-data.*');
    const [masterDataOpen, setMasterDataOpen] = useState(masterDataActive);
    const user = auth.user;
    const initials = user.name
        .split(' ')
        .map((name) => name[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();

    useEffect(() => {
        if (masterDataActive) {
            setMasterDataOpen(true);
        }
    }, [masterDataActive]);

    return (
        <div className="min-h-screen bg-[#eef7f8] text-slate-950">
            {sidebarOpen && (
                <button
                    aria-label="Tutup navigasi"
                    className="fixed inset-0 z-30 bg-cyan-950/50 lg:hidden"
                    onClick={() => setSidebarOpen(false)}
                />
            )}

            <aside
                className={cn(
                    'fixed inset-y-0 left-0 z-40 flex w-72 flex-col overflow-hidden border-r border-cyan-950/10 bg-[#006d78] text-white shadow-2xl shadow-cyan-950/20 transition-transform lg:translate-x-0',
                    sidebarOpen ? 'translate-x-0' : '-translate-x-full',
                )}
            >
                <div className="relative border-b border-white/10 px-5 py-5">
                    <div className="pointer-events-none absolute -right-12 -top-16 h-36 w-36 rounded-full bg-[#ff7900]/25 blur-2xl" />
                    <div className="pointer-events-none absolute -bottom-20 left-8 h-36 w-36 rounded-full bg-cyan-200/20 blur-2xl" />

                    <div className="relative flex items-center gap-3">
                        <div className="flex h-12 w-12 items-center justify-center rounded-lg border border-white/20 bg-white p-1.5 shadow-lg shadow-cyan-950/20">
                            <img
                                src="/brand/logo-unu-kaltim.png"
                                alt="Logo UNUKALTIM"
                                className="h-full w-full object-contain"
                            />
                        </div>
                        <div>
                            <Link href={route('dashboard')} className="block text-sm font-semibold tracking-wide">
                                E-Surat Internal
                            </Link>
                            <p className="mt-0.5 text-[11px] font-medium uppercase tracking-[0.22em] text-orange-100">
                                UNUKALTIM
                            </p>
                        </div>
                    </div>

                    <div className="relative mt-5 rounded-lg border border-white/10 bg-white/10 p-3 backdrop-blur">
                        <div className="flex items-start gap-3">
                            <div className="mt-0.5 flex h-8 w-8 items-center justify-center rounded-md bg-[#ff7900] text-white">
                                <GraduationCap className="h-4 w-4" />
                            </div>
                            <div>
                                <p className="text-xs font-semibold text-white">Alur Kerja Persuratan</p>
                                <p className="mt-1 text-xs leading-5 text-cyan-50/80">
                                    Penerimaan surat, disposisi berjenjang, persetujuan, dan arsip kampus.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <nav className="flex-1 space-y-4 overflow-y-auto px-3 py-4">
                    {navigationSections.map((section) => {
                        const items = section.items.filter((item) => !item.permission || auth.permissions.includes(item.permission));

                        if (items.length === 0) {
                            return null;
                        }

                        return (
                            <div key={section.title}>
                                <p className="px-3 pb-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-cyan-50/45">
                                    {section.title}
                                </p>
                                <div className="space-y-1">
                                    {items.map((item) => {
                            const Icon = item.icon;
                            const hasApprovalBadge =
                                item.href === 'outgoing-letters.approvals' && notifications.pending_approvals_count > 0;
                            const active =
                                item.href === 'outgoing-letters.index'
                                    ? route().current('outgoing-letters.index') ||
                                      route().current('outgoing-letters.create') ||
                                      route().current('outgoing-letters.edit') ||
                                      route().current('outgoing-letters.show')
                                    : item.href === 'dispositions.index'
                                      ? route().current('dispositions.index') ||
                                        route().current('dispositions.create') ||
                                        route().current('dispositions.show')
                                    : route().current(item.active);

                                        if (item.children) {
                                            return (
                                                <div key={item.name} className="space-y-1">
                                                    <button
                                                        type="button"
                                                        onClick={() => setMasterDataOpen((open) => !open)}
                                                        className={cn(
                                                            'group relative flex h-11 w-full items-center gap-3 rounded-lg px-3 text-sm font-medium transition-all',
                                                            active
                                                                ? 'bg-white text-cyan-950 shadow-lg shadow-cyan-950/20'
                                                                : 'text-cyan-50/80 hover:bg-white/10 hover:text-white',
                                                        )}
                                                    >
                                                        <span
                                                            className={cn(
                                                                'flex h-7 w-7 items-center justify-center rounded-md transition-colors',
                                                                active
                                                                    ? 'bg-[#ff7900] text-white'
                                                                    : 'bg-white/10 text-cyan-50 group-hover:bg-[#ff7900]/25 group-hover:text-orange-100',
                                                            )}
                                                        >
                                                            <Icon className="h-4 w-4" />
                                                        </span>
                                                        <span className="flex-1 text-left">{item.name}</span>
                                                        <ChevronDown
                                                            className={cn(
                                                                'h-4 w-4 transition-transform',
                                                                masterDataOpen && 'rotate-180',
                                                            )}
                                                        />
                                                    </button>

                                                    {masterDataOpen && (
                                                        <div className="ml-6 space-y-1 border-l border-white/10 pl-5">
                                                            {item.children.map((child) => {
                                                                const childActive = route().current(child.active);

                                                                return (
                                                                    <Link
                                                                        key={child.href}
                                                                        href={route(child.href)}
                                                                        onClick={() => setSidebarOpen(false)}
                                                                        className={cn(
                                                                            'flex min-h-[2.5rem] items-center rounded-lg px-3 text-sm transition-all',
                                                                            childActive
                                                                                ? 'bg-white/10 font-medium text-white'
                                                                                : 'text-cyan-50/75 hover:bg-white/10 hover:text-white',
                                                                        )}
                                                                    >
                                                                        {child.name}
                                                                    </Link>
                                                                );
                                                            })}
                                                        </div>
                                                    )}
                                                </div>
                                            );
                                        }

                                        return (
                                            <Link
                                                key={item.name}
                                                href={route(item.href)}
                                                onClick={() => setSidebarOpen(false)}
                                                className={cn(
                                                    'group relative flex h-11 items-center gap-3 rounded-lg px-3 text-sm font-medium transition-all',
                                                    active
                                                        ? 'bg-white text-cyan-950 shadow-lg shadow-cyan-950/20'
                                                        : 'text-cyan-50/80 hover:bg-white/10 hover:text-white',
                                                )}
                                            >
                                                <span
                                                    className={cn(
                                                        'flex h-7 w-7 items-center justify-center rounded-md transition-colors',
                                                        active
                                                            ? 'bg-[#ff7900] text-white'
                                                            : 'bg-white/10 text-cyan-50 group-hover:bg-[#ff7900]/25 group-hover:text-orange-100',
                                                    )}
                                                >
                                                    <Icon className="h-4 w-4" />
                                                </span>
                                                {item.name}
                                                {hasApprovalBadge && (
                                                    <span className="ml-auto inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-[#ff7900] px-1.5 text-[10px] font-semibold leading-5 text-white">
                                                        {notifications.pending_approvals_count > 9 ? '9+' : notifications.pending_approvals_count}
                                                    </span>
                                                )}
                                                {active && !hasApprovalBadge && <span className="ml-auto h-2 w-2 rounded-full bg-[#ff7900]" />}
                                            </Link>
                                        );
                                    })}
                                </div>
                            </div>
                        );
                    })}
                </nav>

                <div className="border-t border-white/10 p-3">
                    <div className="rounded-lg border border-white/10 bg-cyan-950/25 p-3">
                        <div className="flex items-center gap-2 text-orange-100">
                            <ShieldCheck className="h-4 w-4" />
                            <p className="text-[11px] font-semibold uppercase tracking-[0.16em]">Unit aktif</p>
                        </div>
                        <p className="mt-2 truncate text-sm font-semibold text-white">{user.unit?.nama ?? 'Belum diatur'}</p>
                    </div>
                </div>
            </aside>

            <div className="lg:pl-72">
                <header className="sticky top-0 z-20 border-b border-cyan-950/10 bg-white/90 shadow-sm shadow-cyan-950/5 backdrop-blur">
                    <div className="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="lg:hidden"
                            onClick={() => setSidebarOpen(true)}
                        >
                            <Menu className="h-5 w-5" />
                        </Button>

                        <div className="relative hidden w-full max-w-md md:block">
                            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-cyan-700/60" />
                            <input
                                className="h-10 w-full rounded-lg border border-cyan-950/10 bg-cyan-50/60 pl-9 pr-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-cyan-700/40 focus:bg-white focus:ring-2 focus:ring-cyan-700/10"
                                placeholder="Cari surat, agenda, atau disposisi"
                                type="search"
                            />
                        </div>

                        <div className="ml-auto flex items-center gap-2">
                            <Dropdown>
                                <Dropdown.Trigger>
                                    <button
                                        type="button"
                                        className="relative inline-flex h-10 w-10 items-center justify-center rounded-lg text-cyan-950 transition hover:bg-cyan-50"
                                    >
                                        <Bell className="h-4 w-4" />
                                        {notifications.unread_count > 0 && (
                                            <span className="absolute right-1.5 top-1.5 inline-flex min-w-[1.1rem] items-center justify-center rounded-full bg-[#ff7900] px-1 text-[10px] font-semibold leading-4 text-white">
                                                {notifications.unread_count > 9 ? '9+' : notifications.unread_count}
                                            </span>
                                        )}
                                    </button>
                                </Dropdown.Trigger>
                                <Dropdown.Content
                                    width="80"
                                    contentClasses="overflow-hidden rounded-xl border border-cyan-950/10 bg-white py-0"
                                >
                                    <div className="border-b border-slate-200 px-4 py-3">
                                        <div className="flex items-center justify-between gap-3">
                                            <div>
                                                <p className="text-sm font-semibold text-slate-950">Notifikasi</p>
                                                <p className="mt-1 text-xs text-slate-500">
                                                    {notifications.unread_count > 0
                                                        ? `${notifications.unread_count} belum dibaca`
                                                        : 'Semua notifikasi sudah dibaca'}
                                                </p>
                                                {notifications.pending_approvals_count > 0 && (
                                                    <p className="mt-1 text-xs font-medium text-cyan-700">
                                                        {notifications.pending_approvals_count} surat menunggu persetujuan Anda
                                                    </p>
                                                )}
                                            </div>
                                            {notifications.unread_count > 0 && (
                                                <Dropdown.Link
                                                    href={route('notifications.read-all')}
                                                    method="post"
                                                    as="button"
                                                    className="px-0 py-0 text-xs font-semibold text-cyan-700 hover:bg-transparent hover:text-cyan-900 focus:bg-transparent"
                                                >
                                                    Tandai semua
                                                </Dropdown.Link>
                                            )}
                                        </div>
                                    </div>

                                    <div className="max-h-[24rem] overflow-y-auto">
                                        {notifications.items.length > 0 ? (
                                            notifications.items.map((notification) => (
                                                <Dropdown.Link
                                                    key={notification.id}
                                                    href={route('notifications.read', notification.id)}
                                                    method="post"
                                                    as="button"
                                                    className={cn(
                                                        'border-b border-slate-100 px-4 py-3 text-left last:border-b-0',
                                                        !notification.read_at && 'bg-cyan-50/60',
                                                    )}
                                                >
                                                    <NotificationItem notification={notification} />
                                                </Dropdown.Link>
                                            ))
                                        ) : (
                                            <div className="px-4 py-8 text-center text-sm text-slate-500">
                                                Belum ada notifikasi.
                                            </div>
                                        )}
                                    </div>
                                </Dropdown.Content>
                            </Dropdown>

                            <Dropdown>
                                <Dropdown.Trigger>
                                    <button className="flex h-11 items-center gap-3 rounded-lg border border-cyan-950/10 bg-white px-2 py-1.5 text-left text-sm shadow-sm shadow-cyan-950/5 hover:bg-cyan-50/60">
                                        <span className="flex h-8 w-8 items-center justify-center rounded-md bg-cyan-900 text-xs font-semibold text-white">
                                            {initials}
                                        </span>
                                        <span className="hidden min-w-0 sm:block">
                                            <span className="block truncate text-sm font-semibold text-cyan-950">{user.name}</span>
                                            <span className="block truncate text-xs text-slate-500">
                                                {user.position?.nama ?? user.email}
                                            </span>
                                        </span>
                                        <ChevronsUpDown className="hidden h-4 w-4 text-cyan-700/60 sm:block" />
                                    </button>
                                </Dropdown.Trigger>
                                <Dropdown.Content>
                                    <Dropdown.Link href={route('profile.edit')}>Profil</Dropdown.Link>
                                    <Dropdown.Link href={route('logout')} method="post" as="button">
                                        Keluar
                                    </Dropdown.Link>
                                </Dropdown.Content>
                            </Dropdown>
                        </div>
                    </div>
                </header>

                <main className="min-h-[calc(100vh-4rem)]">
                    {header && (
                        <div className="border-b border-cyan-950/10 bg-[linear-gradient(135deg,#ffffff_0%,#effbfc_54%,#fff2e5_100%)] px-4 py-5 sm:px-6 lg:px-8">
                            <div className="flex items-start gap-4">
                                <div className="hidden h-12 w-1.5 rounded-full bg-[#ff7900] sm:block" />
                                <div className="min-w-0 flex-1">{header}</div>
                            </div>
                        </div>
                    )}

                    <div className="relative px-4 py-6 sm:px-6 lg:px-8">
                        <div className="pointer-events-none absolute inset-x-0 top-0 h-40 bg-[radial-gradient(circle_at_top_right,rgba(255,121,0,0.16),transparent_34%),radial-gradient(circle_at_top_left,rgba(0,157,171,0.14),transparent_30%)]" />
                        <div className="relative">
                        {flash?.success && (
                            <div className="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm">
                                {flash.success}
                            </div>
                        )}
                        {flash?.error && (
                            <div className="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 shadow-sm">
                                {flash.error}
                            </div>
                        )}
                        {children}
                        </div>
                    </div>
                </main>
            </div>
        </div>
    );
}

function NotificationItem({ notification }: { notification: AppNotification }) {
    return (
        <div>
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <p className="line-clamp-1 text-sm font-semibold text-slate-950">
                        {notification.title}
                    </p>
                    {notification.sender && (
                        <p className="mt-1 text-xs font-medium text-cyan-700">
                            Dari {notification.sender}
                        </p>
                    )}
                </div>
                {!notification.read_at && (
                    <span className="mt-1 h-2 w-2 shrink-0 rounded-full bg-[#ff7900]" />
                )}
            </div>
            <p className="mt-2 line-clamp-2 text-xs leading-5 text-slate-600">{notification.body}</p>
            <div className="mt-2 flex items-center justify-between gap-3 text-[11px] text-slate-500">
                <span>{formatNotificationTime(notification.created_at)}</span>
                {notification.batas_waktu && <span>Deadline {formatDate(notification.batas_waktu)}</span>}
            </div>
        </div>
    );
}

function formatNotificationTime(value?: string | null) {
    if (!value) {
        return '-';
    }

    const date = new Date(value);
    const diffInMinutes = Math.round((date.getTime() - Date.now()) / 60000);
    const formatter = new Intl.RelativeTimeFormat('id-ID', { numeric: 'auto' });

    if (Math.abs(diffInMinutes) < 60) {
        return formatter.format(diffInMinutes, 'minute');
    }

    const diffInHours = Math.round(diffInMinutes / 60);
    if (Math.abs(diffInHours) < 24) {
        return formatter.format(diffInHours, 'hour');
    }

    const diffInDays = Math.round(diffInHours / 24);
    if (Math.abs(diffInDays) < 7) {
        return formatter.format(diffInDays, 'day');
    }

    return formatDate(value);
}

function formatDate(value?: string | null) {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
}
