<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Persona;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use GuzzleHttp\Client;

class PersonaController extends Controller
{
    protected function getPersona($id): ?Persona{
        return Persona::select('rcvealerta')
            ->where('rcvealerta', $id)
            ->with('odisea:ID,TipoAlerta')
            ->first();
    }

    public function show($id)
    {
        $persona = $this->getPersona($id);

        if (!$persona) {
            return response()->json(['message' => 'Registro de persona no encontrado'], 404);
        }

        $persona->ruta_imagen = "http://10.210.4.156/f2_alertaodisea/Imagenes/{$persona->rcvealerta}.jpeg";

        return response()->json($persona);
    }

    public function imagenGrande($id)
    {
        return $this->procesarImagen($id, 'grande');
    }

    public function imagenMiniatura($id)
    {
        return $this->procesarImagen($id, 'thumb');
    }

    protected function procesarImagen($id, $tipo = 'grande'){
        $type = "{$tipo}s";

        $test = Storage::disk('public')->get("personas/$type/{$id}.jpeg");
        $test2 = Storage::disk('public')->get("plateadas/$type/{$id}.jpeg");

        if ($test || $test2){
            return response($test ?? $test2)->header('Content-Type', 'image/jpeg');
        }

        $persona = $this->getPersona($id);

        if (!$persona) {
            return response()->json(['message' => 'Persona no encontrada'], 404);
        }

        $isPerson = $persona->odisea->TipoAlerta !== 'PLATEADA';
        $nombreArchivo = "{$persona->rcvealerta}.jpeg";

        $directorio = $this->obtenerDirectorio($tipo, $isPerson ? 'personas' : 'plateadas');
        $rutaLocal = storage_path("app/public/{$directorio}/{$nombreArchivo}");
        $urlRemota = $this->obtenerUrlRemota($persona->rcvealerta, $isPerson);

        if (file_exists($rutaLocal)) {
            return response()->file($rutaLocal, ['Content-Type' => 'image/jpeg']);
        }

        try {
            $contenido = $this->descargarImagen($urlRemota);
            $imagen = $this->procesarConIntervention($contenido, $tipo);

            Storage::makeDirectory("public/{$directorio}");

            file_put_contents($rutaLocal, $imagen->toJpeg(80));

            return response($imagen->toJpeg(80))
                ->header('Content-Type', 'image/jpeg');
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al procesar la imagen.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function obtenerDirectorio(string $tipo, string $grupo): string
    {
        return "{$grupo}/" . ($tipo === 'thumb' ? 'thumbs' : 'grandes');
    }

    protected function obtenerUrlRemota(string $nombreArchivo, bool $esPersona): string
    {
        $base = $esPersona
            ? "http://10.210.4.156/f2_alertaodisea/Imagenes"
            : "http://10.210.4.156/f1_alertaplata/Imagenes";

        return "{$base}/{$nombreArchivo}.jpeg";
    }

    protected function descargarImagen(string $url): string
    {
        $client = new Client(['timeout' => 10.0]);

        $respuesta = $client->get($url, [
            'headers' => [
                'User-Agent' => 'Mozilla/5.0'
            ]
        ]);

        if ($respuesta->getStatusCode() !== 200) {
            throw new \Exception("No se pudo acceder a la imagen remota.");
        }

        return (string) $respuesta->getBody();
    }

    protected function procesarConIntervention(string $contenido, string $tipo)
    {
        $driver = $this->obtenerDriverImagen();

        $manager = new ImageManager($driver);
        $imagen = $manager->read($contenido);

        return $tipo === 'thumb'
            ? $imagen->resize(80, 80)
            : $imagen->resize(320, 320, fn ($c) => $c->aspectRatio()->upsize());
    }

    protected function obtenerDriverImagen()
    {
        if (extension_loaded('gd')) {
            return new GdDriver();
        }

        if (extension_loaded('imagick')) {
            return new ImagickDriver();
        }

        throw new \RuntimeException("No se encontró un driver válido para imágenes.");
    }
}
