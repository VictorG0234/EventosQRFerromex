<?php

namespace App\Services;

use App\Models\Guest;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;

class InvitationImageService
{
    /**
     * Genera una imagen de invitación con el QR code montado
     * 
     * @param Guest $guest
     * @return string Path de la imagen generada
     */
    public function generateInvitationWithQR(Guest $guest): string
    {
        // Ruta de la imagen base
        $baseImagePath = public_path('images/emails/invitacion/Invitacion.png');
        
        // Verificar que existe la imagen base
        if (!file_exists($baseImagePath)) {
            throw new \Exception('No se encontró la imagen base de invitación');
        }
        
        // Verificar que el invitado tiene QR
        if (!$guest->qr_code_path || !Storage::disk('public')->exists($guest->qr_code_path)) {
            throw new \Exception('El invitado no tiene código QR generado');
        }
        
        // Obtener la ruta completa del QR
        $qrPath = Storage::disk('public')->path($guest->qr_code_path);
        
        // Cargar la imagen base
        $invitationImage = Image::make($baseImagePath);
        
        // Obtener dimensiones de la imagen base
        $baseWidth = $invitationImage->width();
        $baseHeight = $invitationImage->height();
        
        // Cargar el QR y redimensionarlo
        // La imagen mide 1428 × 2877, el QR lo vamos a poner a un tamaño proporcional
        $qrSize = 650; // Tamaño del QR en píxeles (puedes ajustar esto)
        $qrImage = Image::make($qrPath)->resize($qrSize, $qrSize);
        
        // Calcular posición para centrar el QR horizontalmente
        // Y posicionarlo verticalmente donde está el espacio en blanco
        $qrX = ($baseWidth - $qrSize) / 2; // Centrado horizontal
        $qrY = 950; // Posición vertical (ajustada más arriba para el espacio en blanco)
        
        // Insertar el QR en la imagen base
        $invitationImage->insert($qrImage, 'top-left', (int)$qrX, (int)$qrY);
        
        // Crear directorio si no existe
        $outputDir = storage_path('app/public/invitations');
        if (!file_exists($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        
        // Generar nombre único para la imagen
        $filename = 'invitation_' . $guest->id . '_' . time() . '.png';
        $outputPath = $outputDir . '/' . $filename;
        
        // Guardar la imagen
        $invitationImage->save($outputPath, 90); // 90 es la calidad
        
        // Retornar la ruta relativa
        return 'invitations/' . $filename;
    }
    
    /**
     * Genera invitación y retorna la imagen directamente para descarga
     */
    public function downloadInvitationWithQR(Guest $guest)
    {
        $baseImagePath = public_path('images/emails/invitacion/Invitacion.png');
        
        if (!file_exists($baseImagePath)) {
            throw new \Exception('No se encontró la imagen base de invitación');
        }
        
        if (!$guest->qr_code_path || !Storage::disk('public')->exists($guest->qr_code_path)) {
            throw new \Exception('El invitado no tiene código QR generado');
        }
        
        $qrPath = Storage::disk('public')->path($guest->qr_code_path);
        $invitationImage = Image::make($baseImagePath);
        
        $baseWidth = $invitationImage->width();
        $qrSize = 650;
        $qrImage = Image::make($qrPath)->resize($qrSize, $qrSize);
        
        $qrX = ($baseWidth - $qrSize) / 2;
        $qrY = 950;
        
        $invitationImage->insert($qrImage, 'top-left', (int)$qrX, (int)$qrY);
        
        return $invitationImage->response('png', 90);
    }
}
