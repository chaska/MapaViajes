<?php
declare(strict_types=1);
// Colaborar con idiomas: la plantilla, la comprobación de una traducción y el candado del empaquetador. Trabaja en una copia temporal.
require __DIR__ . '/aserciones.php';
require __DIR__ . '/../build/idiomas.php';

$repo = dirname(__DIR__);
$tmp  = sys_get_temp_dir() . '/mv-idiomas-' . bin2hex(random_bytes(4));
mkdir($tmp, 0755, true);

function copiar(string $de, string $a): void { mkdir($a, 0755, true); foreach (scandir($de) ?: [] as $f) { if ($f === '.' || $f === '..') { continue; } is_dir("$de/$f") ? copiar("$de/$f", "$a/$f") : copy("$de/$f", "$a/$f"); } }
function borrar(string $d): void { if (!str_starts_with($d, sys_get_temp_dir() . '/mv-idiomas-')) { return; } foreach (scandir($d) ?: [] as $f) { if ($f === '.' || $f === '..') { continue; } is_dir("$d/$f") ? borrar("$d/$f") : unlink("$d/$f"); } rmdir($d); }
function nueva(string $repo, string $tmp, string $n): string { $r = "$tmp/$n"; foreach (array_keys(Idiomas::GRUPOS) as $g) { copiar("$repo/$g", "$r/$g"); } foreach (array_unique(array_column(Idiomas::GRUPOS, 0)) as $m) { @mkdir(dirname("$r/$m"), 0755, true); copy("$repo/$m", "$r/$m"); } return $r; }
function cambiar(string $r, string $tag, string $file, callable $f): void { foreach (array_keys(Idiomas::GRUPOS) as $g) { $p = "$r/$g/$tag/$file"; if (is_file($p)) { file_put_contents($p, $f((string) file_get_contents($p))); return; } } }

seccion('Los idiomas que ya hay');
afirma(Idiomas::todo($repo) === [], 'en-GB y es-ES completos y manifiestos al día: ' . json_encode(Idiomas::todo($repo)));
afirma(Idiomas::idiomas($repo) === ['en-GB', 'es-ES'], 'los idiomas actuales son en-GB y es-ES');

seccion('Plantilla de un idioma nuevo');
$r = nueva($repo, $tmp, 'a');
afirma(Idiomas::plantilla($r, 'fr-FR') === [], 'crea fr-FR en las 6 carpetas');
foreach (Idiomas::manifiestosNuevos($r) as $ruta => $xml) { file_put_contents($ruta, $xml); }
afirma(Idiomas::comprobar($r, 'fr-FR') === [], 'la plantilla sin traducir ya es válida (mismas claves, mismos marcadores)');
afirma(Idiomas::todo($r) === [], 'y con los manifiestos puestos todo queda correcto: ' . json_encode(Idiomas::todo($r)));
afirma(substr_count((string) file_get_contents("$r/src/component/mapaviajes.xml"), 'tag="fr-FR"') === 3, 'el manifiesto del componente lista fr-FR (1 del mapa + 2 del administrador)');
afirma(substr_count((string) file_get_contents("$r/src/plugins/user/mapaviajes/mapaviajes.xml"), 'tag="fr-FR"') === 2, 'y el plugin de usuario también (2 archivos)');
afirma(Idiomas::plantilla($r, 'fr-FR') !== [], 'no pisa un idioma que ya existe');
afirma(Idiomas::plantilla($r, 'xx') !== [] && Idiomas::plantilla($r, '../x') !== [], 'rechaza códigos de idioma raros');

seccion('Una traducción honesta pasa');
cambiar($r, 'fr-FR', 'com_mapaviajes.ini', fn (string $t) => str_replace('COM_MAPAVIAJES_NO_ALBUMS="There is no map available to you yet."', 'COM_MAPAVIAJES_NO_ALBUMS="Aucune carte n\'est disponible pour vous."', $t));
afirma(Idiomas::comprobar($r, 'fr-FR') === [], 'un texto traducido, con apóstrofo y acentos, es válido');

seccion('Lo malo se rechaza');
$malos = [
	'falta una clave' => fn (string $t) => preg_replace('/^COM_MAPAVIAJES_NO_ALBUMS=.*\R/m', '', $t, 1),
	'sobra una clave' => fn (string $t) => $t . "\nCOM_MAPAVIAJES_INVENTADA=\"x\"\n",
	'clave repetida' => fn (string $t) => $t . "\nCOM_MAPAVIAJES=\"otra\"\n",
	'script' => fn (string $t) => str_replace('COM_MAPAVIAJES="Trip map"', 'COM_MAPAVIAJES="<script>alert(1)</script>"', $t),
	'html' => fn (string $t) => str_replace('COM_MAPAVIAJES="Trip map"', 'COM_MAPAVIAJES="<b>Mapa</b>"', $t),
	'javascript:' => fn (string $t) => str_replace('COM_MAPAVIAJES="Trip map"', 'COM_MAPAVIAJES="javascript:alert(1)"', $t),
	'manejador onerror=' => fn (string $t) => str_replace('COM_MAPAVIAJES="Trip map"', 'COM_MAPAVIAJES="x onerror=alert(1)"', $t),
	'dirección externa' => fn (string $t) => str_replace('COM_MAPAVIAJES="Trip map"', 'COM_MAPAVIAJES="Mapa https://malo.example.com/x"', $t),
	'www' => fn (string $t) => str_replace('COM_MAPAVIAJES="Trip map"', 'COM_MAPAVIAJES="ver www.malo.com"', $t),
	'entidad HTML' => fn (string $t) => str_replace('COM_MAPAVIAJES="Trip map"', 'COM_MAPAVIAJES="a &lt;script&gt;"', $t),
	'línea suelta' => fn (string $t) => $t . "\nesto no es una clave\n",
	'texto vacío' => fn (string $t) => str_replace('COM_MAPAVIAJES="Trip map"', 'COM_MAPAVIAJES=""', $t),
	'texto enorme' => fn (string $t) => str_replace('COM_MAPAVIAJES="Trip map"', 'COM_MAPAVIAJES="' . str_repeat('a', 5000) . '"', $t),
];
foreach ($malos as $nombre => $f) {
	$r2 = nueva($repo, $tmp, 'm' . md5($nombre)); Idiomas::plantilla($r2, 'fr-FR');
	foreach (Idiomas::manifiestosNuevos($r2) as $ruta => $xml) { file_put_contents($ruta, $xml); }
	cambiar($r2, 'fr-FR', 'com_mapaviajes.ini', $f);
	$p = Idiomas::comprobar($r2, 'fr-FR');
	afirma($p !== [] && Idiomas::todo($r2) !== [], "se rechaza: $nombre" . ($p ? '  (' . substr($p[0], strrpos($p[0], ': ') + 2) . ')' : ''));
}
// marcadores
$r3 = nueva($repo, $tmp, 'marc'); Idiomas::plantilla($r3, 'fr-FR'); foreach (Idiomas::manifiestosNuevos($r3) as $ruta => $xml) { file_put_contents($ruta, $xml); }
$ref = Idiomas::leer("$r3/src/component/frontend/language/en-GB/com_mapaviajes.ini")['keys']; $kS = ''; foreach ($ref as $k => $v) { if (str_contains($v, '%s')) { $kS = $k; break; } }
cambiar($r3, 'fr-FR', 'com_mapaviajes.ini', fn (string $t) => preg_replace('/^(' . $kS . '=")(.*)(")$/m', '$1sin marcador$3', $t, 1));
afirma($kS !== '' && count(array_filter(Idiomas::comprobar($r3, 'fr-FR'), fn ($x) => str_contains($x, 'marcadores'))) === 1, 'se rechaza quitar el marcador %s de ' . $kS);
// archivo sobrante y carpeta incompleta
$r4 = nueva($repo, $tmp, 'sob'); Idiomas::plantilla($r4, 'fr-FR'); foreach (Idiomas::manifiestosNuevos($r4) as $ruta => $xml) { file_put_contents($ruta, $xml); }
file_put_contents("$r4/src/component/frontend/language/fr-FR/malo.php", '<?php echo 1;');
afirma(count(array_filter(Idiomas::comprobar($r4, 'fr-FR'), fn ($x) => str_contains($x, 'sobra el archivo malo.php'))) === 1, 'se rechaza un archivo que no es de la referencia (un .php)');
$r5 = nueva($repo, $tmp, 'inc'); Idiomas::plantilla($r5, 'de-DE'); borrar("$r5/src/package/language/de-DE"); foreach (Idiomas::manifiestosNuevos($r5) as $ruta => $xml) { file_put_contents($ruta, $xml); }
afirma(Idiomas::comprobar($r5, 'de-DE') !== [], 'se rechaza un idioma incompleto (falta una carpeta)');

seccion('El empaquetador no deja pasar un idioma malo ni uno sin listar');
$r6 = nueva($repo, $tmp, 'build'); Idiomas::plantilla($r6, 'it-IT');
afirma(Idiomas::todo($r6) !== [] && count(array_filter(Idiomas::todo($r6), fn ($x) => str_contains($x, 'no está al día'))) >= 1, 'un idioma sin poner en los manifiestos se detecta (no saldría en el paquete)');
foreach (Idiomas::manifiestosNuevos($r6) as $ruta => $xml) { file_put_contents($ruta, $xml); }
afirma(Idiomas::todo($r6) === [], 'con los manifiestos puestos pasa');
cambiar($r6, 'it-IT', 'plg_user_mapaviajes.ini', fn (string $t) => $t . "\nPLG_USER_MAPAVIAJES_X=\"<img src=x onerror=alert(1)>\"\n");
afirma(Idiomas::todo($r6) !== [], 'y con una traducción maliciosa en cualquiera de los plugins, el empaquetador la rechazaría');
afirma(str_contains((string) file_get_contents($repo . '/build/build.php'), "Idiomas::todo(\$root)"), 'build.php llama a la comprobación de idiomas');

borrar($tmp);
fin();
