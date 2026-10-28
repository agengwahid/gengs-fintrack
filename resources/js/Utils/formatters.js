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
