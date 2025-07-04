<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Odisea;
use App\Models\Persona;
use GuzzleHttp\Http\Request;
use GuzzleHttp\Client;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\GD\Driver;
use Intervention\Image\Drivers\GD\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Encoders\JpegEncoder;
use PhpParser\Node\Stmt\TryCatch;

class PersonaController extends Controller
{
  public function show($id) {

//   $persona = Persona::where('rcvealerta', $id)->first();
  $persona = Persona::where('rcvealerta', $id)
                        ->whereHas('odisea', function ($query) {
                            $query->where('TipoAlerta', '!=', 'PLATEADA');
                        })
                        ->with('odisea')
                        ->first();


    if (!$persona) {
        return response()->json([
            'message' => 'Registro de persona no encontrado'
        ], 404);
    }

    $persona->ruta_imagen = "http://10.210.4.156/f2_alertaodisea/Imagenes/{$persona->rcvealerta}.jpeg";
    return response()->json($persona);

   }

   public function imagenGrande($id) {

    return $this->procesarImagen($id, 'grande');

   }

   public function imagenMiniatura($id) {

    return $this->procesarImagen($id, 'thumb');

   }

   public function procesarImagen($id, $tipo = 'grande')
   {
        $persona = Persona::where('rcvealerta', $id)
                    ->whereHas('odisea', fn ($q) => $q->where('TipoAlerta', '!=', 'PLATEADA'))
                    ->with('odisea')->first();

        $plateada = Persona::where('rcvealerta', $id)
                    ->whereHas('odisea', fn ($q) => $q->where('TipoAlerta', 'PLATEADA'))
                    ->with('odisea')
                    ->first();

        if (!$persona && !$plateada) {
            return response()->json([
                'message' => 'Persona no encontrada'
            ], 404);
        }

        // Determinar tipo
        $modelo = $persona ?: $plateada;
        $nombreArchivo = "{$modelo->rcvealerta}.jpeg";

        if ($persona) {
            $directorio = $tipo === 'thumb' ? 'personas/thumbs' : 'personas/grandes';
            // Ruta remota
            $urlRemota = "http://10.210.4.156/f2_alertaodisea/Imagenes/{$nombreArchivo}";

        }  else {
            $directorio = $tipo === 'thumb' ? 'plateadas/thumbs' : 'plateadas/grandes';
            // Ruta remota
            $urlRemota = "http://10.210.4.156/f1_alertaplata/Imagenes/{$nombreArchivo}";
        }

        $rutaLocal = storage_path("app/public/{$directorio}/{$nombreArchivo}");

        // Si ya existe la imagen, regresar
        if (file_exists($rutaLocal)) {
            return response()->file($rutaLocal, ['Content-type' => 'image/jpeg']);
        }

        try {
            // Descargar la imagen de la URL externa
            $client = new \GuzzleHttp\Client();
            $respuesta = $client->get($urlRemota, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0'
                ]
            ]);

            if ($respuesta->getStatusCode() !== 200) {
                return response()->json([
                    'message' => 'Imagen remota no disponible'
                ], 404);
            }

            $contenido = (string) $respuesta->getBody();

            if (empty($contenido)) {
                return response()->json(['message' => 'La imagen descargada esta vacía.'], 400);
            }


            // Selección segura del driver
            if (extension_loaded('gd') && class_exists(GdDriver::class)) {
                $driver = new GdDriver();
            } elseif (extension_loaded('imagick') && class_exists(ImagickDriver::class)) {
                $driver = new ImagickDriver();
            } else {
                return response()->json([
                    'message' => 'No se encontró un driver válido para procesar la imágenes.'
                ], 500);
            }

            // Aplicar Intervention Image
            // $manager = new ImageManager(new Driver());
            $manager = new ImageManager($driver);
            $imagen = $manager->read($contenido);

            // Ajustar el tamaño según el tipo
            if ($tipo === 'thumb') {
                $imagen = $imagen->resize(100, 100);
            } else {
                $imagen->resize(800, 800, function ($c) {
                    $c->aspectRatio();
                    $c->upsize();
                });
            }

            // Verificar que la carpeta exista
            if (!file_exists(dirname($rutaLocal))) {
                mkdir(dirname($rutaLocal), 0777, true);
            }

            // Guardar en disco local
            file_put_contents($rutaLocal, $imagen->toJpeg(80));

            // Retornar imagen
            return response($imagen->toJpeg(80))
                    ->header('Content-type', 'image/jpeg');


        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al procesar la imagen.', 'error' => $e->getMessage()
            ], 500);
        }
   }

}
