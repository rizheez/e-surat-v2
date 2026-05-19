export const DEFAULT_SALAM_PEMBUKA = "Assalamu'alaikum Wr. Wb.";

export const DEFAULT_PENUTUP_TEXT =
    'Demikian disampaikan untuk dilaksanakan sebagaimana mestinya. Atas perhatian dan kerja samanya diucapkan terima kasih.';

export const DEFAULT_TEMBUSAN_TEXT = 'Pertinggal';

export function toParagraphs(value?: string | null): string[] {
    return (value ?? '')
        .split(/\r?\n\s*\r?\n/)
        .map((item) => item.trim())
        .filter(Boolean);
}

export function toLineItems(value?: string | null): string[] {
    return (value ?? '')
        .split(/\r?\n/)
        .map((item) => item.trim())
        .filter(Boolean);
}
