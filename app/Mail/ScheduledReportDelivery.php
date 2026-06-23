<?php

namespace App\Mail;

use App\Models\ReportSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ScheduledReportDelivery extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ReportSchedule $schedule,
        public string $attachmentPath,
        public string $attachmentName,
    ) {}

    public function envelope(): Envelope
    {
        $template = $this->schedule->template;
        $title = $template?->name ?? 'Reporte de gastos';

        return new Envelope(
            subject: "[IDHYAL] Reporte programado: {$title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.scheduled-report-delivery',
            with: [
                'template' => $this->schedule->template,
                'cadenceLabel' => match ($this->schedule->cadence) {
                    'daily' => 'diaria',
                    'weekly' => 'semanal',
                    'monthly' => 'mensual',
                    default => $this->schedule->cadence,
                },
                'format' => strtoupper($this->schedule->format),
                'generatedAt' => now('America/Mexico_City'),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->attachmentPath)
                ->as($this->attachmentName),
        ];
    }
}
