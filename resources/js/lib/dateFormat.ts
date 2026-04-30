export function formatDateChile(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    const isoMatch = value.match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (isoMatch) {
        const [, yyyy, mm, dd] = isoMatch;
        return `${dd}-${mm}-${yyyy}`;
    }

    const parsed = new Date(value);
    if (!Number.isNaN(parsed.getTime())) {
        const dd = String(parsed.getDate()).padStart(2, '0');
        const mm = String(parsed.getMonth() + 1).padStart(2, '0');
        const yyyy = parsed.getFullYear();
        return `${dd}-${mm}-${yyyy}`;
    }

    return value;
}

export function formatDateTimeChile(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) {
        return formatDateChile(value);
    }

    const dd = String(parsed.getDate()).padStart(2, '0');
    const mm = String(parsed.getMonth() + 1).padStart(2, '0');
    const yyyy = parsed.getFullYear();
    const hh = String(parsed.getHours()).padStart(2, '0');
    const min = String(parsed.getMinutes()).padStart(2, '0');

    return `${dd}-${mm}-${yyyy} ${hh}:${min}`;
}
