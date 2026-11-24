/**
 * Format angka menjadi format mata uang Rupiah
 * Contoh: 1250000 -> "Rp1.250.000"
 */
export function formatRupiah(amount, withSymbol = true) {
    const num = Number(amount) || 0;
    const isNegative = num < 0;
    const absoluteFormatted = Math.abs(num)
        .toFixed(0)
        .replace(/\B(?=(\d{3})+(?!\d))/g, '.');

    if (!withSymbol) {
        return (isNegative ? '-' : '') + absoluteFormatted;
    }

    return (isNegative ? '-Rp' : 'Rp') + absoluteFormatted;
}

/**
 * Format tanggal dalam format Indonesia
 * Contoh: "2026-10-28" -> "28 Okt 2026"
 */
export function formatDateIndo(dateString, format = 'short') {
    if (!dateString) return '-';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString;

    const bulanPanjang = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    const bulanPendek = [
        'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'
    ];

    const day = date.getDate();
    const month = format === 'long' ? bulanPanjang[date.getMonth()] : bulanPendek[date.getMonth()];
    const year = date.getFullYear();

    if (format === 'datetime') {
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${day} ${bulanPendek[date.getMonth()]} ${year}, ${hours}:${minutes} WIB`;
    }

    return `${day} ${month} ${year}`;
}

/**
 * Format jam dalam format Indonesia (HH:mm)
 * Contoh: "2026-11-24T03:40:51.000000Z" -> "10:40 WIB"
 */
export function formatTime(dateString, withWib = true) {
    if (!dateString) return '';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return '';

    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');

    return `${hours}:${minutes}${withWib ? ' WIB' : ''}`;
}

/**
 * Format tanggal dan jam transaksi dalam format Indonesia
 * Menggabungkan tanggal transaksi (date) dan waktu (time atau created_at).
 * Contoh: ("2026-11-02", "14:30") -> "2 Nov 2026, 14:30 WIB"
 * Contoh: ("2026-11-02", null, "2026-11-02T03:40:51Z") -> "2 Nov 2026, 10:40 WIB"
 */
export function formatTransactionDate(dateStr, timeOrCreatedAt = null, fallbackCreatedAt = null, withWib = true) {
    if (!dateStr && !timeOrCreatedAt && !fallbackCreatedAt) return '-';

    let day, month, year;
    if (dateStr) {
        const parts = String(dateStr).split('T')[0].split('-');
        if (parts.length === 3) {
            year = parseInt(parts[0], 10);
            const mIdx = parseInt(parts[1], 10) - 1;
            day = parseInt(parts[2], 10);
            const bulanPendek = [
                'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
                'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'
            ];
            month = bulanPendek[mIdx] || parts[1];
        }
    }

    if (!day || !month || !year) {
        const d = new Date(dateStr || timeOrCreatedAt || fallbackCreatedAt);
        if (isNaN(d.getTime())) return dateStr || '-';
        day = d.getDate();
        const bulanPendek = [
            'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
            'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'
        ];
        month = bulanPendek[d.getMonth()];
        year = d.getFullYear();
    }

    // 1. Explicit time string: "14:30" or "14:30:00"
    if (timeOrCreatedAt && typeof timeOrCreatedAt === 'string' && /^\d{1,2}:\d{2}(:\d{2})?$/.test(timeOrCreatedAt.trim())) {
        const parts = timeOrCreatedAt.trim().split(':');
        const hours = parts[0].padStart(2, '0');
        const minutes = parts[1].padStart(2, '0');
        return `${day} ${month} ${year}, ${hours}:${minutes}${withWib ? ' WIB' : ''}`;
    }

    // 2. ISO datetime string (fallback or timeOrCreatedAt if ISO format)
    const timeSource = fallbackCreatedAt || (timeOrCreatedAt && String(timeOrCreatedAt).includes('T') ? timeOrCreatedAt : null) || (dateStr && String(dateStr).includes('T') ? dateStr : null);
    if (timeSource) {
        const t = new Date(timeSource);
        if (!isNaN(t.getTime())) {
            const hours = String(t.getHours()).padStart(2, '0');
            const minutes = String(t.getMinutes()).padStart(2, '0');
            return `${day} ${month} ${year}, ${hours}:${minutes}${withWib ? ' WIB' : ''}`;
        }
    }

    return `${day} ${month} ${year}`;
}

/**
 * Format periode bulan YYYY-MM
 * Contoh: "2026-10" -> "Oktober 2026"
 */
export function formatPeriodIndo(periodString) {
    if (!periodString) return '-';
    const parts = periodString.split('-');
    if (parts.length !== 2) return periodString;

    const bulanPanjang = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    const monthIdx = parseInt(parts[1], 10) - 1;
    if (monthIdx >= 0 && monthIdx < 12) {
        return `${bulanPanjang[monthIdx]} ${parts[0]}`;
    }
    return periodString;
}

/**
 * Format persentase
 */
export function formatPercent(value) {
    const num = Number(value) || 0;
    return `${Math.round(num)}%`;
}
