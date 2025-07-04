<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;

class VerificarImagenDrivers extends Command
{
    protected $signature = 'verificar:drivers-imagen';
    protected $description = 'Verifica si los drivers GD o Imagick están disponibles y funcionales con Intervention Image v3';

    public function handle()
    {
        $this->info('Verificando soporte de drivers para Intervention Image...');

        // Verifica GD
        if (extension_loaded('gd')) {
            try {
                $manager = new ImageManager(new GdDriver());
                $this->info('✅ GD está disponible y funciona correctamente.');
            } catch (\Throwable $e) {
                $this->error('❌ GD está habilitado, pero falló al iniciar el driver: ' . $e->getMessage());
            }
        } else {
            $this->warn('⚠️ La extensión GD no está cargada.');
        }

        // Verifica Imagick
        if (extension_loaded('imagick')) {
            try {
                $manager = new ImageManager(new ImagickDriver());
                $this->info('✅ Imagick está disponible y funciona correctamente.');
            } catch (\Throwable $e) {
                $this->error('❌ Imagick está habilitado, pero falló al iniciar el driver: ' . $e->getMessage());
            }
        } else {
            $this->warn('⚠️ La extensión Imagick no está cargada.');
        }

        return 0;
    }
}
