<?php

namespace App\Mail;

use App\Models\Visita;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NuevaVisitaRegistrada extends Mailable
{
    use Queueable, SerializesModels;

    public $visita;
    public $libros;

    /**
     * Crear una nueva instancia de mensaje con la visita y sus libros asociados.
     */
    public function __construct(Visita $visita, $libros = [])
    {
        $this->visita = $visita;
        $this->libros = $libros;
    }

    /**
     * Construir el mensaje.
     */
    public function build()
    {
        // Construcción dinámica del bloque HTML de los libros de interés con enlace
        $htmlLibros = '';
        if (!empty($this->libros) && count($this->libros) > 0) {
            $htmlLibros .= "<div style='margin-top: 20px; background-color: #f8fafc; padding: 15px; border-radius: 12px; border: 1px solid #e2e8f0;'>";
            $htmlLibros .= "<h4 style='color: #0f172a; margin-top: 0; margin-bottom: 10px;'>📚 Catálogos Digitales de su Interés:</h4>";
            $htmlLibros .= "<p style='font-size: 13px; margin-bottom: 12px;'>De acuerdo a lo conversado, ponemos a su disposición los accesos interactivos a los títulos solicitados:</p>";
            $htmlLibros .= "<ul style='padding-left: 20px; margin: 0;'>";
            
            foreach ($this->libros as $libro) {
                $htmlLibros .= "<li style='margin-bottom: 8px; font-size: 13px;'>";
                $htmlLibros .= "<strong>" . htmlspecialchars($libro->titulo) . "</strong> — ";
                $htmlLibros .= "<a href='" . htmlspecialchars($libro->link_flipbook) . "' target='_blank' style='color: #b91c1c; font-weight: bold; text-decoration: underline;'>Ver Flipbook</a>";
                $htmlLibros .= "</li>";
            }
            
            $htmlLibros .= "</ul>";
            $htmlLibros .= "</div>";
        }

        return $this->subject('Confirmación de Registro de Visita - ' . $this->visita->nombre_plantel)
                    ->html("
                        <div style='font-family: Arial, sans-serif; padding: 20px; color: #334155;'>
                            <h2 style='color: #b91c1c;'>Estimado(a),</h2>
                            <p>Le informamos que se ha registrado exitosamente la visita en nuestro sistema de control para el plantel <strong>{$this->visita->nombre_plantel}</strong>.</p>
                            <p>Agradecemos la atención brindada durante la entrevista efectuada con <strong>{$this->visita->persona_entrevistada}</strong> en su cargo de <strong>{$this->visita->cargo}</strong>.</p>
                            
                            {$htmlLibros}

                            <hr style='border: none; border-top: 1px dashed #cbd5e1; margin: 20px 0;'>
                            <p style='font-size: 12px; color: #64748b;'>Este es un mensaje automático, por favor no responda directamente a este correo.</p>
                        </div>
                    ");
    }
}