# Colaborar en Mapa de viajes

Gracias por querer ayudar. Esto es lo básico.

## Traducciones
La forma más fácil de colaborar. Guía paso a paso, en lenguaje sencillo: [`docs/TRADUCIR.md`](docs/TRADUCIR.md). Resumen: `php build/idiomas.php plantilla xx-XX`, traduces, `php build/idiomas.php comprobar xx-XX` y lo envías por GitHub.

## Avisar de un fallo
Abre un «Issue» y cuenta: qué hiciste, qué esperabas y qué pasó, la versión de Joomla y de Mapa de viajes. Si puedes, una captura **sin datos personales** (tapa nombres, correos y la dirección de tu web).

## Avisar de un fallo de seguridad
**No lo publiques en un Issue.** Usa el aviso privado de GitHub: en la página del repositorio, pestaña **Security › Report a vulnerability**. Así solo lo ve quien mantiene el proyecto. Cuenta el problema, cómo reproducirlo y qué versión afecta.

## Proponer cambios en el código
1. Abre primero un Issue para comentarlo.
2. Antes de enviar, ejecuta el empaquetador (`php build/build.php`): rechaza código peligroso, direcciones externas e idiomas mal hechos. Las pruebas están en `tests/` (mira [`tests/LEEME.md`](tests/LEEME.md)).
3. Nada de datos personales en el código, las pruebas ni las capturas: usa datos inventados (`@example.test`).

## Licencia
Al enviar algo, aceptas que se publique bajo la misma licencia del proyecto: **GNU GPL v3 o posterior** ([`LICENSE`](LICENSE)).
