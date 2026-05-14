<x-mail::message>
# {{ $template?->name ?? 'Reporte de gastos' }}

Tu reporte programado está listo. Lo encontrarás adjunto a este correo.

- **Frecuencia:** {{ $cadenceLabel }}
- **Formato:** {{ $format }}
- **Generado:** {{ $generatedAt->format('d/m/Y H:i') }} (CDMX)

@if($template?->description)
> {{ $template->description }}
@endif

Gracias,
**IDHYAL**
</x-mail::message>
