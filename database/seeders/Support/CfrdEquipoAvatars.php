<?php

namespace Database\Seeders\Support;

/**
 * Fotos oficiales del equipo (https://cfrd.udec.cl/equipo/). Archivos fuente en `public/equipo-cfrd/`.
 * En seed se guardan en BD como data URL JPEG en base64 (`data:image/jpeg;base64,...`).
 *
 * @see CfrdDevUserSeeder
 */
final class CfrdEquipoAvatars
{
    /**
     * Correo institucional => nombre de archivo en public/equipo-cfrd/ (descargado del sitio CFRD).
     *
     * @var array<string, string>
     */
    public const FILENAMES_BY_EMAIL = [
        'admin@cfrd.cl' => 'jaime-hernandez.jpg',
        'dbordon@udec.cl' => 'daniel-bordon.jpg',
        'claudiavaldes@udec.cl' => 'claudia-valdes.jpg',
        'jfigueroav@udec.cl' => 'eduardo-figueroa.jpg',
        'joparedes@udec.cl' => 'jose-paredes.jpg',
        'marcospalma@udec.cl' => 'marcos-palma.jpg',
        'lletelie@udec.cl' => 'leonardo-letelier.jpg',
        'pmartinez@udec.cl' => 'pedro-martinez.jpg',
        'fsuazo@udec.cl' => 'francisca-suazo.jpg',
        'aalcaino@udec.cl' => 'andrea-alcaino.jpg',
        'gerardolopez@udec.cl' => 'gerardo-lopez.jpg',
        'mmedel@udec.cl' => 'mauricio-medel.jpg',
        'cristinavergara@udec.cl' => 'cristina-vergara.jpg',
        'davtorres@udec.cl' => 'david-torres.jpg',
        'ivanburgos@udec.cl' => 'ivan-burgos.jpg',
        'marcelorivas@udec.cl' => 'marcelo-rivas.jpg',
        'djarac@udec.cl' => 'diego-jara.jpg',
        'hcerna@udec.cl' => 'hector-cerna.jpg',
        'antavila@udec.cl' => 'antonieta-avila.jpg',
        'moiseslizana@udec.cl' => 'moises-lizana.jpg',
        'macarenavalenzu@udec.cl' => 'macarena-valenzuela.jpg',
        'rrocham@udec.cl' => 'ricardo-rocha.jpg',
        'posorio@udec.cl' => 'paulina-osorio.jpg',
        'asilvan@udec.cl' => 'angelo-silva.jpg',
        'alontapia@udec.cl' => 'alonso-tapia.jpg',
        'manualbornoz@udec.cl' => 'manuel-albornoz.jpg',
        'frvillegas@udec.cl' => 'nicolas-villegas.jpg',
        'guitorres@udec.cl' => 'guillermo-torres.jpg',
        'pedrogodoy@udec.cl' => 'pedro-godoy.jpg',
        'cvicencio@udec.cl' => 'carlos-vicencio.jpg',
        'arhernandez@udec.cl' => 'ariel-hernandez.jpg',
        'jomanriquez@udec.cl' => 'jose-manriquez.jpg',
        'jaimhernandez@udec.cl' => 'jaime-hernandez.jpg',
        'pmasquiaran@udec.cl' => 'pablo-masquiaran.jpg',
        'alfredopacheco@udec.cl' => 'alfredo-pacheco.jpg',
        'gceballos@udec.cl' => 'guillermo-ceballos.jpg',
    ];

    /**
     * Lee el JPEG desde `public/equipo-cfrd/` y devuelve data URL con imagen en base64.
     */
    public static function dataUrlForEmail(string $email): ?string
    {
        $key = strtolower(trim($email));
        $file = self::FILENAMES_BY_EMAIL[$key] ?? null;
        if ($file === null) {
            return null;
        }

        $path = public_path('equipo-cfrd/'.$file);
        if (! is_readable($path)) {
            return null;
        }

        $binary = file_get_contents($path);
        if ($binary === false || $binary === '') {
            return null;
        }

        return 'data:image/jpeg;base64,'.base64_encode($binary);
    }
}
