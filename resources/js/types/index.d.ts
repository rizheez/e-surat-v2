import { Config } from 'ziggy-js';

export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    unit_id?: number | null;
    position_id?: number | null;
    is_active?: boolean;
    roles?: string[];
    primary_role?: string | null;
    unit?: Unit | null;
    position?: Position | null;
}

export interface Role {
    id: number;
    name: string;
}

export interface Unit {
    id: number;
    nama: string;
    kode: string;
    parent_id?: number | null;
    is_cross_unit_target?: boolean;
    parent?: Unit | null;
}

export interface Position {
    id: number;
    nama: string;
    level: number;
    unit_id?: number | null;
}

export interface LetterCategory {
    id: number;
    nama: string;
    kode: string;
    deskripsi?: string | null;
}

export interface LetterNature {
    id: number;
    nama: string;
    kode: string;
    level_kerahasiaan: number;
}

export interface ArchiveClassification {
    id: number;
    nama: string;
    kode: string;
    masa_retensi?: number | null;
}

export interface DispositionInstructionTemplate {
    id: number;
    judul: string;
    isi_instruksi: string;
}

export interface IncomingLetter {
    id: number;
    nomor_agenda: string;
    nomor_surat: string;
    tanggal_surat: string;
    tanggal_diterima: string;
    asal_surat: string;
    perihal: string;
    ringkasan?: string | null;
    sifat_surat_id: number;
    kategori_surat_id: number;
    has_file?: boolean;
    file_url?: string | null;
    preview_url?: string | null;
    status: string;
    nature?: LetterNature;
    category?: LetterCategory;
    created_by?: number;
    created_by_user?: User;
    created_by_relation?: User;
    createdBy?: User;
    dispositions?: Disposition[];
}

export interface OutgoingLetter {
    id: number;
    nomor_surat_keluar: string;
    tanggal_surat: string;
    tujuan_surat: string;
    perihal: string;
    ringkasan?: string | null;
    kategori_surat_id: number;
    signatory_user_id?: number | null;
    content_mode?: 'upload' | 'generate';
    lampiran_text?: string | null;
    kepada_text?: string | null;
    lokasi_tujuan?: string | null;
    salam_pembuka?: string | null;
    isi_surat?: string | null;
    lampiran_detail?: string | null;
    penutup_text?: string | null;
    penandatangan_jabatan?: string | null;
    penandatangan_nama?: string | null;
    tembusan_text?: string | null;
    has_file?: boolean;
    file_url?: string | null;
    preview_url?: string | null;
    pdf_download_url?: string | null;
    status: string;
    approval_requested_at?: string | null;
    approved_at?: string | null;
    approval_note?: string | null;
    category?: LetterCategory;
    createdBy?: User;
    signatory?: User | null;
}

export interface DispositionRecipient {
    id: number;
    recipient_id: number;
    unit_id?: number | null;
    status: string;
    tanggal_dibaca?: string | null;
    tanggal_selesai?: string | null;
    recipient?: User;
    unit?: Unit;
}

export interface DispositionFollowup {
    id: number;
    recipient_id: number;
    catatan: string;
    status: string;
    has_file?: boolean;
    file_url?: string | null;
    created_at: string;
    recipient?: User;
}

export interface Disposition {
    id: number;
    incoming_letter_id: number;
    parent_disposition_id?: number | null;
    sender_id: number;
    instruksi: string;
    catatan?: string | null;
    batas_waktu?: string | null;
    status: string;
    tanggal_disposisi: string;
    incomingLetter?: IncomingLetter;
    sender?: User;
    parent?: Disposition | null;
    recipients?: DispositionRecipient[];
    followups?: DispositionFollowup[];
    children?: Disposition[];
    current_user_recipient?: DispositionRecipient | null;
}

export interface ActivityTimelineItem {
    id: number;
    log_name: string;
    description: string;
    created_at: string;
    user?: User | null;
    properties?: Record<string, unknown> | null;
}

export interface Option {
    value: string;
    label: string;
}

export interface AppNotification {
    id: string;
    type: string;
    title: string;
    body: string;
    sender?: string | null;
    url?: string | null;
    read_at?: string | null;
    created_at?: string | null;
    batas_waktu?: string | null;
}

export interface Paginator<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
        permissions: string[];
        roles: string[];
    };
    flash: {
        success?: string;
        error?: string;
    };
    notifications: {
        unread_count: number;
        pending_approvals_count: number;
        items: AppNotification[];
    };
    ziggy: Config & { location: string };
};
