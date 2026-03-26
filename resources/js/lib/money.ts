/** MXN display from integer centavos. */
export function formatCentsMx(cents: number): string {
    return (cents / 100).toLocaleString('es-MX', {
        style: 'currency',
        currency: 'MXN',
    });
}
