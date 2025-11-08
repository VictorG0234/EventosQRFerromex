<?php

namespace App\Services;

use App\Models\Guest;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QrCodeService
{
    /**
     * Generar código QR para un invitado
     */
    public function generateQrCode(Guest $guest): string
    {
        // Datos que irán en el QR (sin premios_rifa por seguridad)
        $qrData = [
            'guest_id' => $guest->id,
            'event_id' => $guest->event_id,
            'numero_empleado' => $guest->numero_empleado,
            'full_name' => $guest->full_name,
            'timestamp' => now()->timestamp,
            'hash' => $this->generateHash($guest)
        ];

        // Convertir a JSON
        $qrDataJson = json_encode($qrData);

        // Crear el código QR
        $qrCode = QrCode::create($qrDataJson)
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(ErrorCorrectionLevel::Low)
            ->setSize(300)
            ->setMargin(10)
            ->setRoundBlockSizeMode(RoundBlockSizeMode::Margin);

        // Crear el writer
        $writer = new PngWriter();

        // Generar la imagen
        $result = $writer->write($qrCode);

        // Generar nombre de archivo único
        $filename = 'qr_codes/' . $guest->event_id . '/' . Str::slug($guest->numero_empleado . '_' . $guest->nombre) . '_' . time() . '.png';

        // Guardar en storage
        Storage::disk('public')->put($filename, $result->getString());

        // Actualizar el invitado con la ruta del QR
        $guest->update([
            'qr_code_path' => $filename,
            'qr_code_data' => $qrDataJson
        ]);

        return $filename;
    }

    /**
     * Validar un código QR escaneado
     */
    public function validateQrCode(string $qrData, int $eventId): array
    {
        try {
            $data = json_decode($qrData, true);

            if (!$data) {
                throw new \Exception('Código QR inválido - formato incorrecto');
            }

            // Verificar campos requeridos
            $requiredFields = ['guest_id', 'event_id', 'numero_empleado', 'full_name', 'hash'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    throw new \Exception("Código QR inválido - falta campo: {$field}");
                }
            }

            // Verificar que pertenezca al evento correcto
            if ($data['event_id'] != $eventId) {
                throw new \Exception('Este código QR no pertenece a este evento');
            }

            // Buscar el invitado
            $guest = Guest::where('id', $data['guest_id'])
                ->where('event_id', $eventId)
                ->where('numero_empleado', $data['numero_empleado'])
                ->first();

            if (!$guest) {
                throw new \Exception('Invitado no encontrado o datos inconsistentes');
            }

            // Validar el hash
            if ($data['hash'] !== $this->generateHash($guest)) {
                throw new \Exception('Código QR inválido - hash de seguridad incorrecto');
            }

            return [
                'valid' => true,
                'guest' => $guest,
                'data' => $data,
                'message' => 'Código QR válido'
            ];

        } catch (\Exception $e) {
            return [
                'valid' => false,
                'guest' => null,
                'data' => null,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Generar hash de seguridad para el invitado
     */
    protected function generateHash(Guest $guest): string
    {
        $data = $guest->id . $guest->event_id . $guest->numero_empleado . $guest->full_name;
        return hash('sha256', $data . config('app.key'));
    }

    /**
     * Regenerar códigos QR para múltiples invitados
     */
    public function regenerateMultiple(array $guests): array
    {
        $results = [
            'success' => 0,
            'errors' => []
        ];

        foreach ($guests as $guest) {
            try {
                $this->generateQrCode($guest);
                $results['success']++;
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'guest_id' => $guest->id,
                    'numero_empleado' => $guest->numero_empleado,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Eliminar archivo QR del storage
     */
    public function deleteQrFile(Guest $guest): bool
    {
        if ($guest->qr_code_path && Storage::disk('public')->exists($guest->qr_code_path)) {
            return Storage::disk('public')->delete($guest->qr_code_path);
        }

        return true;
    }

    /**
     * Obtener la URL pública del código QR
     */
    public function getQrUrl(Guest $guest): ?string
    {
        if (!$guest->qr_code_path) {
            return null;
        }

        return Storage::disk('public')->url($guest->qr_code_path);
    }

    /**
     * Verificar si el archivo QR existe
     */
    public function qrFileExists(Guest $guest): bool
    {
        return $guest->qr_code_path && Storage::disk('public')->exists($guest->qr_code_path);
    }

    /**
     * Generar QR en base64 para envío por email
     */
    public function generateQrBase64(Guest $guest): string
    {
        // Si ya existe el archivo, leerlo
        if ($this->qrFileExists($guest)) {
            $content = Storage::disk('public')->get($guest->qr_code_path);
            return base64_encode($content);
        }

        // Si no existe, generarlo temporalmente
        $qrData = [
            'guest_id' => $guest->id,
            'event_id' => $guest->event_id,
            'numero_empleado' => $guest->numero_empleado,
            'full_name' => $guest->full_name,
            'timestamp' => now()->timestamp,
            'hash' => $this->generateHash($guest)
        ];

        $qrCode = QrCode::create(json_encode($qrData))
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(ErrorCorrectionLevel::Low)
            ->setSize(300)
            ->setMargin(10)
            ->setRoundBlockSizeMode(RoundBlockSizeMode::Margin);

        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        return base64_encode($result->getString());
    }
}