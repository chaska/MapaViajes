<?php
/**
 * Idiomas de Mapa de viajes: comprobar una traducción, crear la plantilla de un idioma nuevo y poner los idiomas en los manifiestos.
 *
 *   php build/idiomas.php comprobar fr-FR      valida una traducción (la misma comprobación que hace el empaquetador)
 *   php build/idiomas.php plantilla fr-FR      crea la carpeta fr-FR en todas las extensiones, lista para traducir
 *   php build/idiomas.php manifiestos          pone en los manifiestos XML los idiomas que haya
 *   php build/idiomas.php todos                comprueba todos los idiomas y que los manifiestos estén al día
 *
 * Opción: --root=CARPETA  (por defecto, la raíz del repositorio; sirve para las pruebas).
 * El idioma de referencia es en-GB: una traducción tiene que tener exactamente sus mismos archivos y sus mismas claves.
 */

declare(strict_types=1);

final class Idiomas
{
	public const REF = 'en-GB';

	/** carpeta de idiomas => manifiesto que la lista y valor de folder="" de ese manifiesto */
	public const GRUPOS = [
		'src/component/frontend/language'                  => ['src/component/mapaviajes.xml', 'frontend/language'],
		'src/component/backend/language'                   => ['src/component/mapaviajes.xml', 'backend/language'],
		'src/package/language'                             => ['src/package/pkg_mapaviajes.xml', 'language'],
		'src/plugins/content/mapaviajes/language'          => ['src/plugins/content/mapaviajes/mapaviajes.xml', 'language'],
		'src/plugins/user/mapaviajes/language'             => ['src/plugins/user/mapaviajes/mapaviajes.xml', 'language'],
		'src/plugins/privacy/mapaviajes/language'          => ['src/plugins/privacy/mapaviajes/mapaviajes.xml', 'language'],
	];

	public static function etiquetaValida(string $tag): bool
	{
		return preg_match('/^[a-z]{2,3}-[A-Z]{2}$/', $tag) === 1;
	}

	/** Idiomas que hay (por las carpetas de la referencia y las demás), referencia y es-ES primero. @return list<string> */
	public static function idiomas(string $root): array
	{
		$tags = [];

		foreach (array_keys(self::GRUPOS) as $dir) {
			foreach (glob($root . '/' . $dir . '/*', GLOB_ONLYDIR) ?: [] as $d) {
				$tags[basename($d)] = true;
			}
		}

		$tags = array_keys($tags);
		usort($tags, static function (string $a, string $b): int {
			$orden = [self::REF => 0, 'es-ES' => 1];

			return [$orden[$a] ?? 2, $a] <=> [$orden[$b] ?? 2, $b];
		});

		return $tags;
	}

	/** @return array{keys: array<string, string>, problems: list<string>} */
	public static function leer(string $path): array
	{
		$out = ['keys' => [], 'problems' => []];
		$raw = (string) file_get_contents($path);

		if (!mb_check_encoding($raw, 'UTF-8')) {
			$out['problems'][] = 'no es UTF-8';

			return $out;
		}

		if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $raw) === 1) {
			$out['problems'][] = 'lleva caracteres de control';
		}

		foreach (preg_split('/\r\n|\n|\r/', $raw) ?: [] as $n => $line) {
			$t = trim($line);

			if ($t === '' || $t[0] === ';') {
				continue;
			}

			if (preg_match('/^([A-Z][A-Z0-9_]*)="(.*)"$/', $t, $m) !== 1) {
				$out['problems'][] = 'línea ' . ($n + 1) . ' con formato no válido (debe ser CLAVE="texto")';

				continue;
			}

			if (isset($out['keys'][$m[1]])) {
				$out['problems'][] = 'clave repetida: ' . $m[1];
			}

			$out['keys'][$m[1]] = $m[2];
		}

		return $out;
	}

	/** Marcadores %s %d %1$s %% de un texto, ordenados. @return list<string> */
	private static function marcadores(string $v): array
	{
		preg_match_all('/%(?:\d+\$)?[sd%]/', $v, $m);
		sort($m[0]);

		return $m[0];
	}

	/** Lo que nunca puede llevar un texto traducido (es texto que se muestra en la web de otra persona). @return list<string> */
	private static function peligros(string $v): array
	{
		$p = [];

		if (preg_match('/<[A-Za-z\/!?]/', $v) === 1) {
			$p[] = 'lleva etiquetas HTML';
		}

		if (preg_match('/(?:javascript|vbscript)\s*:|\bdata:\S/i', $v) === 1) {
			$p[] = 'lleva javascript:, vbscript: o una dirección data:';
		}

		if (preg_match('/\bon[a-z]{3,}\s*=/i', $v) === 1) {
			$p[] = 'lleva un manejador de eventos (on…=)';
		}

		if (preg_match('~(?:https?:)?//[a-z0-9][a-z0-9-]*\.[a-z]{2,}~i', $v) === 1 || preg_match('/\bwww\.[a-z0-9-]+\./i', $v) === 1) {
			$p[] = 'lleva una dirección externa';
		}

		if (preg_match('/&#?[a-z0-9]+;/i', $v) === 1) {
			$p[] = 'lleva una entidad HTML (&…;): escribe el carácter tal cual';
		}

		return $p;
	}

	/** @return list<string> problemas de un idioma (vacío = correcto) */
	public static function comprobar(string $root, string $tag): array
	{
		$pr = [];

		if (!self::etiquetaValida($tag)) {
			return ['«' . $tag . '» no es un código de idioma válido (ejemplo: fr-FR, pt-BR)'];
		}

		foreach (self::GRUPOS as $dir => $_) {
			$refDir = $root . '/' . $dir . '/' . self::REF;
			$dir2   = $root . '/' . $dir . '/' . $tag;

			if (!is_dir($refDir)) {
				$pr[] = $dir . ': falta la referencia ' . self::REF;

				continue;
			}

			if (!is_dir($dir2)) {
				$pr[] = $dir . '/' . $tag . ': falta la carpeta (solo se aceptan idiomas completos)';

				continue;
			}

			$ref   = array_map('basename', glob($refDir . '/*') ?: []);
			$mios  = array_map('basename', glob($dir2 . '/*') ?: []);
			sort($ref);
			sort($mios);

			foreach (array_diff($ref, $mios) as $f) {
				$pr[] = $dir . '/' . $tag . ': falta el archivo ' . $f;
			}

			foreach (array_diff($mios, $ref) as $f) {
				$pr[] = $dir . '/' . $tag . ': sobra el archivo ' . $f . ' (solo se admiten los de ' . self::REF . ')';
			}

			foreach (array_intersect($ref, $mios) as $f) {
				$rel = $dir . '/' . $tag . '/' . $f;

				if (!str_ends_with($f, '.ini') || is_link($dir2 . '/' . $f) || filesize($dir2 . '/' . $f) > 300000) {
					$pr[] = $rel . ': archivo no válido (solo .ini normales de menos de 300 KB)';

					continue;
				}

				$a = self::leer($refDir . '/' . $f);
				$b = self::leer($dir2 . '/' . $f);

				foreach ($b['problems'] as $x) {
					$pr[] = $rel . ': ' . $x;
				}

				foreach (array_diff_key($a['keys'], $b['keys']) as $k => $_v) {
					$pr[] = $rel . ': falta la clave ' . $k;
				}

				foreach (array_diff_key($b['keys'], $a['keys']) as $k => $_v) {
					$pr[] = $rel . ': sobra la clave ' . $k . ' (no existe en ' . self::REF . ')';
				}

				foreach ($b['keys'] as $k => $v) {
					if (!isset($a['keys'][$k])) {
						continue;
					}

					if ($v === '' && $a['keys'][$k] !== '') {
						$pr[] = $rel . ': ' . $k . ' está vacía';
					}

					if (self::marcadores($v) !== self::marcadores($a['keys'][$k])) {
						$pr[] = $rel . ': ' . $k . ' no conserva los marcadores (' . implode(' ', self::marcadores($a['keys'][$k])) . ')';
					}

					if (strlen($v) > 4 * strlen($a['keys'][$k]) + 200) {
						$pr[] = $rel . ': ' . $k . ' es demasiado larga';
					}

					foreach (self::peligros($v) as $x) {
						$pr[] = $rel . ': ' . $k . ' ' . $x;
					}
				}
			}
		}

		return $pr;
	}

	/** @return list<string> */
	public static function plantilla(string $root, string $tag): array
	{
		if (!self::etiquetaValida($tag) || $tag === self::REF) {
			return ['«' . $tag . '» no es un código de idioma válido o es el de referencia'];
		}

		$pr = [];

		foreach (self::GRUPOS as $dir => $_) {
			$origen  = $root . '/' . $dir . '/' . self::REF;
			$destino = $root . '/' . $dir . '/' . $tag;

			if (is_dir($destino)) {
				$pr[] = $dir . '/' . $tag . ' ya existe';

				continue;
			}

			if (!is_dir($origen) || !@mkdir($destino, 0755, true)) {
				$pr[] = 'no se pudo crear ' . $dir . '/' . $tag;

				continue;
			}

			foreach (glob($origen . '/*.ini') ?: [] as $f) {
				$txt = (string) file_get_contents($f);
				$txt = preg_replace('/\(' . preg_quote(self::REF, '/') . '\)/', '(' . $tag . ')', $txt, 1) ?? $txt;
				file_put_contents($destino . '/' . basename($f), $txt);
			}
		}

		return $pr;
	}

	/** Reescribe en cada manifiesto la lista de idiomas. @return array<string, string> ruta => contenido nuevo */
	public static function manifiestosNuevos(string $root): array
	{
		$tags = self::idiomas($root);
		$res  = [];

		foreach (self::GRUPOS as $dir => [$manif, $folder]) {
			$ruta = $root . '/' . $manif;
			$xml  = $res[$ruta] ?? (string) file_get_contents($ruta);
			$ficheros = array_map('basename', glob($root . '/' . $dir . '/' . self::REF . '/*.ini') ?: []);
			sort($ficheros);

			$xml = preg_replace_callback(
				'~(<languages folder="' . preg_quote($folder, '~') . '">)(.*?)(\s*</languages>)~s',
				static function (array $m) use ($tags, $ficheros): string {
					preg_match('/\n(\s*)<language /', $m[2], $ind);
					$sangria = $ind[1] ?? "\t\t";
					$lineas  = '';

					foreach ($tags as $t) {
						foreach ($ficheros as $f) {
							$lineas .= "\n" . $sangria . '<language tag="' . $t . '">' . $t . '/' . $f . '</language>';
						}
					}

					return $m[1] . $lineas . $m[3];
				},
				$xml,
				1
			) ?? $xml;
			$res[$ruta] = $xml;
		}

		return $res;
	}

	/** @return list<string> */
	public static function manifiestosAlDia(string $root): array
	{
		$pr = [];

		foreach (self::manifiestosNuevos($root) as $ruta => $nuevo) {
			if ($nuevo !== (string) file_get_contents($ruta)) {
				$pr[] = substr($ruta, strlen($root) + 1) . ': la lista de idiomas no está al día (ejecuta: php build/idiomas.php manifiestos)';
			}
		}

		return $pr;
	}

	/** Todo lo que el empaquetador exige. @return list<string> */
	public static function todo(string $root): array
	{
		$pr = [];

		foreach (self::idiomas($root) as $tag) {
			if (!self::etiquetaValida($tag)) {
				$pr[] = 'carpeta de idioma con nombre no válido: ' . $tag;

				continue;
			}

			array_push($pr, ...self::comprobar($root, $tag));
		}

		return array_merge($pr, self::manifiestosAlDia($root));
	}
}

if (PHP_SAPI === 'cli' && isset($argv[0]) && realpath($argv[0]) === __FILE__) {
	$root = dirname(__DIR__);
	$args = [];

	foreach (array_slice($argv, 1) as $a) {
		if (str_starts_with($a, '--root=')) {
			$root = rtrim(substr($a, 7), '/');
		} else {
			$args[] = $a;
		}
	}

	$orden = $args[0] ?? '';
	$tag   = $args[1] ?? '';
	$sal   = static function (array $problemas, string $bien): never {
		if ($problemas === []) {
			fwrite(STDOUT, $bien . "\n");
			exit(0);
		}

		fwrite(STDERR, "HAY QUE ARREGLAR:\n - " . implode("\n - ", $problemas) . "\n");
		exit(1);
	};

	switch ($orden) {
		case 'comprobar':
			$sal(Idiomas::comprobar($root, $tag), 'Traducción ' . $tag . ' correcta: archivos, claves y marcadores coinciden y no lleva nada peligroso.');
		case 'plantilla':
			$p = Idiomas::plantilla($root, $tag);

			if ($p === []) {
				foreach (Idiomas::manifiestosNuevos($root) as $ruta => $xml) {
					file_put_contents($ruta, $xml);
				}
			}

			$sal($p, 'Plantilla ' . $tag . ' creada en las 6 carpetas de idiomas y puesta en los manifiestos. Traduce los textos entre comillas (no las claves ni los %s/%d) y luego: php build/idiomas.php comprobar ' . $tag);
		case 'manifiestos':
			foreach (Idiomas::manifiestosNuevos($root) as $ruta => $xml) {
				file_put_contents($ruta, $xml);
			}

			$sal([], 'Manifiestos al día.');
		case 'todos':
			$sal(Idiomas::todo($root), 'Todos los idiomas correctos y manifiestos al día.');
		default:
			fwrite(STDERR, "Uso: php build/idiomas.php comprobar xx-XX | plantilla xx-XX | manifiestos | todos  [--root=CARPETA]\n");
			exit(2);
	}
}
