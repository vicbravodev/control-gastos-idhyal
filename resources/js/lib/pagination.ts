/** Laravel paginator link labels often contain HTML entities. */
export function decodePaginationLabel(html: string): string {
    return html
        .replace(/<[^>]*>/g, '')
        .replace(/&laquo;/g, '«')
        .replace(/&raquo;/g, '»')
        .replace(/&lsaquo;/g, '‹')
        .replace(/&rsaquo;/g, '›')
        .replace(/&amp;/g, '&');
}
